<div x-data="rosterManager()" class="mt-0">
    <div class="alert alert-danger text-break" x-show="error" x-text="error" x-cloak role="alert"></div>
    <div class="alert alert-success" x-show="message" x-text="message" x-cloak role="status"></div>
    @can('attendance-roster-import')
    <section class="card mb-4" x-show="workspaceSection === 'roster'"><div class="card-body">
        <h2 class="h5">Employee roster CSV import</h2>
        <p class="small text-muted">Import Working / Week Off dates for multiple employees using employee IDs, or use the user_id header with Sys ID from Users when employee IDs are unassigned. Preview before saving. Existing employee/date rows are updated; omitted dates remain unchanged. Days with Pending or Approved CO cannot be changed.</p>
        <button type="button" class="btn btn-outline-secondary btn-sm mb-3" @click="template()">Download employee-ID template</button>
        <button type="button" class="btn btn-outline-secondary btn-sm mb-3" @click="template(true)">Download system-ID template</button>
        <label class="form-label d-block" for="rosterFile">Roster CSV (maximum 2 MB / 2,000 rows)</label>
        <input id="rosterFile" type="file" accept=".csv,text/csv" class="form-control mb-3" @change="choose($event)" :disabled="busy">
        <div class="d-flex gap-2"><button type="button" class="btn btn-outline-primary" :disabled="busy || !file" @click="upload(false)">Preview roster</button><button type="button" class="btn btn-primary" :disabled="busy || !hash" @click="upload(true)">Confirm roster import</button></div>
        <div x-show="preview.length" x-cloak class="mt-3"><p><span x-text="preview.length"></span> validated rows</p><div class="table-responsive" style="max-height:300px"><table class="table table-sm"><thead><tr><th>Employee identifier</th><th>Name</th><th>Date</th><th>Roster</th><th>Action</th></tr></thead><tbody><template x-for="row in preview" :key="row.user_id + ':' + row.date"><tr><td x-text="row.employee_id"></td><td x-text="row.name"></td><td x-text="row.date"></td><td x-text="row.day_type"></td><td x-text="row.action"></td></tr></template></tbody></table></div></div>
    </div></section>
    @endcan
    <section class="card" x-show="workspaceSection === 'co'"><div class="card-body">
        <h2 class="h5">Compensatory Off (CO)</h2>
        <p class="small text-muted">Your requests and requests you can review appear here. The attendance employee filter applies to the Attendance section.</p>
        <p class="small text-muted">Completed check-in and check-out on a rostered Week Off automatically requests 1 CO. Your direct manager or HR/Admin approves it before your balance changes. One credit per employee/day; no minimum hours or expiry. Attendance and existing leave rules stay unchanged.</p>
        <details class="mb-4"><summary class="text-primary fw-medium">Request CO for an older record or resubmit a rejected request</summary><form @submit.prevent="claim()" class="row g-3 align-items-end mb-4">
            <div class="col-md-3"><label class="form-label" for="coDate">Week-off date</label><input id="coDate" type="date" class="form-control" x-model="date" required></div>
            <div class="col-md-6"><label class="form-label" for="coReason">Reason / work performed</label><input id="coReason" class="form-control" x-model="reason" maxlength="2000" required></div>
            <div class="col-md-3"><button type="submit" class="btn btn-primary" :disabled="busy">Request 1 CO</button></div>
        </form></details>
        <button type="button" class="btn btn-outline-secondary btn-sm mb-3" @click="loadClaims()" :disabled="busy">Refresh CO requests</button>
        <div class="table-responsive"><table class="table align-middle"><thead><tr><th>Employee / date</th><th>Reason</th><th>Status / review</th><th>Actions</th></tr></thead><tbody>
            <tr x-show="!claims.length"><td colspan="4" class="text-muted">No CO requests found.</td></tr>
            <template x-for="item in claims" :key="item.id"><tr><td><strong x-text="item.name"></strong><small class="d-block" x-text="item.employee_id"></small><span x-text="item.date"></span><small class="d-block text-muted">Requested: <span x-text="item.co_requested_at || '—'"></span></small></td><td class="text-break" x-text="item.co_reason"></td><td><span class="badge bg-secondary" x-text="item.co_status"></span><small class="d-block" x-text="item.reviewer_name || ''"></small><small class="d-block" x-text="item.reviewed_at || ''"></small><small class="d-block text-break" x-text="item.review_comment || ''"></small><details class="small mt-1"><summary>Audit history</summary><template x-for="event in item.history || []" :key="event.id"><p class="mb-1"><span x-text="event.action + ' · ' + (event.actor_name || 'Deleted user') + ' · ' + event.created_at"></span><small class="d-block text-break" x-text="event.details"></small></p></template></details></td><td>@can('attendance-co-review')<div class="d-flex gap-2" x-show="item.can_review"><button type="button" class="btn btn-success btn-sm" @click="review(item,'Approved')" :disabled="busy">Approve 1 CO</button><button type="button" class="btn btn-outline-danger btn-sm" @click="review(item,'Rejected')" :disabled="busy">Reject</button></div>@endcan</td></tr></template>
        </tbody></table></div>
        <div class="d-flex gap-3 align-items-center"><button type="button" class="btn btn-outline-secondary btn-sm" :disabled="busy || page <= 1" @click="page--; loadClaims()">Previous CO requests</button><span>Page <span x-text="page"></span> of <span x-text="pages"></span></span><button type="button" class="btn btn-outline-secondary btn-sm" :disabled="busy || page >= pages" @click="page++; loadClaims()">Next CO requests</button></div>
    </div></section>
</div>
