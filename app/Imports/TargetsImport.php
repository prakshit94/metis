<?php

namespace App\Imports;

use App\Models\Target;
use App\Modules\Users\Models\User;
use App\Modules\Users\Models\Team;
use App\Modules\Users\Models\Department;
use App\Services\TargetAchievementService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;

class TargetsImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    /** Rows that were skipped due to validation/lookup failures */
    public array $skippedRows = [];

    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            $rowNum = $index + 2; // +2 because row 1 is the header

            $type       = trim($row['assignee_type'] ?? $row['targetable_type'] ?? '');
            $identifier = trim($row['emp_id_code'] ?? $row['targetable_identifier'] ?? '');

            if (empty($type) || empty($identifier)) {
                $this->skippedRows[] = "Row {$rowNum}: missing Assignee Type or EMP ID / Code.";
                continue;
            }

            // Convert UI metric name to enum
            $rawMetric = trim($row['metric_type'] ?? '');
            $metricType = strtolower(str_replace(' ', '_', $rawMetric));
            $metricType = match ($metricType) {
                'invoiced_amount' => 'invoice_collection',
                'outbound_call_attempts' => 'calls_made',
                default => $metricType,
            };

            $periodType = strtolower(trim($row['period_type'] ?? ''));
            $startDate  = trim($row['start_date'] ?? '');
            $endDate    = trim($row['end_date'] ?? '');
            $targetAmt  = $row['target'] ?? $row['target_amount'] ?? null;

            if (empty($metricType) || empty($periodType) || empty($startDate) || empty($endDate) || $targetAmt === null) {
                $this->skippedRows[] = "Row {$rowNum}: missing required field(s) — metric_type, period_type, start_date, end_date, or target amount.";
                continue;
            }

            $validMetrics = ['sales_revenue', 'orders_count', 'invoice_collection', 'payment_collection', 'calls_made'];
            if (!in_array($metricType, $validMetrics)) {
                $this->skippedRows[] = "Row {$rowNum}: invalid metric_type '{$metricType}'. Allowed: " . implode(', ', $validMetrics);
                continue;
            }

            $validPeriods = ['daily', 'monthly', 'yearly'];
            if (!in_array($periodType, $validPeriods)) {
                $this->skippedRows[] = "Row {$rowNum}: invalid period_type '{$periodType}'. Allowed: " . implode(', ', $validPeriods);
                continue;
            }

            if (!is_numeric($targetAmt) || (float) $targetAmt < 0) {
                $this->skippedRows[] = "Row {$rowNum}: target amount must be a non-negative number.";
                continue;
            }

            try {
                $parsedStart = Carbon::parse($startDate);
                $parsedEnd = Carbon::parse($endDate);
            } catch (\Throwable) {
                $this->skippedRows[] = "Row {$rowNum}: start_date or end_date is invalid.";
                continue;
            }

            if ($parsedEnd->lessThan($parsedStart)) {
                $this->skippedRows[] = "Row {$rowNum}: end_date must be on or after start_date.";
                continue;
            }

            $targetable = $this->resolveTargetable($type, $identifier);
            if (!$targetable) {
                $this->skippedRows[] = "Row {$rowNum}: could not find {$type} with identifier '{$identifier}'.";
                continue;
            }

            try {
                $target = Target::withTrashed()->firstOrNew([
                    'targetable_id'   => $targetable->id,
                    'targetable_type' => get_class($targetable),
                    'metric_type'     => $metricType,
                    'period_type'     => $periodType,
                    'start_date'      => $startDate,
                ]);
                if ($target->exists && $target->trashed()) {
                    $target->restore();
                }

                $target->end_date = $endDate;
                $target->target_amount = (float) $targetAmt;
                $target->status = in_array(strtolower($row['status'] ?? ''), ['active', 'achieved', 'failed'])
                    ? strtolower($row['status'])
                    : 'active';
                if (!$target->exists) {
                    $target->achieved_amount = 0;
                }
                $target->save();
                app(TargetAchievementService::class)->recalculate($target);
            } catch (\Exception $e) {
                $this->skippedRows[] = "Row {$rowNum}: failed to save — {$e->getMessage()}";
            }
        }
    }

    private function resolveTargetable(string $type, string $identifier): ?object
    {
        return match (strtolower($type)) {
            'user'       => User::where('email', $identifier)->orWhere('employee_id', $identifier)->first(),
            'team'       => Team::where('code', $identifier)->orWhere('name', $identifier)->first(),
            'department' => Department::where('code', $identifier)->orWhere('name', $identifier)->first(),
            default      => null,
        };
    }
}
