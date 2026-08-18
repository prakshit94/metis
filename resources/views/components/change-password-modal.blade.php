<!-- Change Password Modal -->
<div class="modal fade" id="changePasswordModal" aria-labelledby="changePasswordModalLabel" aria-hidden="true" x-data="changePasswordModal()">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-body-secondary border-bottom-0 py-3 px-4">
                <h5 class="modal-title fw-bold text-body" id="changePasswordModalLabel">
                    <i class="bi bi-key text-primary me-2"></i>Change Password
                </h5>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form @submit.prevent="submitForm" id="changePasswordForm">
                <div class="modal-body p-4">
                    <!-- Error Message -->
                    <template x-if="errorMessage">
                        <div class="alert alert-danger d-flex align-items-center mb-4 border-0 shadow-sm" role="alert">
                            <i class="bi bi-exclamation-triangle fs-5 me-3"></i>
                            <div x-text="errorMessage" class="fw-semibold small"></div>
                        </div>
                    </template>
                    
                    <!-- Success Message -->
                    <template x-if="successMessage">
                        <div class="alert alert-success d-flex align-items-center mb-4 border-0 shadow-sm" role="alert">
                            <i class="bi bi-check-circle fs-5 me-3"></i>
                            <div x-text="successMessage" class="fw-semibold small"></div>
                        </div>
                    </template>

                    <div class="mb-3">
                        <label for="new_password" class="form-label fw-semibold small text-muted">New Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-body-secondary border-end-0 text-muted"><i class="bi bi-lock"></i></span>
                            <input :type="showPassword ? 'text' : 'password'" 
                                   class="form-control bg-body border-start-0 border-end-0 px-0 shadow-none" 
                                   id="new_password" 
                                   x-model="formData.password" 
                                   :class="{'is-invalid': errors.password}"
                                   required>
                            <button type="button" class="btn btn-outline-secondary border-start-0 shadow-none px-3" tabindex="-1" @click="showPassword = !showPassword">
                                <i :class="showPassword ? 'bi bi-eye-slash' : 'bi bi-eye'"></i>
                            </button>
                        </div>
                        <template x-if="errors.password">
                            <div class="text-danger small mt-1 fw-medium" x-text="errors.password[0]"></div>
                        </template>
                        <div class="form-text small mt-2">Password must be at least 8 characters long and contain mixed case letters and numbers.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password_confirmation" class="form-label fw-semibold small text-muted">Confirm New Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-body-secondary border-end-0 text-muted"><i class="bi bi-lock"></i></span>
                            <input :type="showPasswordConfirmation ? 'text' : 'password'" 
                                   class="form-control bg-body border-start-0 border-end-0 px-0 shadow-none" 
                                   id="password_confirmation" 
                                   x-model="formData.password_confirmation" 
                                   :class="{'is-invalid': errors.password_confirmation}"
                                   required>
                            <button type="button" class="btn btn-outline-secondary border-start-0 shadow-none px-3" tabindex="-1" @click="showPasswordConfirmation = !showPasswordConfirmation">
                                <i :class="showPasswordConfirmation ? 'bi bi-eye-slash' : 'bi bi-eye'"></i>
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer bg-body-secondary border-top-0 py-3 px-4 d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-light border-0 fw-semibold shadow-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold shadow-sm d-flex align-items-center gap-2" :disabled="isLoading">
                        <span x-show="!isLoading">Update Password</span>
                        <span x-show="isLoading" x-cloak>
                            <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                            Updating...
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('changePasswordModal', () => ({
        userId: '{{ Auth::id() }}',
        defaultUserId: '{{ Auth::id() }}',
        isLoading: false,
        errorMessage: '',
        successMessage: '',
        showPassword: false,
        showPasswordConfirmation: false,
        formData: {
            password: '',
            password_confirmation: ''
        },
        errors: {},
        
        init() {
            const modalEl = document.getElementById('changePasswordModal');
            if(modalEl) {
                modalEl.addEventListener('hidden.bs.modal', () => {
                    this.resetForm();
                    this.userId = this.defaultUserId; // Reset back to logged-in user when closed
                });
            }
            
            window.addEventListener('open-change-password-modal', (e) => {
                this.userId = e.detail.userId;
                this.resetForm();
                bootstrap.Modal.getOrCreateInstance(document.getElementById('changePasswordModal')).show();
            });
        },
        
        resetForm() {
            this.formData.password = '';
            this.formData.password_confirmation = '';
            this.errors = {};
            this.errorMessage = '';
            this.successMessage = '';
            this.showPassword = false;
            this.showPasswordConfirmation = false;
            this.isLoading = false;
        },
        
        async submitForm() {
            this.isLoading = true;
            this.errorMessage = '';
            this.successMessage = '';
            this.errors = {};
            
            try {
                const response = await fetch(`/api/users/${this.userId}`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify(this.formData)
                });
                
                const data = await response.json();
                
                if (!response.ok) {
                    if (response.status === 422) {
                        this.errors = data.errors || {};
                        if (!this.errors.password && data.message) {
                            this.errorMessage = data.message;
                        }
                    } else {
                        this.errorMessage = data.message || 'An error occurred while updating the password.';
                    }
                } else {
                    this.successMessage = 'Password updated successfully!';
                    
                    setTimeout(() => {
                        const modalEl = document.getElementById('changePasswordModal');
                        if (modalEl) {
                            const modal = bootstrap.Modal.getInstance(modalEl);
                            if (modal) modal.hide();
                        }
                    }, 1500);
                }
            } catch (error) {
                this.errorMessage = 'A network error occurred. Please try again.';
                console.error(error);
            } finally {
                this.isLoading = false;
            }
        }
    }));
});
</script>
