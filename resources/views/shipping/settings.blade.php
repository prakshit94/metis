@extends('layouts.app')

@section('title', 'Shipping Settings')
@section('page', 'shipping-settings')

@section('content')
<div class="shipping-settings" x-data="shippingSettings" x-cloak>
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 mb-lg-5">
        <div>
            <h1 class="h3 mb-0">Shipping Settings</h1>
            <p class="text-body-secondary mb-0">Manage API credentials and provider configurations</p>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8 col-xl-6">
            <!-- India Post Settings Card -->
            <div class="card border border-secondary-subtle shadow-sm mb-4 bg-body-tertiary">
                <div class="card-header bg-body border-bottom border-secondary-subtle py-3">
                    <div class="d-flex align-items-center">
                        <div class="bg-danger bg-opacity-10 text-danger rounded p-2 me-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                            <i class="bi bi-mailbox"></i>
                        </div>
                        <h2 class="h5 card-title mb-0 fw-bold">India Post API Credentials</h2>
                    </div>
                </div>
                <div class="card-body p-4 bg-body">
                    <form @submit.prevent="saveSettings">
                        <div class="mb-4">
                            <label class="form-label fw-semibold text-body-secondary small">Base URL <span class="text-danger">*</span></label>
                            <input type="url" class="form-control" x-model="form.india_post_base_url" placeholder="https://test.cept.gov.in/beextcustomer" required>
                            <div class="form-text small">The API endpoint for India Post. Use the sandbox URL for testing.</div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-body-secondary small">Username <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" x-model="form.india_post_username" placeholder="API Username" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-body-secondary small">Password</label>
                                <div class="input-group">
                                    <input :type="showPassword ? 'text' : 'password'" class="form-control" x-model="form.india_post_password" placeholder="Leave blank to keep unchanged">
                                    <button class="btn btn-outline-secondary" type="button" @click="showPassword = !showPassword">
                                        <i class="bi" :class="showPassword ? 'bi-eye-slash' : 'bi-eye'"></i>
                                    </button>
                                </div>
                                <div class="form-text small">Only fill this if you want to update the password.</div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold text-body-secondary small">Bulk Customer ID <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" x-model="form.india_post_bulk_customer_id" placeholder="e.g. 3000064781" required>
                        </div>

                        <h6 class="fw-bold mb-3 border-bottom border-secondary-subtle pb-2">Contract Details</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-body-secondary small">Speed Post Doc Contract ID</label>
                                <input type="text" class="form-control" x-model="form.india_post_contract_sp_doc">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-body-secondary small">Speed Post Parcel Contract ID</label>
                                <input type="text" class="form-control" x-model="form.india_post_contract_sp_parcel">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-body-secondary small">Business Parcel Contract ID</label>
                                <input type="text" class="form-control" x-model="form.india_post_contract_bp">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-body-secondary small">24 SpeedPost Doc Contract ID</label>
                                <input type="text" class="form-control" x-model="form.india_post_contract_24_sp_doc">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-body-secondary small">24 SPP Parcel Contract ID</label>
                                <input type="text" class="form-control" x-model="form.india_post_contract_24_spp_parspl">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-body-secondary small">48 SpeedPost Doc Contract ID</label>
                                <input type="text" class="form-control" x-model="form.india_post_contract_48_sp_doc">
                            </div>
                        </div>

                        <div class="d-flex justify-content-end border-top border-secondary-subtle pt-3 mt-4">
                            <button type="submit" class="btn btn-primary px-4" :disabled="saving">
                                <span x-show="saving" class="spinner-border spinner-border-sm me-2" role="status"></span>
                                <i class="bi bi-floppy me-2" x-show="!saving"></i> Save Configuration
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Alpine JS Logic -->
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('shippingSettings', () => ({
        form: {
            india_post_base_url: '{{ $settings['india_post_base_url'] ?? config('shipping.providers.india_post.base_url') }}',
            india_post_username: '{{ $settings['india_post_username'] ?? config('shipping.providers.india_post.username') }}',
            india_post_password: '',
            india_post_bulk_customer_id: '{{ $settings['india_post_bulk_customer_id'] ?? config('shipping.providers.india_post.bulk_customer_id') }}',
            india_post_contract_sp_doc: '{{ $settings['india_post_contract_sp_doc'] ?? config('shipping.providers.india_post.contracts.SP_INLAND_DOC') }}',
            india_post_contract_sp_parcel: '{{ $settings['india_post_contract_sp_parcel'] ?? config('shipping.providers.india_post.contracts.SP_INLAND_PARCEL') }}',
            india_post_contract_bp: '{{ $settings['india_post_contract_bp'] ?? config('shipping.providers.india_post.contracts.BUSINESS_PARCEL') }}',
            india_post_contract_24_sp_doc: '{{ $settings['india_post_contract_24_sp_doc'] ?? config('shipping.providers.india_post.contracts.24_SPEEDPOST_DOC') }}',
            india_post_contract_24_spp_parspl: '{{ $settings['india_post_contract_24_spp_parspl'] ?? config('shipping.providers.india_post.contracts.24_SPP_PARSPL') }}',
            india_post_contract_48_sp_doc: '{{ $settings['india_post_contract_48_sp_doc'] ?? config('shipping.providers.india_post.contracts.48_SPEEDPOST_DOC') }}',
        },
        showPassword: false,
        saving: false,

        async saveSettings() {
            this.saving = true;
            try {
                const response = await fetch('/api/shipping/settings', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(this.form)
                });
                
                const data = await response.json();
                
                if (!response.ok) {
                    throw new Error(data.message || data.error || 'Failed to save settings.');
                }
                
                // Show success toast
                this.showToast(data.message || 'Settings updated successfully.', 'success');
                this.form.india_post_password = ''; // Clear password field for security
                
            } catch (error) {
                this.showToast(error.message, 'error');
            } finally {
                this.saving = false;
            }
        },
        
        showToast(message, type = 'success') {
            const container = document.getElementById('toast-container');
            if (!container) return;
            
            const iconMap = {
                success: 'bi-check-circle-fill',
                error: 'bi-x-circle-fill',
                warning: 'bi-exclamation-triangle-fill'
            };
            
            const toast = document.createElement('div');
            toast.className = `toast align-items-center text-bg-${type === 'error' ? 'danger' : type} border-0 show mb-2`;
            toast.setAttribute('role', 'alert');
            toast.innerHTML = `
                <div class="d-flex">
                    <div class="toast-body">
                        <i class="bi ${iconMap[type] || 'bi-info-circle-fill'} me-2"></i>
                        <span>${message}</span>
                    </div>
                    <button type="button" class="btn-close btn-close me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>`;
                
            container.appendChild(toast);
            setTimeout(() => toast.remove(), 4000);
        }
    }));
});
</script>
@endsection
