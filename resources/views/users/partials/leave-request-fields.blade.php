            <div class="modal-body p-4">
                <p class="small text-muted mb-4">Choose the employee and leave type, then enter the requested dates and reason.</p>
                <div class="alert alert-danger" role="alert" x-show="error" x-text="error" style="display: none;"></div>

                <div class="small text-muted mb-3" role="status" x-show="isLoadingBalances"><span class="spinner-border spinner-border-sm me-2"></span>Loading available leave types…</div>
                <div class="row g-3 mb-4">
                <div class="col-md-6">
<label class="form-label" for="leaveUser">Employee <span class="text-danger">*</span></label>
<x-searchable-select model="form.user_id" label="Leave employee">
<select x-ref="native" aria-hidden="true" tabindex="-1" @focus="$refs.trigger.focus()" @invalid.prevent="validationMessage = $event.target.validationMessage; $refs.trigger.focus()" class="visually-hidden" id="leaveUser" x-model="form.user_id" required @disabled(!$isGlobalHr)>
                        <option value="">Select Employee</option>
                        <template x-for="user in users" :key="user.id">
                            <option :value="user.id" :selected="String(user.id) === String(form.user_id)" x-text="user.name"></option>
                        </template>
                    </select>
</x-searchable-select>
</div>

                <div class="col-md-6">
<label class="form-label" for="leaveType">Leave Type <span class="text-danger">*</span></label>
<x-searchable-select model="form.leave_type" label="Leave type">
<select x-ref="native" aria-hidden="true" tabindex="-1" @focus="$refs.trigger.focus()" @invalid.prevent="validationMessage = $event.target.validationMessage; $refs.trigger.focus()" class="visually-hidden" id="leaveType" x-model="form.leave_type" required :disabled="isLoadingBalances || userBalances.length === 0">
                        <option value="">Select Leave Type</option>
                        <template x-for="balance in userBalances" :key="balance.id">
                            <option :value="balance.leave_type"
                                    x-text="`${balance.leave_type} Leave (Balance: ${balance.balance})`"
                                    :disabled="balance.balance <= 0 && balance.leave_type !== originalLeaveType"></option>
                        </template>
                    </select>
</x-searchable-select>
                    <div class="form-text text-warning" x-show="form.user_id && userBalances.length === 0 && !isLoadingBalances">
                        No active leave balances found for this employee.
                    </div>
                </div>

                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <div>
                            <label class="form-label fw-semibold" for="leaveStart">Start date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="leaveStart" x-model="form.start_date" required>

</div>
                    </div>
                    <div class="col-md-6">
                        <div>
                            <label class="form-label fw-semibold" for="leaveEnd">End date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="leaveEnd" x-model="form.end_date" :min="form.start_date" required>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold" for="leaveReason">Reason <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="leaveReason" x-model="form.reason" rows="3" placeholder="Explain the reason for your leave request" required></textarea>
                </div>

                @if($isGlobalHr && auth()->user()->can('leave-approve'))
                <div class="mb-3" x-show="editingId" x-cloak>
<label class="form-label" for="leaveStatus">Status</label>
<x-searchable-select model="form.status" label="Leave status">
<select x-ref="native" aria-hidden="true" tabindex="-1" @focus="$refs.trigger.focus()" @invalid.prevent="validationMessage = $event.target.validationMessage; $refs.trigger.focus()" class="visually-hidden" id="leaveStatus" x-model="form.status">
                        <option value="Pending">Pending</option>
                        <option value="Approved">Approved</option>
                        <option value="Rejected">Rejected</option>
                    </select>
</x-searchable-select>
</div>
                @endif
            </div>
