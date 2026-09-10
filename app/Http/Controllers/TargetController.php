<?php

namespace App\Http\Controllers;

use App\Models\Target;
use App\Exports\TargetsExport;
use App\Imports\TargetsImport;
use App\Modules\Core\Controllers\Controller;
use App\Services\TargetAchievementService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class TargetController extends Controller
{
    public function index(Request $request)
    {
        $query = Target::with('targetable');

        if ($request->filled('metric_type')) {
            $query->where('metric_type', $request->metric_type);
        }

        $periodType = $request->input('period_type', 'daily');
        if ($periodType) {
            $query->where('period_type', $periodType);
        }

        $month = $request->input('month', date('n'));
        if ($month && $periodType !== 'yearly') {
            $query->whereMonth('start_date', $month);
        }

        $currentMonth = (int)date('n');
        $currentYear = (int)date('Y');
        $defaultFinancialYear = $currentMonth < 4 ? $currentYear - 1 : $currentYear;
        $financialYear = $request->input('financial_year', $defaultFinancialYear);

        if ($financialYear) {
            $startYear = (int)$financialYear;
            $start = \Carbon\Carbon::createFromDate($startYear, 4, 1)->startOfDay();
            $end = \Carbon\Carbon::createFromDate($startYear + 1, 3, 31)->endOfDay();
            $query->whereBetween('start_date', [$start, $end]);
        }

        // For daily view: only show today and past days — hide upcoming/future dates
        if ($periodType === 'daily') {
            $query->where('start_date', '<=', \Carbon\Carbon::today()->endOfDay());
        }

        if ($request->filled('search')) {
            $searches = (array) $request->search;
            $query->whereHasMorph('targetable', '*', function ($q, $type) use ($searches) {
                $q->where(function ($q2) use ($type, $searches) {
                    foreach ($searches as $search) {
                        if ($type === \App\Modules\Users\Models\User::class) {
                            $q2->orWhere('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%");
                        } elseif ($type === \App\Modules\Users\Models\Team::class || $type === \App\Modules\Users\Models\Department::class) {
                            $q2->orWhere('name', 'like', "%{$search}%");
                        }
                    }
                });
            });
        }

        $perPage = $request->input('per_page', 20);
        $targets = $query->orderByDesc('start_date')->paginate($perPage);

        $stats = [
            'total' => Target::count(),
            'active' => Target::where('status', 'active')->count(),
            'achieved' => Target::where('status', 'achieved')->count(),
            'failed' => Target::where('status', 'failed')->count(),
        ];

        $availableAssignees = [
            'User' => \App\Modules\Users\Models\User::all()->map(function($u) { return ['id' => $u->id, 'name' => $u->name ?? $u->email]; }),
            'Team' => \App\Modules\Users\Models\Team::all()->map(function($t) { return ['id' => $t->id, 'name' => $t->name]; }),
            'Department' => \App\Modules\Users\Models\Department::all()->map(function($d) { return ['id' => $d->id, 'name' => $d->name]; }),
        ];

        return view('targets.index', compact('targets', 'stats', 'availableAssignees'));
    }

    public function create()
    {
        return redirect()->route('targets.index');
    }

    public function store(Request $request)
    {
        $rules = [
            'targetable_type' => 'required|string',
            'targetable_ids' => 'required|array',
            'targetable_ids.*' => 'integer',
            'metric_type' => 'required|string',
            'period_type' => 'required|string',
            'target_amount' => 'required|numeric|min:0',
            'achieved_amount' => 'nullable|numeric|min:0',
            'status' => 'nullable|string|in:active,achieved,failed',
        ];

        if ($request->period_type === 'monthly') {
            $rules['target_month'] = 'required|integer|min:1|max:12';
            $rules['target_year'] = 'required|integer';
        } elseif ($request->period_type === 'yearly') {
            $rules['target_year'] = 'required|integer';
        } else {
            $rules['start_date'] = 'required|date';
            $rules['end_date'] = 'required|date|after_or_equal:start_date';
        }

        $validated = $request->validate($rules);

        if ($validated['targetable_type'] === 'User') {
            $validated['targetable_type'] = \App\Modules\Users\Models\User::class;
        } elseif ($validated['targetable_type'] === 'Team') {
            $validated['targetable_type'] = \App\Modules\Users\Models\Team::class;
        } elseif ($validated['targetable_type'] === 'Department') {
            $validated['targetable_type'] = \App\Modules\Users\Models\Department::class;
        }

        foreach ($validated['targetable_ids'] as $id) {
            $this->createTargetChain($validated, $id);
        }

        return redirect()->route('targets.index')->with('success', 'Targets created successfully.');
    }

    public function createTargetChain($data, $id)
    {
        $baseData = [
            'targetable_type' => $data['targetable_type'],
            'targetable_id' => $id,
            'metric_type' => $data['metric_type'],
            'status' => $data['status'] ?? 'active',
            'achieved_amount' => $data['achieved_amount'] ?? 0,
        ];

        if ($data['period_type'] === 'yearly') {
            $startYear = (int)$data['target_year'];
            $startDate = \Carbon\Carbon::createFromDate($startYear, 4, 1)->startOfDay();
            $endDate = \Carbon\Carbon::createFromDate($startYear + 1, 3, 31)->endOfDay();
            
            $target = Target::firstOrNew([
                'targetable_type' => $baseData['targetable_type'],
                'targetable_id' => $baseData['targetable_id'],
                'metric_type' => $baseData['metric_type'],
                'period_type' => 'yearly',
                'start_date' => $startDate,
                'end_date' => $endDate,
            ]);
            $target->target_amount = $data['target_amount'];
            $target->status = $baseData['status'];
            if (!$target->exists || isset($data['achieved_amount'])) {
                $target->achieved_amount = $baseData['achieved_amount'];
            }
            $target->save();

            $monthlyAmount = round($data['target_amount'] / 12, 2);
            
            for ($m = 0; $m < 12; $m++) {
                $monthDate = $startDate->copy()->addMonths($m);
                $monthData = $data;
                $monthData['period_type'] = 'monthly';
                $monthData['target_month'] = $monthDate->month;
                $monthData['target_year'] = $monthDate->year;
                $monthData['target_amount'] = $monthlyAmount;
                unset($monthData['achieved_amount']); // Don't pass achieved amount to children unless intended
                $this->createTargetChain($monthData, $id);
            }

        } elseif ($data['period_type'] === 'monthly') {
            $startDate = \Carbon\Carbon::createFromDate($data['target_year'], $data['target_month'], 1)->startOfDay();
            $endDate = $startDate->copy()->endOfMonth();

            $target = Target::firstOrNew([
                'targetable_type' => $baseData['targetable_type'],
                'targetable_id' => $baseData['targetable_id'],
                'metric_type' => $baseData['metric_type'],
                'period_type' => 'monthly',
                'start_date' => $startDate,
                'end_date' => $endDate,
            ]);
            $target->target_amount = $data['target_amount'];
            $target->status = $baseData['status'];
            if (!$target->exists || isset($data['achieved_amount'])) {
                $target->achieved_amount = $baseData['achieved_amount'];
            }
            $target->save();

            $workingDays = [];
            $current = $startDate->copy();
            while ($current <= $endDate) {
                if ($current->isWeekday()) {
                    $workingDays[] = $current->copy();
                }
                $current->addDay();
            }

            if (empty($workingDays)) {
                $current = $startDate->copy();
                while ($current <= $endDate) {
                    $workingDays[] = $current->copy();
                    $current->addDay();
                }
            }

            $numDays = count($workingDays);
            if ($numDays > 0) {
                $dailyAmount = round($data['target_amount'] / $numDays, 2);
                foreach ($workingDays as $day) {
                    $dTarget = Target::firstOrNew([
                        'targetable_type' => $baseData['targetable_type'],
                        'targetable_id' => $baseData['targetable_id'],
                        'metric_type' => $baseData['metric_type'],
                        'period_type' => 'daily',
                        'start_date' => $day->copy()->startOfDay(),
                        'end_date' => $day->copy()->endOfDay(),
                    ]);
                    $dTarget->target_amount = $dailyAmount;
                    $dTarget->status = $baseData['status'];
                    if (!$dTarget->exists || isset($data['achieved_amount'])) {
                        $dTarget->achieved_amount = $baseData['achieved_amount'];
                    }
                    $dTarget->save();
                }
            }
        } else {
            if ($data['period_type'] === 'daily') {
                $startDate = \Carbon\Carbon::parse($data['start_date'])->startOfDay();
                $endDate = \Carbon\Carbon::parse($data['end_date'])->endOfDay();

                $workingDays = [];
                $current = $startDate->copy();
                while ($current <= $endDate) {
                    if ($current->isWeekday()) {
                        $workingDays[] = $current->copy();
                    }
                    $current->addDay();
                }

                if (empty($workingDays)) {
                    $current = $startDate->copy();
                    while ($current <= $endDate) {
                        $workingDays[] = $current->copy();
                        $current->addDay();
                    }
                }

                $numDays = count($workingDays);
                if ($numDays > 0) {
                    $dailyAmount = round($data['target_amount'] / $numDays, 2);
                    foreach ($workingDays as $day) {
                        $dTarget = Target::firstOrNew([
                            'targetable_type' => $baseData['targetable_type'],
                            'targetable_id' => $baseData['targetable_id'],
                            'metric_type' => $baseData['metric_type'],
                            'period_type' => 'daily',
                            'start_date' => $day->copy()->startOfDay(),
                            'end_date' => $day->copy()->endOfDay(),
                        ]);
                        $dTarget->target_amount = $dailyAmount;
                        $dTarget->status = $baseData['status'];
                        if (!$dTarget->exists || isset($data['achieved_amount'])) {
                            $dTarget->achieved_amount = $baseData['achieved_amount'];
                        }
                        $dTarget->save();
                    }
                }
            } else {
                $target = Target::firstOrNew([
                    'targetable_type' => $baseData['targetable_type'],
                    'targetable_id' => $baseData['targetable_id'],
                    'metric_type' => $baseData['metric_type'],
                    'period_type' => $data['period_type'],
                    'start_date' => \Carbon\Carbon::parse($data['start_date']),
                    'end_date' => \Carbon\Carbon::parse($data['end_date']),
                ]);
                $target->target_amount = $data['target_amount'];
                $target->status = $baseData['status'];
                if (!$target->exists || isset($data['achieved_amount'])) {
                    $target->achieved_amount = $baseData['achieved_amount'];
                }
                $target->save();
            }
        }
    }

    public function importForm()
    {
        return redirect()->route('targets.index');
    }

    public function importTemplate()
    {
        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="targets_import_template.csv"',
            'Cache-Control'       => 'no-cache',
        ];

        // Columns exactly as the importer expects them
        $columns = [
            'targetable_type',       // User | Team | Department
            'targetable_identifier', // User: email or employee_id  |  Team/Dept: code or name
            'metric_type',           // sales_revenue | orders_count | invoice_collection | payment_collection | calls_made
            'period_type',           // daily | monthly | yearly
            'start_date',            // YYYY-MM-DD
            'end_date',              // YYYY-MM-DD
            'target_amount',
            'status',                // active | achieved | failed
        ];

        $examples = [
            // User – sales_revenue – monthly
            ['User', 'john@example.com',   'sales_revenue',      'monthly', '2026-09-01', '2026-09-30', '50000',   'active'],
            // User – orders_count – daily  (employee_id as identifier)
            ['User', 'EMP001',             'orders_count',       'daily',   '2026-09-10', '2026-09-10', '15',      'active'],
            // User – payment_collection – monthly
            ['User', 'jane@example.com',   'payment_collection', 'monthly', '2026-09-01', '2026-09-30', '30000',   'active'],
            // Team – sales_revenue – yearly  (team code as identifier)
            ['Team', 'GJ',                 'sales_revenue',      'yearly',  '2026-04-01', '2027-03-31', '1000000', 'active'],
            // Team – invoice_collection – monthly
            ['Team', 'MH',                 'invoice_collection', 'monthly', '2026-09-01', '2026-09-30', '200000',  'active'],
            // Department – sales_revenue – monthly  (dept code as identifier)
            ['Department', 'SALES',        'sales_revenue',      'monthly', '2026-09-01', '2026-09-30', '500000',  'active'],
            // Department – calls_made – daily
            ['Department', 'SUPPORT',      'calls_made',         'daily',   '2026-09-10', '2026-09-10', '100',     'active'],
        ];

        $callback = function () use ($columns, $examples) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            foreach ($examples as $row) {
                fputcsv($file, $row);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,csv,xls',
        ]);

        $importer = new TargetsImport;

        try {
            Excel::import($importer, $request->file('file'));
        } catch (\Exception $e) {
            return redirect()->route('targets.index')
                ->withErrors(['import' => 'Import failed: ' . $e->getMessage()]);
        }

        $skipped = $importer->skippedRows;

        if (!empty($skipped)) {
            // Flash each skipped row reason as a separate error message
            return redirect()->route('targets.index')
                ->with('success', count($skipped) . ' row(s) had issues (see warnings below). Any valid rows were imported successfully.')
                ->withErrors($skipped);
        }

        return redirect()->route('targets.index')
            ->with('success', 'Targets imported successfully.');
    }

    public function export(Request $request): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $periodType = $request->query('period_type');
        $metricType = $request->query('metric_type');
        $financialYear = $request->query('financial_year');

        $fileName = 'targets_' . ($periodType ?: 'all') . '_' . date('Ymd_His') . '.xlsx';

        return Excel::download(new TargetsExport(
            $periodType,
            $metricType,
            $financialYear ? (int) $financialYear : null
        ), $fileName);
    }

    public function recalculate(Request $request, TargetAchievementService $service): \Illuminate\Http\RedirectResponse
    {
        $query = Target::query();

        // Honour any active filters so the button recalculates only what the user is viewing
        if ($request->filled('metric_type')) {
            $query->where('metric_type', $request->metric_type);
        }
        if ($request->filled('period_type')) {
            $query->where('period_type', $request->period_type);
        }

        $count = 0;
        $query->each(function (Target $target) use ($service, &$count) {
            $service->recalculate($target);
            $count++;
        });

        return redirect()->back()->with('success', "Recalculated achieved amounts for {$count} target(s) successfully.");
    }

    public function edit(Target $target)
    {
        return redirect()->route('targets.index');
    }

    public function update(Request $request, Target $target)
    {
        $validated = $request->validate([
            'target_amount' => 'required|numeric|min:0',
            'achieved_amount' => 'nullable|numeric|min:0',
            'status' => 'required|string|in:active,achieved,failed',
        ]);

        // Ensure achieved_amount defaults to 0 if not provided
        $validated['achieved_amount'] = $validated['achieved_amount'] ?? $target->achieved_amount;

        $oldAmount = $target->target_amount;
        $target->update($validated);

        if ($target->period_type === 'yearly' && $oldAmount != $validated['target_amount']) {
            $months = Target::where('targetable_type', $target->targetable_type)
                ->where('targetable_id', $target->targetable_id)
                ->where('metric_type', $target->metric_type)
                ->where('period_type', 'monthly')
                ->whereBetween('start_date', [$target->start_date, $target->end_date])
                ->get();

            if ($months->count() > 0) {
                $newAmountPerMonth = round($target->target_amount / $months->count(), 2);
                foreach ($months as $month) {
                    $month->update(['target_amount' => $newAmountPerMonth]);
                    $this->syncDailyTargets($month);
                }
            }
        } elseif ($target->period_type === 'monthly' && $oldAmount != $validated['target_amount']) {
            $startYear = $target->start_date->month < 4 ? $target->start_date->year - 1 : $target->start_date->year;
            
            $yearlyTarget = Target::where('targetable_type', $target->targetable_type)
                ->where('targetable_id', $target->targetable_id)
                ->where('metric_type', $target->metric_type)
                ->where('period_type', 'yearly')
                ->whereYear('start_date', $startYear)
                ->whereMonth('start_date', 4)
                ->first();

            if ($yearlyTarget) {
                $otherMonths = Target::where('targetable_type', $target->targetable_type)
                    ->where('targetable_id', $target->targetable_id)
                    ->where('metric_type', $target->metric_type)
                    ->where('period_type', 'monthly')
                    ->where('id', '!=', $target->id)
                    ->whereBetween('start_date', [$yearlyTarget->start_date, $yearlyTarget->end_date])
                    ->get();

                if ($otherMonths->count() > 0) {
                    $remainingYearlyAmount = $yearlyTarget->target_amount - $target->target_amount;
                    if ($remainingYearlyAmount < 0) $remainingYearlyAmount = 0;
                    
                    $newAmountPerMonth = round($remainingYearlyAmount / $otherMonths->count(), 2);
                    foreach ($otherMonths as $other) {
                        $other->update(['target_amount' => $newAmountPerMonth]);
                        $this->syncDailyTargets($other);
                    }
                }
            }

            $this->syncDailyTargets($target);
        }

        return redirect()->route('targets.index')->with('success', 'Target updated successfully.');
    }

    private function syncDailyTargets(Target $monthTarget)
    {
        $dailyTargets = Target::where('targetable_type', $monthTarget->targetable_type)
            ->where('targetable_id', $monthTarget->targetable_id)
            ->where('metric_type', $monthTarget->metric_type)
            ->where('period_type', 'daily')
            ->whereBetween('start_date', [$monthTarget->start_date, $monthTarget->end_date])
            ->get();

        if ($dailyTargets->count() > 0) {
            $newDailyAmount = round($monthTarget->target_amount / $dailyTargets->count(), 2);
            foreach ($dailyTargets as $daily) {
                $daily->update(['target_amount' => $newDailyAmount]);
            }
        }
    }

    public function destroy(Target $target)
    {
        $target->delete();
        return redirect()->route('targets.index')->with('success', 'Target deleted successfully.');
    }
}
