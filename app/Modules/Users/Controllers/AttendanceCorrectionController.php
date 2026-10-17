<?php

namespace App\Modules\Users\Controllers;

use App\Modules\Core\Controllers\Controller;
use App\Modules\Users\Models\Attendance;
use App\Modules\Users\Models\AttendanceCorrection;
use App\Modules\Users\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AttendanceCorrectionController extends Controller
{
    private function global(Request $request): bool
    {
        return $request->user()->hasRole(['Super Admin', 'Admin']) || $request->user()->can('view-all-data');
    }

    private function reviewerIds(Request $request)
    {
        return User::query()->select('id')->where('manager_id', $request->user()->id)
            ->where('id', '!=', $request->user()->id);
    }

    private function visibleQuery(Request $request)
    {
        $query = AttendanceCorrection::query();
        if (! $this->global($request)) {
            $teamIds = $request->user()->getLobTeamUserIds();
            $query->where(function ($scope) use ($request) {
                $scope->where('user_id', $request->user()->id);
                if ($request->user()->can('attendance-edit')) {
                    $scope->orWhereIn('user_id', $this->reviewerIds($request));
                }
            });
            if ($teamIds !== null) {
                $query->whereIn('user_id', array_unique([...$teamIds, $request->user()->id]));
            }
        }
        return $query;
    }

    private function canReview(Request $request, AttendanceCorrection $correction): bool
    {
        if (! $request->user()->can('attendance-edit')) return false;
        if ($this->global($request)) return true;
        return (int) $correction->user_id !== (int) $request->user()->id
            && $this->visibleQuery($request)->whereKey($correction->id)->exists();
    }

    private function snapshot(Attendance $attendance): array
    {
        return [
            'date' => $attendance->date->format('Y-m-d'),
            'check_in' => $attendance->check_in?->format('H:i:s'),
            'check_out' => $attendance->check_out?->format('H:i:s'),
            'status' => $attendance->status, 'notes' => $attendance->notes,
        ];
    }

    public function index(Request $request)
    {
        abort_unless($request->user()?->can('attendance-view'), 403);
        $request->validate(['status' => 'nullable|in:Pending,Approved,Rejected', 'scope' => 'nullable|in:mine,review']);
        $query = $this->visibleQuery($request)
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')))
            ->when($request->input('scope') === 'mine', fn ($query) => $query->where('user_id', $request->user()->id));
        if ($request->input('scope') === 'review') {
            abort_unless($request->user()->can('attendance-edit'), 403);
            if (! $this->global($request)) $query->whereIn('user_id', $this->reviewerIds($request));
        }
        $items = $query->with(['user:id,name,employee_id,manager_id', 'reviewer:id,name'])->latest('id')
            ->paginate(min(max($request->integer('per_page', 15), 1), 100));
        $items->getCollection()->each(fn ($item) => $item->setAttribute('can_review', $item->status === 'Pending' && $this->canReview($request, $item)));
        return response()->json($items);
    }

    public function store(Request $request)
    {
        abort_unless($request->user()?->can('attendance-view'), 403);
        $data = $request->validate([
            'attendance_id' => 'nullable|integer', 'date' => 'required|date_format:Y-m-d',
            'check_in_time' => 'nullable|date_format:Y-m-d\\TH:i',
            'check_out_time' => 'nullable|date_format:Y-m-d\\TH:i|after:check_in_time',
            'attendance_status' => 'required|in:Present,Absent,Half-Day,Late',
            'notes' => 'nullable|string|max:5000', 'reason' => 'required|string|max:5000',
        ]);
        if (! empty($data['check_in_time']) && substr($data['check_in_time'], 0, 10) !== $data['date']) {
            throw ValidationException::withMessages(['check_in_time' => 'Check-in must fall on the requested attendance date.']);
        }
        if (! empty($data['check_in_time']) && ! empty($data['check_out_time'])
            && Carbon::parse($data['check_in_time'])->diffInMinutes(Carbon::parse($data['check_out_time'])) >= 1440) {
            throw ValidationException::withMessages(['check_out_time' => 'A session must be shorter than 24 hours to match stored attendance times.']);
        }
        return DB::transaction(function () use ($request, $data) {
            // Lock the employee to serialize submissions even for missing attendance.
            User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            $attendance = empty($data['attendance_id']) ? null : Attendance::whereKey($data['attendance_id'])
                ->where('user_id', $request->user()->id)->lockForUpdate()->firstOrFail();
            if ($attendance && $attendance->date->format('Y-m-d') !== $data['date']) {
                throw ValidationException::withMessages(['date' => 'The date must match the attendance being corrected.']);
            }
            $duplicate = AttendanceCorrection::where('user_id', $request->user()->id)->where('status', 'Pending')
                ->when($attendance, fn ($query) => $query->where('attendance_id', $attendance->id),
                    fn ($query) => $query->whereNull('attendance_id')->where('date', $data['date']))->exists();
            if ($duplicate) throw ValidationException::withMessages(['attendance_id' => 'A pending correction already exists for this record or missing day.']);
            $item = AttendanceCorrection::create(array_merge($data, [
                'user_id' => $request->user()->id, 'status' => 'Pending',
                'original_values' => $attendance ? $this->snapshot($attendance) : null,
            ]));
            return response()->json(['data' => $item, 'message' => 'Correction submitted for approval. Attendance has not changed.'], 201);
        }, 3);
    }

    public function review(Request $request, AttendanceCorrection $correction)
    {
        abort_unless($request->user()?->can('attendance-edit'), 403);
        $data = $request->validate(['status' => 'required|in:Approved,Rejected', 'reviewer_comment' => 'nullable|string|max:5000']);
        return DB::transaction(function () use ($request, $correction, $data) {
            $item = AttendanceCorrection::whereKey($correction->id)->lockForUpdate()->firstOrFail();
            abort_unless($this->canReview($request, $item), 403);
            if ($item->status !== 'Pending') return response()->json(['message' => 'This correction has already been processed.'], 422);
            if ($data['status'] === 'Approved') {
                $attendance = null;
                if ($item->original_values !== null) {
                    $attendance = Attendance::whereKey($item->attendance_id)->lockForUpdate()->first();
                    $original = $item->original_values;
                    ksort($original);
                    $current = $attendance ? $this->snapshot($attendance) : [];
                    ksort($current);
                    if (! $attendance || (int) $attendance->user_id !== (int) $item->user_id || $current !== $original) {
                        return response()->json(['message' => 'Attendance changed after this request. Reject it and ask the employee to resubmit.'], 409);
                    }
                }
                $values = [
                    'user_id' => $item->user_id, 'date' => $item->date->format('Y-m-d'),
                    'check_in' => $item->check_in_time?->format('H:i:s'),
                    'check_out' => $item->check_out_time?->format('H:i:s'),
                    'status' => $item->attendance_status, 'notes' => $item->notes,
                ];
                if ($attendance) $attendance->update($values);
                else $attendance = Attendance::create($values);
                $item->attendance_id = $attendance->id;
            }
            $item->fill($data)->fill(['reviewed_by' => $request->user()->id, 'reviewed_at' => now()])->save();
            return response()->json(['data' => $item, 'message' => 'Correction '.strtolower($item->status).'.']);
        }, 3);
    }
}
