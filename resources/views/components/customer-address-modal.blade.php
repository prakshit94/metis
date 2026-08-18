<div class="modal fade" id="customerAddressModal" aria-labelledby="customerAddressModalLabel" aria-hidden="true" 
     x-data="customerAddressApp()" 
     @open-address-modal.window="openModal($event.detail)">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            {{-- GLOSSY STYLE HEADER WITH BOOTSTRAP --}}
            <div class="modal-header bg-body-tertiary border-bottom d-flex align-items-center justify-content-between p-4">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-3 d-flex align-items-center justify-content-center shadow-sm" style="width: 48px; height: 48px;">
                        <i class="bi bi-geo-alt-fill fs-4"></i>
                    </div>
                    <div>
                        <h4 class="mb-0 fw-bold text-body"><span x-text="isEdit ? 'Edit Address' : 'Add New Address'"></span></h4>
                        <p class="mb-0 small text-muted">Manage your customer address details</p>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body p-4 bg-body-tertiary">
                <form @submit.prevent="submitForm" autocomplete="off">
                    <div class="card border border-secondary border-opacity-25 shadow-sm rounded-4 bg-body-secondary mb-3" style="z-index: 30;">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center gap-2 pb-2 mb-3 border-bottom">
                                <div class="bg-primary bg-opacity-10 text-primary rounded-2 d-flex align-items-center justify-content-center" style="width: 24px; height: 24px;">
                                    <i class="bi bi-geo fs-6"></i>
                                </div>
                                <h6 class="mb-0 fw-bold text-uppercase text-body" style="font-size: 11px; letter-spacing: 1px;">Address Details</h6>
                            </div>
                            
                            <div class="row g-3">
                                <div class="col-sm-6">
                                    <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">Address Label *</label>
                                    <select x-select name="label" class="form-select form-select-sm fw-semibold" style="font-size: 12px;" x-model="form.label" required>
                                        <option value="Home">Home</option>
                                        <option value="Shop">Shop</option>
                                        <option value="Office">Office</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">Status</label>
                                    <select x-select name="status" class="form-select form-select-sm fw-semibold" style="font-size: 12px;" x-model="form.status">
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                </div>

                                <div class="col-sm-6">
                                    <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">Address Line 1 *</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-body text-muted border-end-0"><i class="bi bi-house"></i></span>
                                        <input type="text" name="address_line_1" class="form-control border-start-0 ps-0 fw-semibold" style="font-size: 12px;" x-model="form.address_line_1" required>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">Address Line 2</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-body text-muted border-end-0"><i class="bi bi-signpost"></i></span>
                                        <input type="text" name="address_line_2" class="form-control border-start-0 ps-0 fw-semibold" style="font-size: 12px;" x-model="form.address_line_2">
                                    </div>
                                </div>

                                <div class="col-12">
                                    <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">Village Search</label>
                                    <div class="position-relative">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text bg-body text-muted border-end-0"><i class="bi bi-search"></i></span>
                                            <input type="text" class="form-control border-start-0 ps-0 fw-semibold" style="font-size: 12px;" placeholder="Type 3 letters to search village..." 
                                                   x-model="villageSearchQuery" @input.debounce.300ms="searchVillages()">
                                        </div>
                                        <div class="position-absolute w-100 dropdown-menu show shadow overflow-auto" style="max-height: 200px; z-index: 1060;" x-show="villageResults.length > 0">
                                            <template x-for="v in villageResults" :key="v.id">
                                                <button type="button" class="dropdown-item w-100 text-start py-2 px-3 border-bottom border-light-subtle"
                                                        @click="selectVillage(v)">
                                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                                        <span class="fw-bold text-primary" style="font-size: 12px;" x-text="v.village_name"></span>
                                                        <span class="badge bg-secondary-subtle text-secondary-emphasis" x-text="v.pincode"></span>
                                                    </div>
                                                    <div class="text-muted small" style="font-size: 0.75rem; line-height: 1.4;">
                                                        <span x-show="v.post_so_name" x-text="'PO: ' + v.post_so_name + ' · '"></span>
                                                        <span x-show="v.taluka_name" x-text="'Taluka: ' + v.taluka_name + ' · '"></span>
                                                        <span x-show="v.district_name" x-text="'District: ' + v.district_name + ' · '"></span>
                                                        <span x-show="v.state_name" x-text="'State: ' + v.state_name"></span>
                                                    </div>
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                </div>

                                <!-- Selected Village Details -->
                                <template x-if="form.village_name">
                                    <div class="col-12">
                                        <div class="card bg-body border-0 border-start border-4 border-primary shadow-sm mt-2">
                                            <div class="card-body p-3">
                                                <div class="row g-2">
                                                    <div class="col-md-4">
                                                        <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">Village</label>
                                                        <div class="fw-semibold text-truncate text-body" style="font-size: 12px;" x-text="form.village_name || '—'"></div>
                                                        <input type="hidden" name="village_name" :value="form.village_name">
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">Post Office</label>
                                                        <div class="text-truncate text-body" style="font-size: 12px;" x-text="form.post_office || '—'"></div>
                                                        <input type="hidden" name="post_office" :value="form.post_office">
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">Taluka</label>
                                                        <div class="text-truncate text-body" style="font-size: 12px;" x-text="form.taluka || '—'"></div>
                                                        <input type="hidden" name="taluka" :value="form.taluka">
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">District</label>
                                                        <div class="text-truncate text-body" style="font-size: 12px;" x-text="form.district || '—'"></div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">State</label>
                                                        <div class="text-truncate text-body" style="font-size: 12px;" x-text="form.state || '—'"></div>
                                                        <input type="hidden" name="state" :value="form.state">
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">Pincode</label>
                                                        <div class="fw-bold text-body" style="font-size: 12px;" x-text="form.pincode || '—'"></div>
                                                        <input type="hidden" name="pincode" :value="form.pincode">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <input type="hidden" name="village_id" x-model="form.village_id">
                                        <input type="hidden" name="city" x-model="form.city">
                                    </div>
                                </template>

                                <!-- Manual Input Fields (no village selected) -->
                                <template x-if="!form.village_name">
                                    <div class="col-12 mt-2">
                                        <div class="row g-2">
                                            <div class="col-md-4">
                                                <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">City *</label>
                                                <input type="text" name="city" class="form-control form-control-sm fw-semibold" style="font-size: 12px;" x-model="form.city" required>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">State *</label>
                                                <input type="text" name="state" class="form-control form-control-sm fw-semibold" style="font-size: 12px;" x-model="form.state" required>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">Pincode *</label>
                                                <input type="text" name="pincode" class="form-control form-control-sm fw-semibold" style="font-size: 12px;" x-model="form.pincode" required>
                                            </div>
                                        </div>
                                    </div>
                                </template>

                                <div class="col-12 mt-3">
                                    <div class="form-check form-switch cursor-pointer">
                                        <input class="form-check-input" type="checkbox" name="is_default" value="1" id="is_default" x-model="form.is_default" style="cursor: pointer;">
                                        <label class="form-check-label fw-bold text-primary text-uppercase" for="is_default" style="font-size: 9px; letter-spacing: 0.1em; cursor: pointer;">Set as default address</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-end gap-3 mt-4 pt-3 border-top">
                        <button type="button" class="btn text-muted fw-bold text-uppercase" style="font-size: 11px; letter-spacing: 1px;" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm" :disabled="isSubmitting">
                            <span x-show="isSubmitting" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                            <span x-text="isEdit ? 'Save Changes' : 'Add Address'"></span>
                        </button>
                    </div>
                    <div class="alert alert-danger mt-3 mb-0 py-2 small fw-bold" x-show="formError" x-text="formError" x-cloak></div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('customerAddressApp', () => ({
        isEdit: false,
        customerId: null,
        addressId: null,
        isSubmitting: false,
        formError: '',
        villageSearchQuery: '',
        villageResults: [],
        form: {
            label: 'Home',
            status: 'active',
            address_line_1: '',
            address_line_2: '',
            village_id: '',
            village_name: '',
            post_office: '',
            taluka: '',
            district: '',
            city: '',
            state: '',
            pincode: '',
            is_default: false
        },
        
        resetForm() {
            this.villageSearchQuery = '';
            this.villageResults = [];
            this.form = {
                label: 'Home',
                status: 'active',
                address_line_1: '',
                address_line_2: '',
                village_id: '',
                village_name: '',
                post_office: '',
                taluka: '',
                district: '',
                city: '',
                state: '',
                pincode: '',
                is_default: false
            };
        },
        
        async searchVillages() {
            if (!this.villageSearchQuery || this.villageSearchQuery.length < 3) {
                this.villageResults = [];
                return;
            }
            try {
                const res = await fetch(`/api/villages/search?q=${encodeURIComponent(this.villageSearchQuery)}`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await res.json();
                this.villageResults = data.data || [];
            } catch (e) {
                console.error('Village search failed:', e);
            }
        },

        selectVillage(v) {
            this.form.village_id = v.id;
            this.form.village_name = v.village_name || v.name || '';
            this.form.post_office = v.post_so_name || v.post_office || '';
            this.form.taluka = v.taluka_name || v.taluka || '';
            this.form.district = v.district_name || v.district || '';
            this.form.city = v.district_name || v.district || v.city || '';
            this.form.state = v.state_name || v.state || '';
            this.form.pincode = v.pincode || '';
            
            this.villageSearchQuery = '';
            this.villageResults = [];
        },

        openModal(detail) {
            this.customerId = detail.customerId;
            if (detail.address) {
                this.isEdit = true;
                this.addressId = detail.address.id;
                this.form = { ...detail.address, is_default: Boolean(detail.address.is_default) };
                if (detail.address.village) {
                    this.form.village_name = detail.address.village.village_name || '';
                    this.form.post_office = detail.address.village.post_so_name || '';
                    this.form.taluka = detail.address.village.taluka_name || '';
                    this.form.district = detail.address.village.district_name || '';
                    this.form.state = detail.address.village.state_name || '';
                }
            } else {
                this.isEdit = false;
                this.addressId = null;
                this.resetForm();
            }
            this.formError = '';
            
            bootstrap.Modal.getOrCreateInstance(document.getElementById('customerAddressModal')).show();
        },

        async submitForm(e) {
            this.formError = '';
            this.isSubmitting = true;
            
            const formData = new FormData(e.target);
            const url = this.isEdit 
                ? `/api/customers/${this.customerId}/addresses/${this.addressId}` 
                : `/api/customers/${this.customerId}/addresses`;
                
            if (this.isEdit) {
                formData.append('_method', 'PUT');
            }

            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: formData
                });
                const data = await response.json();
                
                if (!response.ok) {
                    if (data.errors) {
                        this.formError = Object.values(data.errors).flat().join(', ');
                    } else {
                        this.formError = data.message || 'An error occurred during submission.';
                    }
                    this.isSubmitting = false;
                    return;
                }
                
                this.isSubmitting = false;
                const modal = bootstrap.Modal.getInstance(document.getElementById('customerAddressModal'));
                if (modal) modal.hide();
                
                // Dispatch event to refresh customer data
                window.dispatchEvent(new CustomEvent('customer-updated'));
                
                // Show success notification
                if (window.dispatchEvent) {
                    window.dispatchEvent(new CustomEvent('notify', { detail: { type: 'success', message: data.message }}));
                }
            } catch (err) {
                this.formError = 'Network error occurred. Please try again.';
                this.isSubmitting = false;
            }
        }
    }));
});
</script>
@endpush
