<?php

namespace App\Modules\Users\Controllers;

use App\Modules\Core\Controllers\Controller;
use App\Modules\Users\Models\Attendance;
use App\Modules\Users\Models\LeaveBalance;
use App\Modules\Users\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RosterController extends Controller
{
    private function global(Request $request): bool
    {
        return $request->user()->hasRole(['Super Admin', 'Admin']) || $request->user()->can('view-all-data');
    }

    private function users(Request $request)
    {
        $query = User::query();
        if (! $this->global($request)) {
            $query->whereIn('id', $request->user()->getLobTeamUserIds() ?? [$request->user()->id]);
        }

        return $query;
    }

    private function completed(int $user, string $date): bool
    {
        return Attendance::where('user_id', $user)->whereDate('date', $date)
            ->whereIn('status', ['Present', 'Late', 'Half-Day'])->whereNotNull('check_in')->whereNotNull('check_out')
            ->whereColumn('check_in', '!=', 'check_out')->lockForUpdate()->first() !== null;
    }

    private function event(Request $request, int $rosterId, string $action, array $details): void
    {
        DB::table('roster_events')->insert(['roster_id' => $rosterId, 'actor_id' => $request->user()->id, 'action' => $action, 'details' => json_encode($details, JSON_THROW_ON_ERROR), 'created_at' => now()]);
    }

    public function index(Request $request)
    {
        abort_unless($request->user()->can('attendance-view'), 403);
        $data = $request->validate(['start_date' => 'required|date_format:Y-m-d', 'end_date' => 'required|date_format:Y-m-d|after_or_equal:start_date', 'user_id' => 'nullable|integer']);
        $query = DB::table('employee_rosters')->whereIn('user_id', $this->users($request)->select('id'))
            ->whereBetween('date', [$data['start_date'], $data['end_date']]);
        $query->where('user_id', $data['user_id'] ?? $request->user()->id);

        return response()->json(['data' => $query->orderBy('date')->get()]);
    }

    public function import(Request $request)
    {
        abort_unless($request->user()->can('attendance-edit'), 403);
        $request->validate(['file' => 'required|file|max:2048', 'commit' => 'nullable|boolean', 'preview_hash' => 'nullable|string']);
        $file = $request->file('file');
        $hash = hash_file('sha256', $file->getRealPath());
        $handle = fopen($file->getRealPath(), 'r');
        $header = fgetcsv($handle);
        if ($header) {
            $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);
        }
        if (! in_array($header, [['employee_id', 'date', 'day_type'], ['user_id', 'date', 'day_type']], true)) {
            fclose($handle);
            throw ValidationException::withMessages(['file' => 'Expected CSV header: employee_id,date,day_type or user_id,date,day_type']);
        }
        $rows = [];
        $seen = [];
        $errors = [];
        $line = 1;
        while (($row = fgetcsv($handle)) !== false) {
            $line++;
            if ($row === [null]) {
                continue;
            }
            if (count($rows) + count($errors) >= 2000) {
                $errors[] = 'Maximum 2000 rows per import.';
                break;
            }
            if (count($row) !== 3) {
                $errors[] = "Row $line: expected 3 columns.";

                continue;
            }
            [$employee,$date,$type] = array_map('trim', $row);
            $users = $this->users($request)->where($header[0] === 'user_id' ? 'id' : 'employee_id', $header[0] === 'user_id' && ! ctype_digit($employee) ? -1 : $employee)->get(['id', 'name']);
            $parsed = \DateTimeImmutable::createFromFormat('!Y-m-d', $date);
            if ($employee === '' || $users->count() !== 1 || ! $parsed || $parsed->format('Y-m-d') !== $date || ! in_array($type, ['Working', 'Week Off'], true)) {
                $errors[] = "Row $line: invalid/unauthorized employee, date or day_type.";

                continue;
            }
            $key = $users[0]->id.':'.$date;
            if (isset($seen[$key])) {
                $errors[] = "Row $line: duplicate employee/date.";

                continue;
            }
            $seen[$key] = true;
            $existing = DB::table('employee_rosters')->where('user_id', $users[0]->id)->where('date', $date)->first();
            if ($existing && in_array($existing->co_status, ['Pending', 'Approved']) && $existing->day_type !== $type) {
                $errors[] = "Row $line: cannot change a day with Pending/Approved CO.";

                continue;
            }
            $rows[] = ['user_id' => $users[0]->id, 'employee_id' => $employee, 'name' => $users[0]->name, 'date' => $date, 'day_type' => $type, 'action' => $existing ? ($existing->day_type === $type ? 'Unchanged' : 'Update') : 'Create'];
        }
        fclose($handle);
        if (! $rows && ! $errors) {
            $errors[] = 'CSV contains no roster rows.';
        }
        if ($errors) {
            throw ValidationException::withMessages(['file' => $errors]);
        }
        if ($request->boolean('commit')) {
            abort_unless(hash_equals($hash, (string) $request->input('preview_hash')), 422, 'Preview this file before importing.');
            DB::transaction(function () use ($rows, $request) {
                $ids = array_unique(array_column($rows, 'user_id'));
                sort($ids);
                User::whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get();
                foreach ($rows as $row) {
                    $existing = DB::table('employee_rosters')->where('user_id', $row['user_id'])->where('date', $row['date'])->lockForUpdate()->first();
                    abort_if($existing && in_array($existing->co_status, ['Pending', 'Approved']) && $existing->day_type !== $row['day_type'], 409, 'Roster changed; preview again.');
                    DB::table('employee_rosters')->updateOrInsert(['user_id' => $row['user_id'], 'date' => $row['date']], ['day_type' => $row['day_type'], 'updated_at' => now(), ...(! $existing ? ['created_at' => now()] : [])]);
                    $rosterId = DB::table('employee_rosters')->where('user_id', $row['user_id'])->where('date', $row['date'])->value('id');
                    if (! $existing || $existing->day_type !== $row['day_type']) {
                        $this->event($request, $rosterId, 'Imported', ['previous' => $existing?->day_type, 'day_type' => $row['day_type']]);
                    }
                }
            });
        }

        return response()->json(['data' => $rows, 'preview_hash' => $hash, 'message' => $request->boolean('commit') ? 'Roster imported.' : 'Preview ready. Existing employee/date rows will be updated; other dates remain unchanged.']);
    }

    public function claims(Request $request)
    {
        abort_unless($request->user()->can('attendance-view'), 403);
        $request->validate(['page' => 'nullable|integer|min:1']);
        $query = DB::table('employee_rosters as r')->join('users as u', 'u.id', '=', 'r.user_id')->leftJoin('users as reviewer', 'reviewer.id', '=', 'r.reviewed_by')
            ->whereIn('r.user_id', $this->users($request)->select('id'))->whereNotNull('r.co_status');
        if (! $this->global($request)) {
            $query->where(function ($q) use ($request) {
                $q->where('r.user_id', $request->user()->id);
                if ($request->user()->can('attendance-edit')) {
                    $q->orWhere('u.manager_id', $request->user()->id);
                }
            });
        }
        $items = $query->select('r.*', 'u.name', 'u.employee_id', 'u.manager_id', 'reviewer.name as reviewer_name')->orderByDesc('r.id')->paginate(15);
        $events = DB::table('roster_events')->leftJoin('users as actor', 'actor.id', '=', 'roster_events.actor_id')->whereIn('roster_id', $items->getCollection()->pluck('id'))->select('roster_events.*', 'actor.name as actor_name')->orderBy('roster_events.id')->get()->groupBy('roster_id');
        $items->getCollection()->transform(function ($item) use ($request, $events) {
            $item->history = $events->get($item->id, collect());
            $item->can_review = $item->co_status === 'Pending' && $request->user()->can('attendance-edit') && ($this->global($request) || ((int) $item->manager_id === (int) $request->user()->id && (int) $item->user_id !== (int) $request->user()->id));

            return $item;
        });

        return response()->json($items);
    }

    public function claim(Request $request)
    {
        abort_unless($request->user()->can('attendance-view'), 403);
        $data = $request->validate(['date' => 'required|date_format:Y-m-d|before_or_equal:today', 'reason' => 'required|string|max:2000']);
        DB::transaction(function () use ($request, $data) {
            User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            $row = DB::table('employee_rosters')->where('user_id', $request->user()->id)->where('date', $data['date'])->lockForUpdate()->first();
            abort_unless($row && $row->day_type === 'Week Off', 422, 'This date must be a rostered Week Off.');
            abort_if(in_array($row->co_status, ['Pending', 'Approved']), 422, 'CO has already been requested or credited for this day.');
            abort_unless($this->completed($row->user_id, $row->date), 422, 'Completed, non-zero attendance is required on this day.');
            DB::table('employee_rosters')->where('id', $row->id)->update(['co_status' => 'Pending', 'co_reason' => $data['reason'], 'co_requested_at' => now(), 'reviewed_by' => null, 'reviewed_at' => null, 'review_comment' => null, 'updated_at' => now()]);
            $this->event($request, $row->id, 'Requested', ['reason' => $data['reason']]);
        });

        return response()->json(['message' => 'CO requested. Attendance and balance are unchanged pending approval.']);
    }

    public function review(Request $request, int $id)
    {
        abort_unless($request->user()->can('attendance-edit'), 403);
        $data = $request->validate(['status' => 'required|in:Approved,Rejected', 'comment' => 'nullable|string|max:2000']);
        DB::transaction(function () use ($request, $id, $data) {
            $row = DB::table('employee_rosters')->where('id', $id)->first();
            abort_unless($row, 404);
            $employee = $this->users($request)->whereKey($row->user_id)->lockForUpdate()->firstOrFail();
            abort_unless($this->global($request) || ((int) $employee->manager_id === (int) $request->user()->id && (int) $employee->id !== (int) $request->user()->id), 403);
            $row = DB::table('employee_rosters')->where('id', $id)->lockForUpdate()->first();
            abort_unless($row->co_status === 'Pending', 422, 'Request is already processed.');
            if ($data['status'] === 'Approved') {
                // Lock sessions so a concurrent correction cannot change the evidence mid-approval.
                Attendance::where('user_id', $row->user_id)->whereDate('date', $row->date)->lockForUpdate()->get();
                abort_unless($row->day_type === 'Week Off' && $this->completed($row->user_id, $row->date), 409, 'Completed attendance on a rostered Week Off is still required.');
                $balance = LeaveBalance::firstOrCreate(['user_id' => $row->user_id, 'leave_type' => 'CO'], ['total_leaves' => 0, 'used_leaves' => 0, 'is_active' => true]);
                $balance->increment('total_leaves', 1);
            }
            DB::table('employee_rosters')->where('id', $id)->update(['co_status' => $data['status'], 'reviewed_by' => $request->user()->id, 'reviewed_at' => now(), 'review_comment' => $data['comment'] ?? null, 'updated_at' => now()]);
            $this->event($request, $id, $data['status'], ['comment' => $data['comment'] ?? null, 'co_days' => $data['status'] === 'Approved' ? 1 : 0]);
        });

        return response()->json(['message' => $data['status'] === 'Approved' ? 'Approved: 1 CO credited.' : 'CO request rejected.']);
    }
}
