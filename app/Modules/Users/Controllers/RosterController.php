<?php

namespace App\Modules\Users\Controllers;

use App\Modules\Core\Controllers\Controller;
use App\Modules\Users\Models\Attendance;
use App\Modules\Users\Models\LeaveBalance;
use App\Modules\Users\Models\User;
use App\Modules\Users\Services\RosterImport;
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

    public function export(Request $request)
    {
        abort_unless($request->user()?->can('attendance-export') && $request->user()?->can('attendance-view'), 403);
        $data = $request->validate([
            'start_date' => 'required|date_format:Y-m-d|after_or_equal:1000-01-01',
            'end_date' => 'required|date_format:Y-m-d|after_or_equal:start_date',
            'user_id' => 'nullable|integer',
        ]);
        $start = new \DateTimeImmutable($data['start_date']);
        $end = new \DateTimeImmutable($data['end_date']);
        if ($start->diff($end)->days > 365) {
            throw ValidationException::withMessages(['end_date' => 'Export up to 366 days at a time.']);
        }
        $query = $this->users($request)->when(isset($data['user_id']), fn ($q) => $q->whereKey($data['user_id']))
            ->whereNotNull('employee_id')->whereRaw("TRIM(employee_id) != ''");
        $employees = $query->orderBy('name')->orderBy('id')->limit(2001)->get(['id', 'name', 'employee_id']);
        if ($employees->isEmpty() || $employees->count() > 2000) {
            throw ValidationException::withMessages(['user_id' => 'Select 1–2,000 accessible employees with assigned employee IDs.']);
        }
        // Use the same identifier resolution as import; never export ambiguous IDs.
        foreach ($employees as $employee) {
            if ($this->users($request)->where('employee_id', trim($employee->employee_id))->count() !== 1) {
                throw ValidationException::withMessages(['user_id' => 'Employee IDs must be unique before exporting a reusable roster.']);
            }
        }
        $dates = [];
        for ($date = $start; $date <= $end; $date = $date->modify('+1 day')) $dates[] = $date->format('Y-m-d');
        $rosters = DB::table('employee_rosters')->whereIn('user_id', $employees->pluck('id'))
            ->whereBetween('date', [$data['start_date'], $data['end_date']])->get();
        if ($rosters->count() > 10000) {
            throw ValidationException::withMessages(['end_date' => 'Export up to 10,000 roster entries at a time. Select fewer employees or dates.']);
        }
        $byEmployee = $rosters->groupBy('user_id');
        $book = new \PhpOffice\PhpSpreadsheet\Spreadsheet;
        $sheet = $book->getActiveSheet();
        $sheet->setTitle('Employee roster');
        $headers = ['EMP. ID', 'Advisor Name', 'LOB', ...$dates];
        foreach ($headers as $column => $value) {
            $sheet->setCellValueExplicit([$column + 1, 1], $value, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        }
        foreach ($dates as $column => $date) $sheet->setCellValue([$column + 4, 2], (new \DateTimeImmutable($date))->format('l'));
        foreach ($employees as $index => $employee) {
            $row = $index + 3;
            $sheet->setCellValueExplicit([1, $row], $employee->employee_id, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValueExplicit([2, $row], $employee->name, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            foreach ($byEmployee->get($employee->id, collect()) as $roster) {
                $value = match ($roster->day_type) {
                    'Week Off' => 'WO', 'Comp Off' => 'C/OFF', 'Planned Leave' => 'L',
                    'Working' => $roster->shift_start && $roster->shift_end ? substr($roster->shift_start, 0, 5).'-'.substr($roster->shift_end, 0, 5) : 'Working',
                    default => null,
                };
                if ($value === null) throw ValidationException::withMessages(['file' => 'An existing roster value cannot be exported. Review its day type first.']);
                $column = array_search($roster->date, $dates, true) + 4;
                $sheet->setCellValueExplicit([$column, $row], $value, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            }
        }
        $lastColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers));
        $sheet->getStyle('A1:'.$lastColumn.'2')->getFill()->setFillType('solid')->getStartColor()->setARGB('FF548235');
        $sheet->getStyle('A1:'.$lastColumn.'2')->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
        $sheet->freezePane('D3');
        $sheet->getColumnDimension('A')->setWidth(16);
        $sheet->getColumnDimension('B')->setWidth(28);
        $sheet->getColumnDimension('C')->setWidth(16);
        for ($column = 4; $column <= count($headers); $column++) $sheet->getColumnDimension(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($column))->setWidth(18);
        $path = tempnam(sys_get_temp_dir(), 'roster-export-');
        try {
            (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($book))->save($path);
            if (filesize($path) > 2 * 1024 * 1024) throw ValidationException::withMessages(['end_date' => 'Export exceeds the 2 MB import limit. Select fewer employees or dates.']);
            $contents = file_get_contents($path);
        } finally {
            $book->disconnectWorksheets();
            if (is_file($path)) unlink($path);
        }
        return response($contents, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="editable-roster-'.$data['start_date'].'-to-'.$data['end_date'].'.xlsx"',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function import(Request $request)
    {
        abort_unless($request->user()->can('attendance-roster-import'), 403);
        $request->validate(['file' => 'required|file|max:2048', 'commit' => 'nullable|boolean', 'preview_hash' => 'nullable|string']);
        $file = $request->file('file');
        $hash = hash_file('sha256', $file->getRealPath());
        $parser = app(RosterImport::class);
        $entries = $parser->parse($file);
        $rows = [];
        $seen = [];
        $errors = [];
        $employees = [];
        foreach ($entries as $entry) {
            $line = $entry['line'];
            $employee = $entry['identifier'];
            $cacheKey = $entry['identifier_field'].':'.$employee;
            if (! array_key_exists($cacheKey, $employees)) {
                $employees[$cacheKey] = $employee === '' || ($entry['identifier_field'] === 'id' && ! ctype_digit($employee))
                    ? collect() : $this->users($request)->where($entry['identifier_field'], $employee)->get(['id', 'name']);
            }
            $users = $employees[$cacheKey];
            if ($users->count() !== 1) {
                $errors[] = "Row $line: employee '$employee' is unknown, ambiguous or outside your access.";

                continue;
            }
            $date = $entry['date'];
            $type = $entry['day_type'];
            $key = $users[0]->id.':'.$date;
            if (isset($seen[$key])) {
                $errors[] = "Row $line: duplicate employee/date $date.";

                continue;
            }
            $seen[$key] = true;
            $existing = DB::table('employee_rosters')->where('user_id', $users[0]->id)->where('date', $date)->first();
            if ($existing && in_array($existing->co_status, ['Pending', 'Approved']) && $existing->day_type !== $type) {
                $errors[] = "Row $line: cannot change a day with Pending/Approved CO.";

                continue;
            }
            // Legacy day-only imports preserve any existing shift on a Working day.
            $start = $entry['shift_start'];
            $end = $entry['shift_end'];
            if ($type === 'Working' && $start === null) {
                $start = isset($existing->shift_start) ? substr($existing->shift_start, 0, 5) : null;
                $end = isset($existing->shift_end) ? substr($existing->shift_end, 0, 5) : null;
            }
            $unchanged = $existing && $existing->day_type === $type
                && ($existing->shift_start ? substr($existing->shift_start, 0, 5) : null) === $start
                && ($existing->shift_end ? substr($existing->shift_end, 0, 5) : null) === $end;
            $rows[] = ['user_id' => $users[0]->id, 'employee_id' => $employee, 'name' => $users[0]->name, 'lob' => $entry['lob'],
                'date' => $date, 'day_type' => $type, 'shift_start' => $start, 'shift_end' => $end,
                'action' => ! $existing ? 'Create' : ($unchanged ? 'Unchanged' : 'Update'),
                'version' => $existing ? hash('sha256', json_encode($existing)) : null];
        }
        if ($errors) {
            throw ValidationException::withMessages(['file' => array_slice($errors, 0, 50)]);
        }
        // Bind confirmation to the actor, parsed data and current stored rows, not just file bytes.
        $hash = hash_hmac('sha256', $hash.':'.$request->user()->id.':'.json_encode($rows), config('app.key'));
        if ($request->boolean('commit')) {
            abort_unless(hash_equals($hash, (string) $request->input('preview_hash')), 422, 'Preview this file before importing.');
            DB::transaction(function () use ($rows, $request) {
                $ids = array_unique(array_column($rows, 'user_id'));
                sort($ids);
                User::whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get();
                foreach ($rows as $row) {
                    $existing = DB::table('employee_rosters')->where('user_id', $row['user_id'])->where('date', $row['date'])->lockForUpdate()->first();
                    abort_if($existing && in_array($existing->co_status, ['Pending', 'Approved']) && $existing->day_type !== $row['day_type'], 409, 'Roster changed; preview again.');
                    abort_unless(($existing ? hash('sha256', json_encode($existing)) : null) === $row['version'], 409, 'Roster changed; preview again.');
                    if ($row['action'] === 'Unchanged') {
                        continue;
                    }
                    DB::table('employee_rosters')->updateOrInsert(['user_id' => $row['user_id'], 'date' => $row['date']], ['day_type' => $row['day_type'], 'shift_start' => $row['shift_start'], 'shift_end' => $row['shift_end'], 'updated_at' => now(), ...(! $existing ? ['created_at' => now()] : [])]);
                    $rosterId = DB::table('employee_rosters')->where('user_id', $row['user_id'])->where('date', $row['date'])->value('id');
                    if ($row['action'] !== 'Unchanged') {
                        $this->event($request, $rosterId, 'Imported', ['previous' => $existing, 'day_type' => $row['day_type'], 'shift_start' => $row['shift_start'], 'shift_end' => $row['shift_end']]);
                    }
                }
            });
        }

        return response()->json(['data' => $rows, 'dates' => $parser->dates ?: array_values(array_unique(array_column($rows, 'date'))), 'preview_hash' => $hash, 'message' => $request->boolean('commit') ? 'Roster imported.' : 'Preview ready. Blank and omitted dates stay unchanged. Planned leave does not create requests or change balances.']);
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
                if ($request->user()->can('attendance-co-review')) {
                    $q->orWhere('u.manager_id', $request->user()->id);
                }
            });
        }
        $items = $query->select('r.*', 'u.name', 'u.employee_id', 'u.manager_id', 'reviewer.name as reviewer_name')->orderByDesc('r.id')->paginate(15);
        $events = DB::table('roster_events')->leftJoin('users as actor', 'actor.id', '=', 'roster_events.actor_id')->whereIn('roster_id', $items->getCollection()->pluck('id'))->select('roster_events.*', 'actor.name as actor_name')->orderBy('roster_events.id')->get()->groupBy('roster_id');
        $items->getCollection()->transform(function ($item) use ($request, $events) {
            $item->history = $events->get($item->id, collect());
            $item->can_review = $item->co_status === 'Pending' && $request->user()->can('attendance-co-review') && ($this->global($request) || ((int) $item->manager_id === (int) $request->user()->id && (int) $item->user_id !== (int) $request->user()->id));

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
        abort_unless($request->user()->can('attendance-co-review'), 403);
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
