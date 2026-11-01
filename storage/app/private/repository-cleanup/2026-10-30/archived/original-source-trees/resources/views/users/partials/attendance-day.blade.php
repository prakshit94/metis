<div class="modal fade" id="attendanceDayModal" tabindex="-1" aria-labelledby="attendanceDayTitle">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg"><div class="modal-content">
        <div class="modal-header bg-body-tertiary"><h5 class="modal-title" id="attendanceDayTitle">Day details · <span x-text="selectedDay?.date"></span></h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
        <div class="modal-body p-4">
            <p class="text-muted">Every recorded session is listed separately. Open sessions do not contribute to completed time.</p>
            <div class="table-responsive"><table class="table table-sm"><thead><tr><th>Employee</th><th>In</th><th>Out</th><th>Time</th><th>Status</th><th>Notes / action</th></tr></thead><tbody>
                <template x-for="session in daySessions" :key="session.id"><tr>
                    <td x-text="session.user?.name || '—'"></td><td x-text="session.check_in || '—'"></td><td x-text="session.check_out || 'Open'"></td><td x-text="session.total_time || '—'"></td><td x-text="session.status"></td>
                    <td><span class="small text-break" x-text="session.notes || '—'"></span><button type="button" class="btn btn-link btn-sm p-0 d-block" x-show="String(session.user_id) === '{{ auth()->id() }}'" @click="requestCorrection(session)">Request correction</button></td>
                </tr></template>
                <tr x-show="daySessions.length === 0"><td colspan="6" class="text-muted">No recorded sessions for this day.</td></tr>
            </tbody></table></div>
            <h6>Leave requests</h6><template x-for="leave in dayLeaves" :key="leave.id"><p class="small mb-1"><span x-text="leave.leave_type"></span> · <span x-text="leave.status"></span> · <span x-text="leave.reason || '—'"></span></p></template>
            <p class="small text-muted" x-show="dayLeaves.length === 0">No leave requests in the current results.</p>
            <template x-for="holiday in selectedDay?.holidays || []" :key="holiday.id"><p class="small text-primary mb-1"><span x-text="holiday.name"></span> · <span x-text="holiday.type"></span></p></template>
        </div><div class="modal-footer bg-body-tertiary"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button></div>
    </div></div>
</div>
