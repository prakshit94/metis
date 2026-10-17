<div x-data="attendanceCorrections({{ auth()->id() }}, @js(auth()->user()->can('attendance-edit')))">
<section class="card mt-4">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div><h2 class="h5 mb-1">Attendance correction requests</h2><p class="small text-muted mb-0">Pending requests leave attendance unchanged. Your assigned manager or HR/Admin reviews them.</p></div>
        <button type="button" class="btn btn-primary btn-sm" @click="openForm()">Request missing attendance</button>
    </div>
    <div class="card-body">
        <div class="alert alert-danger" x-show="error" x-text="error" x-cloak role="alert"></div>
        <div class="d-flex flex-wrap gap-3 mb-3 align-items-end">
            <div><label class="form-label small">Inbox</label><x-searchable-select model="scope" label="Correction inbox" small>
                <select x-ref="native" class="visually-hidden" tabindex="-1" aria-hidden="true" x-model="scope" @change="filterItems()"><option value="mine">My requests</option>@can('attendance-edit')<option value="review">Requests I can review</option>@endcan</select>
            </x-searchable-select></div>
            <div><label class="form-label small">Status</label><x-searchable-select model="statusFilter" label="Correction status filter" small>
                <select x-ref="native" class="visually-hidden" tabindex="-1" aria-hidden="true" x-model="statusFilter" @change="filterItems()"><option value="">All statuses</option><option>Pending</option><option>Approved</option><option>Rejected</option></select>
            </x-searchable-select></div>
            <button type="button" class="btn btn-outline-secondary btn-sm" :disabled="loading" @click="loadItems()">Refresh corrections</button>
        </div>
        <div class="table-responsive"><table class="table table-hover align-middle"><thead><tr><th>Employee / date</th><th>Proposed attendance</th><th>Reason and original record</th><th>Decision</th><th>Review</th></tr></thead><tbody>
            <tr x-show="loading"><td colspan="5" class="text-muted">Loading corrections…</td></tr>
            <tr x-show="!loading && items.length === 0"><td colspan="5" class="text-muted">No correction requests found.</td></tr>
            <template x-for="item in items" :key="item.id"><tr x-show="!loading">
                <td><strong x-text="item.user?.name || '—'"></strong><small class="d-block text-muted" x-text="item.user?.employee_id || ''"></small><span x-text="item.date"></span></td>
                <td><span class="badge bg-secondary" x-text="item.attendance_status"></span><small class="d-block">In: <span x-text="item.check_in_time || '—'"></span></small><small class="d-block">Out: <span x-text="item.check_out_time || '—'"></span></small><small class="d-block text-break" x-text="item.notes || ''"></small></td>
                <td><span class="text-break" style="white-space: pre-wrap;" x-text="item.reason"></span><details class="small mt-1"><summary>Original values</summary><p x-text="item.original_values ? [item.original_values.date, item.original_values.check_in || '—', item.original_values.check_out || '—', item.original_values.status, item.original_values.notes].filter(Boolean).join(' · ') : 'Missing attendance (new record requested)'"></p></details></td>
                <td><span class="badge" :class="item.status === 'Approved' ? 'bg-success' : item.status === 'Rejected' ? 'bg-danger' : 'bg-warning text-dark'" x-text="item.status"></span><small class="d-block" x-text="item.reviewer?.name || ''"></small><small class="d-block" x-text="item.reviewed_at ? new Date(item.reviewed_at).toLocaleString() : ''"></small><small class="text-break" x-text="item.reviewer_comment || ''"></small></td>
                <td><div class="d-flex gap-1" x-show="item.can_review"><button type="button" class="btn btn-success btn-sm" :disabled="saving" @click="review(item, 'Approved')">Approve</button><button type="button" class="btn btn-outline-danger btn-sm" :disabled="saving" @click="review(item, 'Rejected')">Reject</button></div><span class="small text-muted" x-show="!item.can_review">Read only</span></td>
            </tr></template>
        </tbody></table></div>
        <div class="d-flex gap-3 justify-content-between align-items-center"><span class="small text-muted"><span x-text="total"></span> requests · Page <span x-text="currentPage"></span> of <span x-text="totalPages"></span></span><div class="btn-group"><button type="button" class="btn btn-outline-secondary btn-sm" :disabled="currentPage <= 1 || loading" @click="page(-1)">Previous corrections</button><button type="button" class="btn btn-outline-secondary btn-sm" :disabled="currentPage >= totalPages || loading" @click="page(1)">Next corrections</button></div></div>
    </div>
</section>
    <template x-teleport="body"><div class="modal fade" id="correctionModal" tabindex="-1" aria-labelledby="correctionTitle"><div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg"><form class="modal-content" @submit.prevent="submit()">
        <div class="modal-header bg-body-tertiary"><h5 class="modal-title" id="correctionTitle">Request attendance correction</h5><button type="button" class="btn-close" @click="closeForm()" aria-label="Close"></button></div>
        <div class="modal-body p-4">
            <div class="alert alert-danger" role="alert" x-show="formError" x-text="formError" x-cloak></div>
            <p class="small text-muted">This requests a change to your own attendance. Approval is required.</p>
            <label class="form-label" for="correctionDate">Attendance date</label><input id="correctionDate" type="date" class="form-control mb-3" x-model="form.date" :readonly="Boolean(form.attendance_id)" required>
            <div class="row g-3"><div class="col-md-6">            <label class="form-label" for="correctionIn">Proposed check-in</label><input id="correctionIn" type="datetime-local" class="form-control mb-3" x-model="form.check_in_time">
</div><div class="col-md-6">            <label class="form-label" for="correctionOut">Proposed check-out</label><input id="correctionOut" type="datetime-local" class="form-control mb-3" x-model="form.check_out_time">
</div></div>
            <label class="form-label">Proposed status</label><x-searchable-select model="form.attendance_status" label="Proposed attendance status"><select x-ref="native" class="visually-hidden" tabindex="-1" aria-hidden="true" x-model="form.attendance_status" required><option>Present</option><option>Absent</option><option>Late</option><option>Half-Day</option></select></x-searchable-select>
            <label class="form-label mt-3" for="correctionNotes">Proposed attendance notes</label><textarea id="correctionNotes" class="form-control mb-3" rows="3" x-model="form.notes" maxlength="5000"></textarea>
            <label class="form-label" for="correctionReason">Reason for correction</label><textarea id="correctionReason" class="form-control" rows="3" x-model="form.reason" required maxlength="5000"></textarea>
        </div><div class="modal-footer bg-body-tertiary"><button type="button" class="btn btn-outline-secondary" @click="closeForm()">Cancel</button><button type="submit" class="btn btn-primary" :disabled="saving" x-text="saving ? 'Submitting…' : 'Submit correction'"></button></div>
    </form></div></div></template>
</div>
