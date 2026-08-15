<?php

namespace App\Modules\Users\Controllers;

use App\Modules\Core\Controllers\Controller;
use App\Modules\Users\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:attendance-view', only: ['index', 'show']),
            new Middleware('permission:attendance-create', only: ['store']),
            new Middleware('permission:attendance-edit', only: ['update']),
            new Middleware('permission:attendance-delete', only: ['destroy', 'bulkAction', 'forceDelete']),
        ];
    }

    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('attendance-view'), 403);

        $sortMap = [
            'date' => 'date',
            'user' => 'users.name',
            'status' => 'status',
            'check_in' => 'check_in',
            'check_out' => 'check_out',
        ];
        
        $sortBy = $sortMap[$request->input('sort_by', 'date')] ?? 'date';
        $sortDir = strtolower((string) $request->input('sort_dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        
        $requestedPerPage = (int) $request->input('per_page', 15);
        $perPage = $requestedPerPage === -1 ? -1 : min(max($requestedPerPage, 1), 100);

        $deletedFilter = $request->input('deleted');

        $query = Attendance::query()
            ->select('attendances.*')
            ->join('users', 'attendances.user_id', '=', 'users.id')
            ->where('attendances.user_id', $request->user()->id)
            ->when($deletedFilter === 'with', fn ($q) => $q->withTrashed())
            ->when($deletedFilter === 'only', fn ($q) => $q->onlyTrashed())
            ->with(['user:id,name,employee_id'])
            ->when(
                $request->filled('search'),
                fn ($q) => $q->where(function ($inner) use ($request): void {
                    $term = '%'.$request->input('search').'%';
                    $inner->where('users.name', 'like', $term)
                        ->orWhere('users.employee_id', 'like', $term);
                })
            )
            ->when(
                $request->filled('status'),
                fn ($q) => $q->where('attendances.status', $request->input('status'))
            )
            ->when(
                $request->filled('date'),
                fn ($q) => $q->whereDate('attendances.date', $request->input('date'))
            )
            ->when(
                $request->filled('start_date') && $request->filled('end_date'),
                fn ($q) => $q->whereBetween('attendances.date', [$request->input('start_date'), $request->input('end_date')])
            )
            ->orderBy($sortBy, $sortDir);

        if ($perPage === -1) {
            $attendances = $query->get();
            $responsePayload = [
                'data' => $attendances,
                'total' => $attendances->count(),
                'per_page' => $attendances->count(),
                'current_page' => 1,
                'last_page' => 1,
            ];

            if ($request->filled('start_date') && $request->filled('end_date')) {
                $startDate = $request->input('start_date');
                $endDate = $request->input('end_date');
                
                $leaves = \App\Modules\Users\Models\Leave::where('user_id', $request->user()->id)
                    ->where(function ($q) use ($startDate, $endDate) {
                        $q->whereBetween('start_date', [$startDate, $endDate])
                          ->orWhereBetween('end_date', [$startDate, $endDate])
                          ->orWhere(function ($q2) use ($startDate, $endDate) {
                              $q2->where('start_date', '<', $startDate)
                                 ->where('end_date', '>', $endDate);
                          });
                    })
                    ->get();
                    
                $responsePayload['leaves'] = $leaves;
            }

            return response()->json($responsePayload);
        }

        $attendances = $query->paginate($perPage);

        $responsePayload = [
            'data' => $attendances->items(),
            'current_page' => $attendances->currentPage(),
            'last_page' => $attendances->lastPage(),
            'per_page' => $attendances->perPage(),
            'total' => $attendances->total(),
        ];

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            
            $leaves = \App\Modules\Users\Models\Leave::where('user_id', $request->user()->id)
                ->where(function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('start_date', [$startDate, $endDate])
                      ->orWhereBetween('end_date', [$startDate, $endDate])
                      ->orWhere(function ($q2) use ($startDate, $endDate) {
                          $q2->where('start_date', '<', $startDate)
                             ->where('end_date', '>', $endDate);
                      });
                })
                ->get();
                
            $responsePayload['leaves'] = $leaves;
        }

        return response()->json($responsePayload);
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('attendance-create'), 403);

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'date' => 'required|date',
            'check_in_time' => 'nullable|date_format:Y-m-d\TH:i',
            'check_out_time' => 'nullable|date_format:Y-m-d\TH:i|after:check_in_time',
            'status' => 'required|in:Present,Absent,Half-Day,Late',
            'notes' => 'nullable|string',
        ]);

        $checkIn = !empty($validated['check_in_time']) ? date('H:i:s', strtotime($validated['check_in_time'])) : null;
        $checkOut = !empty($validated['check_out_time']) ? date('H:i:s', strtotime($validated['check_out_time'])) : null;

        $attendance = Attendance::create([
            'user_id' => $validated['user_id'],
            'date' => $validated['date'],
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'status' => $validated['status'],
            'notes' => $validated['notes'] ?? null,
        ]);

        return response()->json(['data' => $attendance, 'message' => 'Attendance recorded successfully.'], 200);
    }
    
    public function update(Request $request, int|string $id): JsonResponse
    {
        abort_unless($request->user()?->can('attendance-edit'), 403);

        $attendance = Attendance::withTrashed()->findOrFail($id);

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'date' => 'required|date',
            'check_in_time' => 'nullable|date_format:Y-m-d\TH:i',
            'check_out_time' => 'nullable|date_format:Y-m-d\TH:i|after:check_in_time',
            'status' => 'required|in:Present,Absent,Half-Day,Late',
            'notes' => 'nullable|string',
        ]);

        $checkIn = !empty($validated['check_in_time']) ? date('H:i:s', strtotime($validated['check_in_time'])) : null;
        $checkOut = !empty($validated['check_out_time']) ? date('H:i:s', strtotime($validated['check_out_time'])) : null;

        $attendance->update([
            'user_id' => $validated['user_id'],
            'date' => $validated['date'],
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'status' => $validated['status'],
            'notes' => $validated['notes'] ?? null,
        ]);

        return response()->json(['data' => $attendance, 'message' => 'Attendance updated successfully.'], 200);
    }

    public function show(Request $request, int|string $id): JsonResponse
    {
        abort_unless($request->user()?->can('attendance-view'), 403);

        $attendance = Attendance::withTrashed()->with('user')->findOrFail($id);
        return response()->json(['data' => $attendance]);
    }
    
    public function destroy(Request $request, int|string $id): JsonResponse
    {
        abort_unless($request->user()?->can('attendance-delete'), 403);

        $attendance = Attendance::withTrashed()->findOrFail($id);

        if ($attendance->trashed()) {
            return response()->json([
                'message' => "Attendance record is already temporarily deleted.",
            ], 400);
        }

        $attendance->delete();

        return response()->json([
            'message' => "Attendance record deleted successfully.",
        ]);
    }
    
    public function restore(Request $request, int|string $id): JsonResponse
    {
        // For simplicity reusing user-restore or a new permission attendance-restore
        abort_unless($request->user()?->can('attendance-edit'), 403);

        $attendance = Attendance::withTrashed()->findOrFail($id);

        if (! $attendance->trashed()) {
            return response()->json([
                'message' => "Attendance record is not deleted.",
            ], 400);
        }

        $attendance->restore();

        return response()->json([
            'message' => "Attendance record restored successfully.",
        ]);
    }

    public function forceDelete(Request $request, int|string $id): JsonResponse
    {
        abort_unless($request->user()?->can('attendance-delete'), 403);

        $attendance = Attendance::withTrashed()->findOrFail($id);
        $attendance->forceDelete();

        return response()->json([
            'message' => "Attendance record permanently deleted.",
        ]);
    }
    
    public function exportSummary(Request $request)
    {
        abort_unless($request->user()?->can('attendance-view'), 403);

        $month = $request->input('month', date('Y-m')); // 'YYYY-MM'
        $year = (int) substr($month, 0, 4);
        $m = (int) substr($month, 5, 2);
        
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $m, $year);
        
        $attendances = Attendance::with('user:id,name,employee_id')
            ->where('user_id', $request->user()->id)
            ->whereYear('date', $year)
            ->whereMonth('date', $m)
            ->get();
        $grouped = $attendances->groupBy(function($item) {
            return \Carbon\Carbon::parse($item->date)->format('Y-m-d');
        });

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=attendance_summary_{$month}.csv",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function() use ($grouped, $daysInMonth, $year, $m, $request) {
            $file = fopen('php://output', 'w');
            
            $columns = ['Employee', 'Employee ID'];
            for ($i = 1; $i <= $daysInMonth; $i++) {
                $columns[] = $i;
            }
            fputcsv($file, $columns);
            
            $user = $request->user();
            $row = [$user->name, $user->employee_id ?? 'N/A'];
            
            for ($i = 1; $i <= $daysInMonth; $i++) {
                $dateStr = sprintf('%04d-%02d-%02d', $year, $m, $i);
                
                if (isset($grouped[$dateStr])) {
                    $dayAttendances = $grouped[$dateStr];
                    $status = 'A';
                    foreach ($dayAttendances as $att) {
                        if ($att->status !== 'Absent') {
                            $status = substr($att->status, 0, 1);
                        }
                    }
                    $row[] = $status;
                } else {
                    $row[] = 'A';
                }
            }
            
            fputcsv($file, $row);
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportDetailed(Request $request)
    {
        abort_unless($request->user()?->can('attendance-view'), 403);
        
        $month = $request->input('month', date('Y-m')); // 'YYYY-MM'
        $year = (int) substr($month, 0, 4);
        $m = (int) substr($month, 5, 2);

        $attendances = Attendance::with('user:id,name,employee_id')
            ->where('user_id', $request->user()->id)
            ->whereYear('date', $year)
            ->whereMonth('date', $m)
            ->orderBy('date', 'asc')
            ->orderBy('check_in', 'asc')
            ->get();

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=attendance_detailed_{$month}.csv",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function() use ($attendances) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Employee', 'Employee ID', 'Date', 'Check In', 'Check Out', 'Total Time', 'Status', 'Notes']);
            
            foreach ($attendances as $att) {
                fputcsv($file, [
                    $att->user->name ?? 'N/A',
                    $att->user->employee_id ?? 'N/A',
                    $att->date,
                    $att->check_in,
                    $att->check_out,
                    $att->total_time,
                    $att->status,
                    $att->notes
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
    
    public function bulkAction(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('attendance-delete'), 403);
        
        $validated = $request->validate([
            'action' => 'required|in:delete,force-delete',
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:attendances,id',
        ]);
        
        $ids = $validated['ids'];
        $action = $validated['action'];
        
        if ($action === 'delete') {
            Attendance::whereIn('id', $ids)->delete();
            return response()->json(['message' => 'Selected records temporarily deleted.']);
        }
        
        if ($action === 'force-delete') {
            Attendance::whereIn('id', $ids)->forceDelete();
            return response()->json(['message' => 'Selected records permanently deleted.']);
        }
        
        return response()->json(['message' => 'Invalid action.'], 400);
    }
}
