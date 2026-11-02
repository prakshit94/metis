<div x-data="rosterManager()" class="mt-0">
    <div class="alert alert-danger text-break" x-show="error" x-text="error" x-cloak role="alert"></div>
    <div class="alert alert-success" x-show="message" x-text="message" x-cloak role="status"></div>
    @can('attendance-roster-import')
    <section class="card mb-4" x-show="workspaceSection === 'roster'"><div class="card-body">
        <h2 class="h5">Employee roster import</h2>
        <details class="small mb-3">
            <summary class="text-primary">Format &amp; import rules</summary>
            <div class="mt-2 text-muted">
                <p class="mb-2">Use assigned employee IDs and one date per column. CSV and XLSX are supported.</p>
                <p class="mb-2"><strong>08:00-17:00</strong> Working · <strong>22:00-06:00</strong> Overnight · <strong>WO</strong> Week off · <strong>C/OFF</strong> Planned compensatory off · <strong>L</strong> Planned leave</p>
                <p class="mb-0">Blank dates stay unchanged. Pending or approved CO is protected. Imports do not create attendance or leave requests, or change balances.</p>
            </div>
        </details>
        <div class="border rounded p-3 mb-3">
            <h3 class="h6">Download template</h3>
            <p class="small text-muted">Edit and re-upload the XLSX roster. Exports include employees with assigned IDs; sample CSVs need actual employee details.</p>
            <div class="row g-3 align-items-end">
                <div class="col-md-3"><label for="rosterTemplateMode" class="form-label">Period</label><select id="rosterTemplateMode" class="form-select" x-model="templateMode"><option value="month">Entire month</option><option value="custom">Custom date range</option></select></div>
                <div class="col-md-3" x-show="templateMode === 'month'"><label for="rosterTemplateMonth" class="form-label">Month</label><input id="rosterTemplateMonth" type="month" class="form-control" x-model="templateMonth"></div>
                <div class="col-md-3" x-show="templateMode === 'custom'" x-cloak><label for="rosterTemplateStart" class="form-label">Start date</label><input id="rosterTemplateStart" type="date" class="form-control" x-model="templateStart"></div>
                <div class="col-md-3" x-show="templateMode === 'custom'" x-cloak><label for="rosterTemplateEnd" class="form-label">End date</label><input id="rosterTemplateEnd" type="date" class="form-control" x-model="templateEnd" :min="templateStart"></div>
                <div class="col-auto d-flex flex-wrap gap-2"><button type="button" class="btn btn-outline-primary" @click="matrixTemplate()">Sample template (CSV)</button>@can('attendance-export')<button type="button" class="btn btn-primary" @click="matrixTemplate(true)">Export editable roster (XLSX)</button>@endcan</div>
            </div>
        </div>
        <label class="form-label d-block" for="rosterFile">Upload roster (CSV / XLSX)</label>
        <input id="rosterFile" type="file" accept=".csv,.xlsx,text/csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" class="form-control mb-3" @change="choose($event)" :disabled="busy">
        <p class="small text-muted">Up to 2 MB · 2,000 employees · 10,000 entries</p>
        <div class="d-flex flex-wrap gap-2"><button type="button" class="btn btn-outline-primary" :disabled="busy || !file" @click="upload(false)">Preview roster</button><button type="button" class="btn btn-primary" :disabled="busy || !hash" @click="upload(true)">Confirm roster import</button></div>
        <div x-show="preview.length" x-cloak class="mt-4">
            <h3 class="h6">Roster preview</h3>
            <p class="small"><strong x-text="gridEmployees.length"></strong> employees · <span x-text="gridDates.length"></span> dates · <span x-text="preview.length"></span> entries · Blank cells stay unchanged.</p>
            <p class="small text-muted">Confirmation saves all preview pages.</p>
            <div class="table-responsive border rounded" style="max-height:440px">
                <table class="table table-sm table-bordered align-middle text-center mb-0" style="min-width:800px;white-space:nowrap">
                    <thead class="table-success" style="position:sticky;top:0;z-index:1"><tr><th scope="col">EMP. ID</th><th scope="col">Advisor Name</th><th scope="col">LOB</th><template x-for="date in gridDates" :key="date"><th scope="col"><span x-text="dateLabel(date)"></span><small class="d-block" x-text="dateLabel(date, true)"></small></th></template></tr></thead>
                    <tbody><template x-for="employee in visibleGrid" :key="employee.id"><tr><th scope="row" x-text="employee.employee_id"></th><td class="fw-semibold text-start" x-text="employee.name"></td><td x-text="employee.lob || '—'"></td><template x-for="date in gridDates" :key="date"><td :class="{'table-success fw-semibold': employee.days[date] &amp;&amp; employee.days[date].day_type !== 'Working'}" :title="employee.days[date] ? employee.days[date].action : 'Unchanged: blank or omitted date'" x-text="cellLabel(employee.days[date])"></td></template></tr></template></tbody>
                </table>
            </div>
            <div class="d-flex align-items-center flex-wrap gap-3 mt-2"><button type="button" class="btn btn-outline-secondary btn-sm" :disabled="previewPage <= 1" @click="previewPage--">Previous employees</button><span class="small">Preview page <span x-text="previewPage"></span> of <span x-text="gridPages"></span></span><button type="button" class="btn btn-outline-secondary btn-sm" :disabled="previewPage >= gridPages" @click="previewPage++">Next employees</button></div>
        </div>
    </div></section>
    @endcan
    <section class="card" x-show="workspaceSection === 'co'"><div class="card-body">
        <h2 class="h5">Compensatory Off (CO)</h2>
        <p class="small text-muted">Work on a rostered week off earns 1 CO after approval.</p>
        <details class="mb-4"><summary class="text-primary fw-medium">Request or resubmit CO</summary><form @submit.prevent="claim()" class="row g-3 align-items-end mb-4">
            <div class="col-md-3"><label class="form-label" for="coDate">Week-off date</label><input id="coDate" type="date" class="form-control" x-model="date" required></div>
            <div class="col-md-6"><label class="form-label" for="coReason">Reason / work performed</label><input id="coReason" class="form-control" x-model="reason" maxlength="2000" required></div>
            <div class="col-md-3"><button type="submit" class="btn btn-primary" :disabled="busy">Request 1 CO</button></div>
        </form></details>
        <button type="button" class="btn btn-outline-secondary btn-sm mb-3" @click="loadClaims()" :disabled="busy">Refresh CO requests</button>
        <div class="table-responsive"><table class="table align-middle"><thead><tr><th>Employee / date</th><th>Reason</th><th>Status / review</th><th>Actions</th></tr></thead><tbody>
            <tr x-show="!claims.length"><td colspan="4" class="text-muted">No CO requests found.</td></tr>
            <template x-for="item in claims" :key="item.id"><tr><td><strong x-text="item.name"></strong><small class="d-block" x-text="item.employee_id"></small><span x-text="item.date"></span><small class="d-block text-muted">Requested: <span x-text="item.co_requested_at || '—'"></span></small></td><td class="text-break" x-text="item.co_reason"></td><td><span class="badge bg-secondary" x-text="item.co_status"></span><small class="d-block" x-text="item.reviewer_name || ''"></small><small class="d-block" x-text="item.reviewed_at || ''"></small><small class="d-block text-break" x-text="item.review_comment || ''"></small><details class="small mt-1"><summary>Audit history</summary><template x-for="event in item.history || []" :key="event.id"><p class="mb-1"><span x-text="event.action + ' · ' + (event.actor_name || 'Deleted user') + ' · ' + event.created_at"></span><small class="d-block text-break" x-text="event.details"></small></p></template></details></td><td>@can('attendance-co-review')<div class="d-flex gap-2" x-show="item.can_review"><button type="button" class="btn btn-success btn-sm" @click="review(item,'Approved')" :disabled="busy">Approve 1 CO</button><button type="button" class="btn btn-outline-danger btn-sm" @click="review(item,'Rejected')" :disabled="busy">Reject</button></div>@endcan</td></tr></template>
        </tbody></table></div>
        <div class="d-flex gap-3 align-items-center"><button type="button" class="btn btn-outline-secondary btn-sm" :disabled="busy || page <= 1" @click="page--; loadClaims()">Previous</button><span>Page <span x-text="page"></span> of <span x-text="pages"></span></span><button type="button" class="btn btn-outline-secondary btn-sm" :disabled="busy || page >= pages" @click="page++; loadClaims()">Next</button></div>
    </div></section>
</div>
