<?php

namespace App\Exports;

use App\Models\Target;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TargetsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths, WithTitle
{
    public function __construct(
        private ?string $periodType    = null,  // daily | monthly | yearly | null (all)
        private ?string $metricType    = null,
        private ?int    $financialYear = null,
    ) {}

    public function collection(): \Illuminate\Support\Collection
    {
        $query = Target::with('targetable');

        if ($this->periodType) {
            $query->where('period_type', $this->periodType);
        }

        if ($this->metricType) {
            $query->where('metric_type', $this->metricType);
        }

        if ($this->financialYear) {
            // Financial year: Apr 1 of given year → Mar 31 of next year
            $fyStart = Carbon::create($this->financialYear, 4, 1)->startOfDay();
            $fyEnd   = Carbon::create($this->financialYear + 1, 3, 31)->endOfDay();
            $query->where('start_date', '>=', $fyStart)->where('end_date', '<=', $fyEnd);
        }

        return $query->orderBy('period_type')->orderBy('start_date', 'desc')->get();
    }

    public function map($target): array
    {
        $now  = Carbon::now();
        $type = class_basename($target->targetable_type);

        // ── Assignee details ──────────────────────────────────────────────────
        $assigneeName = '';
        $empIdCode    = '';

        if ($target->targetable) {
            $assigneeName = $target->targetable->name ?? '';
            if ($type === 'User') {
                $empIdCode = $target->targetable->employee_id ?? $target->targetable->email ?? '';
            } else {
                $empIdCode = $target->targetable->code ?? '';
            }
        }

        // ── Amounts ───────────────────────────────────────────────────────────
        $targetAmt   = (float) $target->target_amount;
        $achievedAmt = (float) $target->achieved_amount;
        $gapAmt      = max(0, $targetAmt - $achievedAmt);
        $pct         = (float) $target->achievement_percentage;

        // ── Timeline ──────────────────────────────────────────────────────────
        $start = $target->start_date;
        $end   = $target->end_date;

        $elapsedStr   = '';
        $remainingStr = '';

        if ($target->period_type === 'daily') {
            $dayStart = $start->copy()->startOfDay();
            $dayEnd   = $start->copy()->endOfDay();

            if ($now < $dayStart) {
                $elapsedStr = '0h 0m past';
                $remainingStr = '23h 59m left';
            } elseif ($now > $dayEnd) {
                $elapsedStr = '23h 59m past';
                $remainingStr = '0h 0m left';
            } else {
                $elapsedMins = (int) $dayStart->diffInMinutes($now);
                $elapsedStr  = floor($elapsedMins / 60) . 'h ' . ($elapsedMins % 60) . 'm past';
                $leftMins    = (int) $now->diffInMinutes($dayEnd);
                $remainingStr= floor($leftMins / 60) . 'h ' . ($leftMins % 60) . 'm left';
            }
        } else {
            $totalDays = (int) ($start->diffInDays($end) + 1);
            if ($now < $start) {
                $elapsedStr   = '0 days past';
                $remainingStr = $totalDays . ' days left';
            } elseif ($now > $end) {
                $elapsedStr   = $totalDays . ' days past';
                $remainingStr = '0 days left';
            } else {
                $past = (int) $start->copy()->startOfDay()->diffInDays($now->copy()->startOfDay());
                $left = (int) $now->copy()->startOfDay()->diffInDays($end->copy()->endOfDay());
                $elapsedStr   = $past . ' days past';
                $remainingStr = $left . ' days left';
            }
        }

        $metricName = ucwords(str_replace('_', ' ', $target->metric_type));
        $periodName = ucfirst($target->period_type);

        return [
            $type,                                        // Assignee Type
            $assigneeName,                                // User Name
            $empIdCode,                                   // EMP ID / Code
            $metricName,                                  // Metric Type
            $periodName,                                  // Period Type
            $start->format('Y-m-d'),                      // Start Date
            $end->format('Y-m-d'),                        // End Date
            $elapsedStr,                                  // Timeline Elapsed
            $remainingStr,                                // Timeline Remaining
            number_format($targetAmt, 2, '.', ''),        // Target
            number_format($achievedAmt, 2, '.', ''),      // Achieved
            number_format($gapAmt, 2, '.', ''),           // Remaining
            number_format($pct, 2, '.', '') . '%',        // Progress (%)
            ucfirst($target->status),                     // Status
        ];
    }

    public function headings(): array
    {
        return [
            'Assignee Type',
            'User Name',
            'EMP ID / Code',
            'Metric Type',
            'Period Type',
            'Start Date',
            'End Date',
            'Timeline Elapsed',
            'Timeline Remaining',
            'Target',
            'Achieved',
            'Remaining',
            'Progress (%)',
            'Status',
        ];
    }

    public function title(): string
    {
        return match ($this->periodType) {
            'daily'   => 'Daily Targets',
            'monthly' => 'Monthly Targets',
            'yearly'  => 'Yearly Targets',
            default   => 'All Targets',
        };
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '2563EB']],
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 16, 'B' => 22, 'C' => 22, 'D' => 20,
            'E' => 14, 'F' => 14, 'G' => 14, 'H' => 18,
            'I' => 18, 'J' => 14, 'K' => 14, 'L' => 14,
            'M' => 16, 'N' => 12,
        ];
    }
}
