<?php

namespace App\Http\Controllers;

use App\Models\Target;
use App\Exports\TargetsExport;
use App\Imports\TargetsImport;
use App\Modules\Core\Controllers\Controller;
use App\Services\TargetAchievementService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class TargetController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:target-view|target-view-all', only: ['index']),
            new Middleware('permission:target-export', only: ['export']),
            new Middleware('permission:target-create', only: ['create', 'store', 'importForm', 'importTemplate', 'import']),
            new Middleware('permission:target-edit', only: ['edit', 'update', 'recalculate']),
            new Middleware('permission:target-delete', only: ['destroy']),
        ];
    }

    public function index(Request $request)
    {
        $request->validate([
            'metric_type' => 'nullable|in:sales_revenue,orders_count,invoice_collection,payment_collection,calls_made',
            'period_type' => 'nullable|in:daily,monthly,yearly',
            'month' => 'nullable|integer|between:1,12',
            'financial_year' => 'nullable|integer|between:2000,2100',
            'per_page' => 'nullable|in:10,20,50,100',
            'search' => 'nullable|array',
            'search.*' => 'string|max:100',
        ]);

        $query = Target::with('targetable');
        
        $user = auth()->user();
        if ($user && !$user->hasAnyRole(['Super Admin', 'Admin']) && !$user->can('target-view-all')) {
            $query->where('targetable_type', $user->getMorphClass())
                  ->where('targetable_id', $user->id);
        }

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

        $baseStatsQuery = clone $query;
        
        $perPage = $request->integer('per_page', 20);
        $targets = $query->orderByDesc('start_date')->orderByDesc('id')->paginate($perPage);
        $this->addPaceMetrics($targets->getCollection());

        $stats = [
            'total' => (clone $baseStatsQuery)->count(),
            'active' => (clone $baseStatsQuery)->where('status', 'active')->count(),
            'achieved' => (clone $baseStatsQuery)->where('status', 'achieved')->count(),
            'failed' => (clone $baseStatsQuery)->where('status', 'failed')->count(),
        ];

        $isGlobalViewer = $user && ($user->hasAnyRole(['Super Admin', 'Admin']) || $user->can('target-view-all'));

        if ($isGlobalViewer) {
            $availableAssignees = [
                'User' => \App\Modules\Users\Models\User::select('id', 'name', 'email')->get()->map(function($u) { return ['id' => $u->id, 'name' => $u->name ?? $u->email]; }),
                'Team' => \App\Modules\Users\Models\Team::select('id', 'name')->get()->map(function($t) { return ['id' => $t->id, 'name' => $t->name]; }),
                'Department' => \App\Modules\Users\Models\Department::select('id', 'name')->get()->map(function($d) { return ['id' => $d->id, 'name' => $d->name]; }),
            ];
        } else {
            $availableAssignees = [
                'User' => $user ? collect([['id' => $user->id, 'name' => $user->name ?? $user->email]]) : collect(),
                'Team' => collect(),
                'Department' => collect(),
            ];
        }

        return view('targets.index', compact('targets', 'stats', 'availableAssignees'));
    }

    public function create()
    {
        return redirect()->route('targets.index');
    }

    public function store(Request $request)
    {
        $targetableTable = match ($request->input('targetable_type')) {
            'User' => 'users',
            'Team' => 'teams',
            'Department' => 'departments',
            default => 'users',
        };

        $rules = [
            'targetable_type' => 'required|string|in:User,Team,Department',
            'targetable_ids' => 'required|array|min:1',
            'targetable_ids.*' => 'required|integer|min:1|distinct|exists:'.$targetableTable.',id',
            'metric_type' => 'required|string|in:sales_revenue,orders_count,invoice_collection,payment_collection,calls_made',
            'period_type' => 'required|string|in:daily,monthly,yearly',
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

        $user = auth()->user();
        if ($user && !$user->hasAnyRole(['Super Admin', 'Admin']) && !$user->can('target-view-all')) {
            if ($validated['targetable_type'] !== $user->getMorphClass() || count($validated['targetable_ids']) !== 1 || (int) $validated['targetable_ids'][0] !== $user->id) {
                abort(403, 'Unauthorized action. You can only assign targets to yourself.');
            }
        }

        DB::transaction(function () use ($validated) {
            foreach ($validated['targetable_ids'] as $id) {
                $this->createTargetChain($validated, $id);

                if ($validated['period_type'] === 'yearly') {
                    $rangeStart = \Carbon\Carbon::createFromDate((int) $validated['target_year'], 4, 1)->startOfDay();
                    $rangeEnd = $rangeStart->copy()->addYear()->subDay()->endOfDay();
                } elseif ($validated['period_type'] === 'monthly') {
                    $rangeStart = \Carbon\Carbon::createFromDate((int) $validated['target_year'], (int) $validated['target_month'], 1)->startOfDay();
                    $rangeEnd = $rangeStart->copy()->endOfMonth()->endOfDay();
                } else {
                    $rangeStart = \Carbon\Carbon::parse($validated['start_date'])->startOfDay();
                    $rangeEnd = \Carbon\Carbon::parse($validated['end_date'])->endOfDay();
                }

                $this->recalculateTargetsInRange(
                    $validated['targetable_type'],
                    (int) $id,
                    $validated['metric_type'],
                    $rangeStart,
                    $rangeEnd
                );
            }
        });

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
            
            $target = $this->firstOrRestoreTarget([
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

            $monthPeriods = [];
            for ($m = 0; $m < 12; $m++) {
                $monthStart = $startDate->copy()->addMonths($m)->startOfDay();
                $monthPeriods[] = [
                    'start' => $monthStart,
                    'end' => $monthStart->copy()->endOfMonth()->endOfDay(),
                ];
            }
            $monthlyAmounts = $this->allocateAmountByWorkingDays($data['target_amount'], $monthPeriods);
            
            for ($m = 0; $m < 12; $m++) {
                $monthDate = $monthPeriods[$m]['start'];
                $monthData = $data;
                $monthData['period_type'] = 'monthly';
                $monthData['target_month'] = $monthDate->month;
                $monthData['target_year'] = $monthDate->year;
                $monthData['target_amount'] = $monthlyAmounts[$m];
                unset($monthData['achieved_amount']); // Don't pass achieved amount to children unless intended
                $this->createTargetChain($monthData, $id);
            }

        } elseif ($data['period_type'] === 'monthly') {
            $startDate = \Carbon\Carbon::createFromDate($data['target_year'], $data['target_month'], 1)->startOfDay();
            $endDate = $startDate->copy()->endOfMonth();

            $target = $this->firstOrRestoreTarget([
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
                $dailyAmounts = $this->distributeAmount($data['target_amount'], $numDays);
                foreach ($workingDays as $index => $day) {
                    $dTarget = $this->firstOrRestoreTarget([
                        'targetable_type' => $baseData['targetable_type'],
                        'targetable_id' => $baseData['targetable_id'],
                        'metric_type' => $baseData['metric_type'],
                        'period_type' => 'daily',
                        'start_date' => $day->copy()->startOfDay(),
                        'end_date' => $day->copy()->endOfDay(),
                    ]);
                    $dTarget->target_amount = $dailyAmounts[$index];
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
                    $dailyAmounts = $this->distributeAmount($data['target_amount'], $numDays);
                    foreach ($workingDays as $index => $day) {
                        $dTarget = $this->firstOrRestoreTarget([
                            'targetable_type' => $baseData['targetable_type'],
                            'targetable_id' => $baseData['targetable_id'],
                            'metric_type' => $baseData['metric_type'],
                            'period_type' => 'daily',
                            'start_date' => $day->copy()->startOfDay(),
                            'end_date' => $day->copy()->endOfDay(),
                        ]);
                        $dTarget->target_amount = $dailyAmounts[$index];
                        $dTarget->status = $baseData['status'];
                        if (!$dTarget->exists || isset($data['achieved_amount'])) {
                            $dTarget->achieved_amount = $baseData['achieved_amount'];
                        }
                        $dTarget->save();
                    }
                }
            } else {
                $target = $this->firstOrRestoreTarget([
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
        $request->validate([
            'period_type' => 'nullable|in:daily,monthly,yearly',
            'metric_type' => 'nullable|in:sales_revenue,orders_count,invoice_collection,payment_collection,calls_made',
            'financial_year' => 'nullable|integer|between:2000,2100',
            'month' => 'nullable|integer|between:1,12',
            'search' => 'nullable|array',
            'search.*' => 'string|max:100',
        ]);

        $periodType = $request->query('period_type');
        $metricType = $request->query('metric_type');
        $financialYear = $request->query('financial_year');

        $fileName = 'targets_' . ($periodType ?: 'all') . '_' . date('Ymd_His') . '.xlsx';

        return Excel::download(new TargetsExport(
            $periodType,
            $metricType,
            $financialYear ? (int) $financialYear : null,
            $request->filled('month') ? (int) $request->query('month') : null,
            (array) $request->query('search', [])
        ), $fileName);
    }

    public function recalculate(Request $request, TargetAchievementService $service): \Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'metric_type' => 'nullable|in:sales_revenue,orders_count,invoice_collection,payment_collection,calls_made',
            'period_type' => 'required|in:daily,monthly,yearly',
            'month' => 'nullable|integer|between:1,12',
            'financial_year' => 'nullable|integer|between:2000,2100',
            'search' => 'nullable|array',
            'search.*' => 'string|max:100',
        ]);

        $query = Target::query();
        
        $user = auth()->user();
        if ($user && !$user->hasAnyRole(['Super Admin', 'Admin']) && !$user->can('target-view-all')) {
            $query->where('targetable_type', $user->getMorphClass())
                  ->where('targetable_id', $user->id);
        }

        // Recalculate every target matching the active filters, across all pages.
        if ($request->filled('metric_type')) {
            $query->where('metric_type', $request->metric_type);
        }
        $periodType = $request->input('period_type', 'daily');
        $query->where('period_type', $periodType);

        $month = $request->input('month', date('n'));
        if ($month && $periodType !== 'yearly') {
            $query->whereMonth('start_date', $month);
        }

        $financialYear = $request->input('financial_year');
        if ($financialYear) {
            $start = \Carbon\Carbon::createFromDate((int) $financialYear, 4, 1)->startOfDay();
            $end = $start->copy()->addYear()->subDay()->endOfDay();
            $query->whereBetween('start_date', [$start, $end]);
        }

        if ($periodType === 'daily') {
            $query->where('start_date', '<=', \Carbon\Carbon::today()->endOfDay());
        }

        if ($request->filled('search')) {
            $searches = (array) $request->input('search');
            $query->whereHasMorph('targetable', '*', function ($q, $type) use ($searches) {
                $q->where(function ($q2) use ($type, $searches) {
                    foreach ($searches as $search) {
                        if ($type === \App\Modules\Users\Models\User::class) {
                            $q2->orWhere('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        } elseif ($type === \App\Modules\Users\Models\Team::class || $type === \App\Modules\Users\Models\Department::class) {
                            $q2->orWhere('name', 'like', "%{$search}%");
                        }
                    }
                });
            });
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
        $user = auth()->user();
        if ($user && !$user->hasAnyRole(['Super Admin', 'Admin']) && !$user->can('target-view-all')) {
            if ($target->targetable_type !== $user->getMorphClass() || $target->targetable_id !== $user->id) {
                abort(403, 'Unauthorized action.');
            }
        }

        $validated = $request->validate([
            'target_amount' => 'required|numeric|min:0',
            'status' => 'required|string|in:active,achieved,failed',
        ]);

        DB::transaction(function () use ($validated, $target) {
            $oldAmount = (float) $target->target_amount;
            $target->target_amount = $validated['target_amount'];
            $target->save();

            if ($target->period_type === 'yearly' && $oldAmount !== (float) $validated['target_amount']) {
                $months = Target::where('targetable_type', $target->targetable_type)
                    ->where('targetable_id', $target->targetable_id)
                    ->where('metric_type', $target->metric_type)
                    ->where('period_type', 'monthly')
                    ->whereBetween('start_date', [$target->start_date, $target->end_date])
                    ->orderBy('start_date')
                    ->get();

                $amounts = $this->allocateAmountByWorkingDays($target->target_amount, $months->all());
                foreach ($months as $index => $month) {
                    $month->update(['target_amount' => $amounts[$index]]);
                    $this->syncDailyTargets($month);
                }
            } elseif ($target->period_type === 'monthly' && $oldAmount !== (float) $validated['target_amount']) {
                $startYear = $target->start_date->month < 4 ? $target->start_date->year - 1 : $target->start_date->year;
            
                $yearlyTarget = Target::where('targetable_type', $target->targetable_type)
                    ->where('targetable_id', $target->targetable_id)
                    ->where('metric_type', $target->metric_type)
                    ->where('period_type', 'yearly')
                    ->whereYear('start_date', $startYear)
                    ->whereMonth('start_date', 4)
                    ->first();

                if ($yearlyTarget) {
                    $monthlyTargetCents = (int) round((float) $target->target_amount * 100);
                    $yearlyTargetCents = (int) round((float) $yearlyTarget->target_amount * 100);
                    if ($monthlyTargetCents > $yearlyTargetCents) {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            'target_amount' => 'The monthly target cannot exceed its annual target. Increase the annual target first.',
                        ]);
                    }

                    $otherMonths = Target::where('targetable_type', $target->targetable_type)
                        ->where('targetable_id', $target->targetable_id)
                        ->where('metric_type', $target->metric_type)
                        ->where('period_type', 'monthly')
                        ->where('id', '!=', $target->id)
                        ->whereBetween('start_date', [$yearlyTarget->start_date, $yearlyTarget->end_date])
                        ->orderBy('start_date')
                        ->get();

                    if ($otherMonths->count() > 0) {
                        $remainingYearlyAmount = max(0, (float) $yearlyTarget->target_amount - (float) $target->target_amount);
                        $amounts = $this->allocateAmountByWorkingDays($remainingYearlyAmount, $otherMonths->all());
                        foreach ($otherMonths as $index => $other) {
                            $other->update(['target_amount' => $amounts[$index]]);
                            $this->syncDailyTargets($other);
                        }
                    } elseif ($monthlyTargetCents !== $yearlyTargetCents) {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            'target_amount' => 'This fiscal year has no other monthly target allocations to rebalance.',
                        ]);
                    }
                }

                $this->syncDailyTargets($target);
            }

            app(TargetAchievementService::class)->recalculate($target);
            if (isset($months)) {
                foreach ($months as $month) {
                    app(TargetAchievementService::class)->recalculate($month);
                    Target::where('targetable_type', $month->targetable_type)
                        ->where('targetable_id', $month->targetable_id)
                        ->where('metric_type', $month->metric_type)
                        ->where('period_type', 'daily')
                        ->whereBetween('start_date', [$month->start_date, $month->end_date])
                        ->each(fn (Target $day) => app(TargetAchievementService::class)->recalculate($day));
                }
            } elseif (isset($otherMonths)) {
                foreach ($otherMonths as $month) {
                    app(TargetAchievementService::class)->recalculate($month);
                    Target::where('targetable_type', $month->targetable_type)
                        ->where('targetable_id', $month->targetable_id)
                        ->where('metric_type', $month->metric_type)
                        ->where('period_type', 'daily')
                        ->whereBetween('start_date', [$month->start_date, $month->end_date])
                        ->each(fn (Target $day) => app(TargetAchievementService::class)->recalculate($day));
                }
            }
            if ($target->period_type === 'monthly') {
                Target::where('targetable_type', $target->targetable_type)
                    ->where('targetable_id', $target->targetable_id)
                    ->where('metric_type', $target->metric_type)
                    ->where('period_type', 'daily')
                    ->whereBetween('start_date', [$target->start_date, $target->end_date])
                    ->each(fn (Target $day) => app(TargetAchievementService::class)->recalculate($day));
            }
        });

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
            $amounts = $this->distributeAmount($monthTarget->target_amount, $dailyTargets->count());
            foreach ($dailyTargets as $index => $daily) {
                $daily->update(['target_amount' => $amounts[$index]]);
            }
        }
    }

    /** Split a currency amount into cent-accurate parts whose sum equals the source amount. */
    private function distributeAmount(float|int|string $amount, int $parts): array
    {
        if ($parts <= 0) {
            return [];
        }

        $totalCents = (int) round((float) $amount * 100);
        $baseCents = intdiv($totalCents, $parts);
        $remainderCents = $totalCents % $parts;

        return array_map(
            fn (int $index) => ($baseCents + ($index < $remainderCents ? 1 : 0)) / 100,
            range(0, $parts - 1)
        );
    }

    /** Allocate a total across periods in proportion to their working days, preserving every cent. */
    private function allocateAmountByWorkingDays(float|int|string $amount, array $periods): array
    {
        if ($periods === []) {
            return [];
        }

        $dayCounts = array_map(function ($period): int {
            $start = $period instanceof Target
                ? $period->start_date->copy()->startOfDay()
                : $period['start']->copy()->startOfDay();
            $end = $period instanceof Target
                ? $period->end_date->copy()->startOfDay()
                : $period['end']->copy()->startOfDay();

            return $this->workingDaysBetween($start, $end);
        }, $periods);
        $dailyAmounts = $this->distributeAmount($amount, array_sum($dayCounts));
        $allocations = [];
        $offset = 0;

        foreach ($dayCounts as $dayCount) {
            $periodCents = 0;
            foreach (array_slice($dailyAmounts, $offset, $dayCount) as $dailyAmount) {
                $periodCents += (int) round($dailyAmount * 100);
            }
            $allocations[] = $periodCents / 100;
            $offset += $dayCount;
        }

        return $allocations;
    }

    private function firstOrRestoreTarget(array $identity): Target
    {
        $target = Target::withTrashed()->firstOrNew($identity);
        if ($target->exists && $target->trashed()) {
            $target->restore();
        }

        return $target;
    }

    private function addPaceMetrics(\Illuminate\Support\Collection $targets): void
    {
        $today = \Carbon\Carbon::today();

        foreach ($targets as $target) {
            $start = $target->start_date->copy()->startOfDay();
            $end = $target->end_date->copy()->startOfDay();
            $remainingStart = $today->greaterThan($start) ? $today->copy() : $start->copy();
            $remainingDays = $today->greaterThan($end) ? 0 : $this->workingDaysBetween($remainingStart, $end);
            $elapsedEnd = $today->lessThan($end) ? $today->copy() : $end->copy();
            $elapsedDays = $today->lessThan($start) ? 0 : $this->workingDaysBetween($start, $elapsedEnd);
            $futureStart = $today->copy()->addDay();
            $futureDays = $futureStart->greaterThan($end)
                ? 0
                : $this->workingDaysBetween($futureStart->greaterThan($start) ? $futureStart : $start, $end);
            $gap = max(0, (float) $target->target_amount - (float) $target->achieved_amount);

            $target->setAttribute('remaining_workdays', $remainingDays);
            $target->setAttribute('required_per_workday', $remainingDays > 0 ? round($gap / $remainingDays, 2) : null);
            $target->setAttribute('actual_per_workday', $elapsedDays > 0 ? round((float) $target->achieved_amount / $elapsedDays, 2) : null);
            $target->setAttribute('projected_amount', $elapsedDays > 0
                ? round(((float) $target->achieved_amount / $elapsedDays) * max(1, $elapsedDays + $futureDays), 2)
                : null);
            $target->setAttribute('projected_percentage', $elapsedDays > 0 && (float) $target->target_amount > 0
                ? round(((float) $target->projected_amount / (float) $target->target_amount) * 100, 1)
                : null);
        }
    }

    private function workingDaysBetween(\Carbon\Carbon $start, \Carbon\Carbon $end): int
    {
        if ($end->lessThan($start)) {
            return 0;
        }

        $count = 0;
        $day = $start->copy();
        while ($day->lessThanOrEqualTo($end)) {
            if ($day->isWeekday()) {
                $count++;
            }
            $day->addDay();
        }

        // Match target generation's fallback for periods containing only weekends.
        return $count ?: $start->diffInDays($end) + 1;
    }

    private function recalculateTargetsInRange(string $targetableType, int $targetableId, string $metricType, $start, $end): void
    {
        $service = app(TargetAchievementService::class);

        Target::where('targetable_type', $targetableType)
            ->where('targetable_id', $targetableId)
            ->where('metric_type', $metricType)
            ->whereBetween('start_date', [$start, $end])
            ->orderBy('start_date')
            ->each(fn (Target $target) => $service->recalculate($target));
    }

    public function destroy(Target $target)
    {
        $user = auth()->user();
        if ($user && !$user->hasAnyRole(['Super Admin', 'Admin']) && !$user->can('target-view-all')) {
            if ($target->targetable_type !== $user->getMorphClass() || $target->targetable_id !== $user->id) {
                abort(403, 'Unauthorized action.');
            }
        }
        
        $target->delete();
        return redirect()->route('targets.index')->with('success', 'Target deleted successfully.');
    }
}
