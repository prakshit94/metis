<div class="modal fade" id="addCustomerModal" tabindex="-1" aria-labelledby="addCustomerModalLabel" aria-hidden="true" 
     x-data="addCustomerApp()" 
     @open-add-customer-modal.window="openModal($event.detail)">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            
            {{-- GLOSSY STYLE HEADER WITH BOOTSTRAP --}}
            <div class="modal-header bg-body-tertiary border-bottom d-flex align-items-center justify-content-between p-4">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-3 d-flex align-items-center justify-content-center shadow-sm" style="width: 48px; height: 48px;">
                        <i class="bi bi-person-plus-fill fs-4"></i>
                    </div>
                    <div>
                        <h4 class="mb-0 fw-bold text-body"><span x-text="isEdit ? 'Edit Customer Profile' : 'Add New Customer'"></span></h4>
                        <p class="mb-0 small text-muted">Register a new customer profile</p>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body p-4 bg-body-tertiary">
                <form id="addCustomerForm" @submit.prevent="submitForm" autocomplete="off">
                    <div class="row g-4">
                        {{-- LEFT COLUMN --}}
                        <div class="col-lg-6">
                            
                            {{-- Basic Identity --}}
                            <div class="card mb-3 border border-secondary border-opacity-25 shadow-sm rounded-4 bg-body-secondary" style="z-index: 30;">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center gap-2 pb-2 mb-3 border-bottom">
                                        <div class="bg-primary bg-opacity-10 text-primary rounded-2 d-flex align-items-center justify-content-center" style="width: 24px; height: 24px;">
                                            <i class="bi bi-person fs-6"></i>
                                        </div>
                                        <h6 class="mb-0 fw-bold text-uppercase text-body" style="font-size: 11px; letter-spacing: 1px;">Basic Identity</h6>
                                    </div>
                                    <div class="row g-2">
                                        <div class="col-sm-4">
                                            <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">First Name *</label>
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text bg-body text-muted border-end-0"><i class="bi bi-person"></i></span>
                                                <input type="text" name="firstname" x-model="form.firstname" class="form-control border-start-0 ps-0 fw-semibold" style="font-size: 12px;" required>
                                            </div>
                                        </div>
                                        <div class="col-sm-4">
                                            <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">Middle Name</label>
                                            <input type="text" name="middlename" x-model="form.middlename" class="form-control form-control-sm fw-semibold" style="font-size: 12px;">
                                        </div>
                                        <div class="col-sm-4">
                                            <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">Last Name *</label>
                                            <input type="text" name="lastname" x-model="form.lastname" class="form-control form-control-sm fw-semibold" style="font-size: 12px;" required>
                                        </div>
                                        <div class="col-sm-4">
                                            <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">Status *</label>
                                            <select name="status" x-model="form.status" class="form-select form-select-sm fw-semibold" style="font-size: 12px;">
                                                <option value="active">Active</option>
                                                <option value="inactive">Inactive</option>
                                                <option value="suspended">Suspended</option>
                                            </select>
                                        </div>
                                        <div class="col-sm-4">
                                            <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">Category</label>
                                            <select name="category" x-model="form.category" class="form-select form-select-sm fw-semibold" style="font-size: 12px;">
                                                <option value="">— Select —</option>
                                                <option value="individual">Individual</option>
                                                <option value="business">Business</option>
                                            </select>
                                        </div>
                                        <div class="col-sm-4 position-relative" @click.away="showSourceDropdown = false">
                                            <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">Lead Source</label>
                                            <div @click="showSourceDropdown = !showSourceDropdown" class="form-control form-control-sm d-flex flex-wrap align-items-center gap-1 cursor-pointer bg-body" style="min-height: 31px; cursor: pointer;">
                                                <template x-if="selectedSources.length === 0">
                                                    <span class="text-muted" style="font-size: 12px;">Select...</span>
                                                </template>
                                                <template x-for="source in selectedSources" :key="source">
                                                    <div class="badge bg-primary bg-opacity-10 text-primary d-flex align-items-center gap-1">
                                                        <span x-text="source" style="font-size: 10px;"></span>
                                                        <i class="bi bi-x cursor-pointer" @click.stop="toggleSource(source)" style="font-size: 12px;"></i>
                                                        <input type="hidden" name="source[]" :value="source">
                                                    </div>
                                                </template>
                                            </div>
                                            <div x-show="showSourceDropdown" class="position-absolute w-100 bg-body border rounded shadow-lg mt-1" style="max-height: 150px; overflow-y: auto; z-index: 1050;">
                                                <template x-for="source in sources" :key="source">
                                                    <div class="px-3 py-1 cursor-pointer custom-hover-bg d-flex align-items-center" @click.stop="toggleSource(source)">
                                                        <input type="checkbox" :checked="selectedSources.includes(source)" class="me-2" style="cursor: pointer;">
                                                        <span style="font-size: 12px;" x-text="source"></span>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            {{-- Contact Channels --}}
                            <div class="card mb-3 border border-secondary border-opacity-25 shadow-sm rounded-4 bg-body-secondary" style="z-index: 20;">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center gap-2 pb-2 mb-3 border-bottom">
                                        <div class="bg-indigo text-indigo bg-opacity-10 rounded-2 d-flex align-items-center justify-content-center" style="width: 24px; height: 24px; color: #6610f2;">
                                            <i class="bi bi-telephone fs-6"></i>
                                        </div>
                                        <h6 class="mb-0 fw-bold text-uppercase text-body" style="font-size: 11px; letter-spacing: 1px;">Contact Channels</h6>
                                    </div>
                                    <div class="row g-2">
                                        <div class="col-sm-4">
                                            <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">Email Address</label>
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text bg-body text-muted border-end-0"><i class="bi bi-envelope"></i></span>
                                                <input type="email" name="email" x-model="form.email" class="form-control border-start-0 ps-0 fw-semibold" style="font-size: 12px;">
                                            </div>
                                        </div>
                                        <div class="col-sm-4">
                                            <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">Primary Phone *</label>
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text bg-body text-muted border-end-0"><i class="bi bi-phone"></i></span>
                                                <input type="text" name="phone" x-model="form.phone" class="form-control border-start-0 ps-0 fw-semibold" style="font-size: 12px;" required pattern="\d{10}" minlength="10" maxlength="10" title="Must be exactly 10 digits" oninput="this.value = this.value.replace(/\D/g, '').substring(0,10)">
                                            </div>
                                        </div>
                                        <div class="col-sm-4">
                                            <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">Alternate Mobile</label>
                                            <input type="text" name="alternatemobile" x-model="form.alternatemobile" class="form-control form-control-sm fw-semibold" style="font-size: 12px;" pattern="\d{10}" minlength="10" maxlength="10" title="Must be exactly 10 digits" oninput="this.value = this.value.replace(/\D/g, '').substring(0,10)">
                                        </div>
                                        <div class="col-sm-4">
                                            <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">Relative Name</label>
                                            <input type="text" name="relative_name" x-model="form.relative_name" class="form-control form-control-sm fw-semibold" style="font-size: 12px;">
                                        </div>
                                        <div class="col-sm-4">
                                            <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">Relative Phone</label>
                                            <input type="text" name="relative_phone" x-model="form.relative_phone" class="form-control form-control-sm fw-semibold" style="font-size: 12px;" pattern="\d{10}" minlength="10" maxlength="10" title="Must be exactly 10 digits" oninput="this.value = this.value.replace(/\D/g, '').substring(0,10)">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            {{-- Internal Notes --}}
                            <div class="card border border-secondary border-opacity-25 shadow-sm rounded-4 bg-body-secondary mb-3 mb-lg-0" style="z-index: 10;">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center gap-2 pb-2 mb-3 border-bottom">
                                        <div class="bg-secondary bg-opacity-10 text-secondary rounded-2 d-flex align-items-center justify-content-center" style="width: 24px; height: 24px;">
                                            <i class="bi bi-file-text fs-6"></i>
                                        </div>
                                        <h6 class="mb-0 fw-bold text-uppercase text-body" style="font-size: 11px; letter-spacing: 1px;">Internal Notes</h6>
                                    </div>
                                    <textarea name="internal_notes" x-model="form.internal_notes" rows="1" class="form-control form-control-sm fw-semibold" style="font-size: 12px;" placeholder="Administrative notes..."></textarea>
                                </div>
                            </div>

                        </div>

                        {{-- RIGHT COLUMN --}}
                        <div class="col-lg-6">
                            
                            {{-- Business & Compliance --}}
                            <div class="card mb-3 border border-secondary border-opacity-25 shadow-sm rounded-4 bg-body-secondary" style="z-index: 30;" x-show="form.category === 'business'" x-transition x-cloak>
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center gap-2 pb-2 mb-3 border-bottom">
                                        <div class="bg-success bg-opacity-10 text-success rounded-2 d-flex align-items-center justify-content-center" style="width: 24px; height: 24px;">
                                            <i class="bi bi-briefcase fs-6"></i>
                                        </div>
                                        <h6 class="mb-0 fw-bold text-uppercase text-body" style="font-size: 11px; letter-spacing: 1px;">Business & Compliance</h6>
                                    </div>
                                    <div class="row g-2">
                                        <div class="col-12">
                                            <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">Company Name</label>
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text bg-body text-muted border-end-0"><i class="bi bi-building"></i></span>
                                                <input type="text" name="company_name" x-model="form.company_name" class="form-control border-start-0 ps-0 fw-semibold" style="font-size: 12px;">
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">GST Number</label>
                                            <input type="text" name="gst_no" x-model="form.gst_no" class="form-control form-control-sm text-uppercase fw-semibold font-monospace" style="font-size: 12px;">
                                        </div>
                                        <div class="col-sm-6">
                                            <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">PAN Number</label>
                                            <input type="text" name="pan_no" x-model="form.pan_no" class="form-control form-control-sm text-uppercase fw-semibold font-monospace" style="font-size: 12px;">
                                        </div>
                                        <div class="col-sm-6">
                                            <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">Tax No (Other)</label>
                                            <input type="text" name="tax_no" x-model="form.tax_no" class="form-control form-control-sm text-uppercase fw-semibold font-monospace" style="font-size: 12px;">
                                        </div>
                                        <div class="col-sm-6">
                                            <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">Aadhaar (Last 4)</label>
                                            <input type="text" name="aadhaar_last4" x-model="form.aadhaar_last4" class="form-control form-control-sm fw-semibold font-monospace" style="font-size: 12px; letter-spacing: 2px;" maxlength="4">
                                        </div>
                                        <div class="col-12 mt-2">
                                            <div class="d-flex gap-4 pt-2">
                                                <input type="hidden" name="kyc_completed" value="0">
                                                <div class="form-check form-switch cursor-pointer">
                                                    <input class="form-check-input" type="checkbox" name="kyc_completed" value="1" id="kycCheck" x-model="form.kyc_completed">
                                                    <label class="form-check-label fw-bold text-muted text-uppercase" for="kycCheck" style="font-size: 9px; letter-spacing: 0.1em; cursor: pointer;">KYC Verified</label>
                                                </div>
                                                <input type="hidden" name="is_blacklisted" value="0">
                                                <div class="form-check form-switch cursor-pointer">
                                                    <input class="form-check-input" type="checkbox" name="is_blacklisted" value="1" id="blacklistCheck" x-model="form.is_blacklisted">
                                                    <label class="form-check-label fw-bold text-danger text-uppercase" for="blacklistCheck" style="font-size: 9px; letter-spacing: 0.1em; cursor: pointer;">Blacklisted</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            {{-- Agriculture Profile --}}
                            <div class="card mb-3 border border-secondary border-opacity-25 shadow-sm rounded-4 bg-body-secondary" style="z-index: 20;">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center gap-2 pb-2 mb-3 border-bottom">
                                        <div class="bg-warning bg-opacity-10 text-warning rounded-2 d-flex align-items-center justify-content-center" style="width: 24px; height: 24px; color: #ffc107;">
                                            <i class="bi bi-brightness-high fs-6"></i>
                                        </div>
                                        <h6 class="mb-0 fw-bold text-uppercase text-body" style="font-size: 11px; letter-spacing: 1px;">Agriculture Profile</h6>
                                    </div>
                                    <div class="row g-2">
                                        <div class="col-sm-6">
                                            <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">Land Area & Unit</label>
                                            <div class="input-group input-group-sm">
                                                <input type="number" step="0.01" name="land_area" x-model="form.land_area" class="form-control w-50 fw-semibold" style="font-size: 12px;">
                                                <select name="land_unit" x-model="form.land_unit" class="form-select w-50 fw-semibold" style="font-size: 12px;">
                                                    <template x-for="unit in landUnits" :key="unit">
                                                        <option :value="unit" x-text="unit"></option>
                                                    </template>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-sm-6 position-relative" @click.away="showIrrigationDropdown = false">
                                            <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">Irrigation</label>
                                            <div @click="showIrrigationDropdown = !showIrrigationDropdown" class="form-control form-control-sm d-flex flex-wrap align-items-center gap-1 cursor-pointer bg-body" style="min-height: 31px; cursor: pointer;">
                                                <template x-if="selectedIrrigation.length === 0">
                                                    <span class="text-muted" style="font-size: 12px;">Select...</span>
                                                </template>
                                                <template x-for="type in selectedIrrigation" :key="type">
                                                    <div class="badge bg-warning bg-opacity-10 text-warning d-flex align-items-center gap-1" style="color: #d39e00 !important;">
                                                        <span x-text="type" style="font-size: 10px;"></span>
                                                        <i class="bi bi-x cursor-pointer" @click.stop="toggleIrrigation(type)" style="font-size: 12px;"></i>
                                                        <input type="hidden" name="irrigation_type[]" :value="type">
                                                    </div>
                                                </template>
                                            </div>
                                            <div x-show="showIrrigationDropdown" class="position-absolute w-100 bg-body border rounded shadow-lg mt-1" style="max-height: 150px; overflow-y: auto; z-index: 1050;">
                                                <template x-for="type in irrigationTypes" :key="type">
                                                    <div class="px-3 py-1 cursor-pointer custom-hover-bg d-flex align-items-center" @click.stop="toggleIrrigation(type)">
                                                        <input type="checkbox" :checked="selectedIrrigation.includes(type)" class="me-2" style="cursor: pointer;">
                                                        <span style="font-size: 12px;" x-text="type"></span>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                        <div class="col-12 position-relative" @click.away="showCropsDropdown = false">
                                            <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">Cultivated Major Crops</label>
                                            <div class="form-control form-control-sm d-flex flex-wrap align-items-center gap-1 bg-body" style="min-height: 31px; cursor: text;" @click="showCropsDropdown = true; $refs.cropSearch.focus()">
                                                <template x-for="crop in selectedCrops" :key="crop">
                                                    <div class="badge bg-success bg-opacity-10 text-success d-flex align-items-center gap-1">
                                                        <span x-text="crop" style="font-size: 10px;"></span>
                                                        <i class="bi bi-x cursor-pointer" @click.stop="toggleCrop(crop)" style="font-size: 12px;"></i>
                                                        <input type="hidden" name="crops[]" :value="crop">
                                                    </div>
                                                </template>
                                                <div class="flex-grow-1 position-relative" style="min-width: 100px;">
                                                    <input x-ref="cropSearch" type="text" x-model="cropSearch" @focus="showCropsDropdown = true" placeholder="Search..." class="border-0 w-100 outline-none" style="font-size: 12px; outline: none !important; box-shadow: none;">
                                                </div>
                                            </div>
                                            <div x-show="showCropsDropdown && filteredCrops.length > 0" class="position-absolute w-100 bg-body border rounded shadow-lg mt-1" style="max-height: 150px; overflow-y: auto; z-index: 1050;">
                                                <template x-for="crop in filteredCrops" :key="crop">
                                                    <div class="px-3 py-1 cursor-pointer custom-hover-bg d-flex align-items-center" @click.stop="toggleCrop(crop)">
                                                        <input type="checkbox" :checked="selectedCrops.includes(crop)" class="me-2" style="cursor: pointer;">
                                                        <span style="font-size: 12px;" x-text="crop"></span>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Financial Terms --}}
                            <div class="card border border-secondary border-opacity-25 shadow-sm rounded-4 bg-body-secondary" style="z-index: 10;">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center gap-2 pb-2 mb-3 border-bottom">
                                        <div class="bg-danger bg-opacity-10 text-danger rounded-2 d-flex align-items-center justify-content-center" style="width: 24px; height: 24px;">
                                            <i class="bi bi-credit-card fs-6"></i>
                                        </div>
                                        <h6 class="mb-0 fw-bold text-uppercase text-body" style="font-size: 11px; letter-spacing: 1px;">Financial Terms</h6>
                                    </div>
                                    <div class="row g-2">
                                        <div class="col-sm-6">
                                            <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">Credit Limit (Rs )</label>
                                            <input type="number" step="0.01" name="credit_limit" x-model="form.credit_limit" class="form-control form-control-sm fw-semibold" style="font-size: 12px;">
                                        </div>
                                        <div class="col-sm-6">
                                            <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">Credit Days</label>
                                            <input type="number" name="credit_days" x-model="form.credit_days" class="form-control form-control-sm fw-semibold" style="font-size: 12px;">
                                        </div>
                                        <div class="col-sm-6">
                                            <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">Wallet Balance</label>
                                            <input type="number" step="0.01" name="outstanding_balance" x-model="form.outstanding_balance" class="form-control form-control-sm fw-semibold" style="font-size: 12px;">
                                        </div>
                                        <div class="col-sm-6">
                                            <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">Validity Period</label>
                                            <input type="date" name="credit_valid_till" x-model="form.credit_valid_till" class="form-control form-control-sm fw-semibold" style="font-size: 12px;">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Form Actions --}}
                    <div class="d-flex justify-content-end gap-3 mt-4 pt-3 border-top">
                        <button type="button" @click="$dispatch('close-modal', { name: 'global-add-customer-modal' })" data-bs-dismiss="modal" class="btn text-muted fw-bold text-uppercase" style="font-size: 11px; letter-spacing: 1px;">Cancel</button>
                        <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm" :disabled="isSubmitting">
                            <span x-show="isSubmitting" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                            <span x-text="isEdit ? 'Save Changes' : 'Create Customer Profile'"></span>
                        </button>
                    </div>
                    <div class="alert alert-danger mt-3 mb-0 py-2 small fw-bold" x-show="formError" x-text="formError" x-cloak></div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.custom-hover-bg:hover { background-color: rgba(var(--bs-primary-rgb), 0.1); }
input[type="text"]:focus, input[type="email"]:focus, input[type="number"]:focus, input[type="date"]:focus, select:focus, textarea:focus {
    box-shadow: 0 0 0 0.25rem rgba(var(--bs-primary-rgb), 0.15) !important;
    border-color: var(--bs-primary) !important;
}
.cursor-pointer { cursor: pointer; }
</style>

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('addCustomerApp', () => ({
        phone: '',
        sources: {!! json_encode($dynamicLeadSources ?? ['Referral', 'Walk-in', 'Social Media', 'Website', 'Advertisement', 'Event', 'Cold Call', 'Other']) !!},
        selectedSources: [],
        showSourceDropdown: false,
        landUnits: {!! json_encode($dynamicLandUnits ?? ['Acre', 'Hectare', 'Bigha', 'Guntha', 'Kanal', 'Marla']) !!},
        irrigationTypes: {!! json_encode($dynamicIrrigationTypes ?? ['Drip', 'Sprinkler', 'Canal', 'Tube Well', 'Rainfed', 'River Pump']) !!},
        selectedIrrigation: [],
        showIrrigationDropdown: false,
        allCrops: {!! json_encode($dynamicCrops ?? ['Wheat', 'Rice', 'Cotton', 'Sugarcane', 'Maize', 'Soybean', 'Gram', 'Mustard', 'Bajra', 'Jowar']) !!},
        selectedCrops: [],
        showCropsDropdown: false,
        cropSearch: '',
        isSubmitting: false,
        formError: '',
        modalInstance: null,
        isEdit: false,
        customerId: null,
        form: {
            firstname: '', middlename: '', lastname: '', email: '', phone: '', alternatemobile: '', relative_name: '', relative_phone: '',
            category: 'individual', status: 'active', internal_notes: '', company_name: '', gst_no: '', pan_no: '', tax_no: '', aadhaar_last4: '', kyc_completed: false, is_blacklisted: false,
            land_area: '', land_unit: 'Acre', credit_limit: '', credit_days: '', outstanding_balance: '', credit_valid_till: ''
        },

        get filteredCrops() {
            if (!this.cropSearch) return this.allCrops;
            return this.allCrops.filter(c => c.toLowerCase().includes(this.cropSearch.toLowerCase()));
        },

        toggleSource(name) {
            if(this.selectedSources.includes(name)) {
                this.selectedSources = this.selectedSources.filter(s => s !== name);
            } else {
                this.selectedSources.push(name);
            }
        },

        toggleIrrigation(name) {
            if(this.selectedIrrigation.includes(name)) {
                this.selectedIrrigation = this.selectedIrrigation.filter(t => t !== name);
            } else {
                this.selectedIrrigation.push(name);
            }
        },

        toggleCrop(name) {
            if(this.selectedCrops.includes(name)) {
                this.selectedCrops = this.selectedCrops.filter(c => c !== name);
            } else {
                this.selectedCrops.push(name);
            }
        },

        resetForm() {
            this.form = {
                firstname: '', middlename: '', lastname: '', email: '', phone: '', alternatemobile: '', relative_name: '', relative_phone: '',
                category: 'individual', status: 'active', internal_notes: '', company_name: '', gst_no: '', pan_no: '', tax_no: '', aadhaar_last4: '', kyc_completed: false, is_blacklisted: false,
                land_area: '', land_unit: 'Acre', credit_limit: '', credit_days: '', outstanding_balance: '', credit_valid_till: ''
            };
            this.selectedSources = [];
            this.selectedIrrigation = [];
            this.selectedCrops = [];
            this.isEdit = false;
            this.customerId = null;
        },

        openModal(detail) {
            this.resetForm();
            if (detail && detail.customer) {
                this.isEdit = true;
                this.customerId = detail.customer.id;
                // copy fields
                for(let key in this.form) {
                    if (detail.customer[key] !== undefined && detail.customer[key] !== null) {
                        this.form[key] = detail.customer[key];
                    }
                }
                // Date formatting for valid_till
                if (detail.customer.credit_valid_till) {
                    this.form.credit_valid_till = new Date(detail.customer.credit_valid_till).toISOString().split('T')[0];
                }
                // Arrays
                this.selectedSources = detail.customer.source || [];
                this.selectedIrrigation = detail.customer.irrigation_type || [];
                this.selectedCrops = detail.customer.crops || [];
            } else if (detail && detail.phone) {
                this.form.phone = detail.phone;
            }
            
            this.formError = '';
            // Initialize modal if not already
            if (!this.modalInstance) {
                this.modalInstance = new bootstrap.Modal(document.getElementById('addCustomerModal'));
            }
            this.modalInstance.show();
        },

        async submitForm(e) {
            this.formError = '';
            this.isSubmitting = true;
            
            if (this.form.phone && this.form.alternatemobile && this.form.phone === this.form.alternatemobile) {
                this.formError = 'Primary Phone and Alternate Mobile cannot be the same.';
                this.isSubmitting = false;
                return;
            }
            
            const formData = new FormData(e.target);
            
            // Bug Fix: Send to /api/customers to hit the correct RESTful POST endpoint
            let url = '/api/customers';
            if (this.isEdit) {
                url = `/api/customers/${this.customerId}`;
                formData.append('_method', 'PUT');
            }
            
            try {
                const response = await fetch(url, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
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
                
                // Success
                this.isSubmitting = false;
                if (this.modalInstance) {
                    this.modalInstance.hide();
                }
                
                if (this.isEdit) {
                    window.dispatchEvent(new CustomEvent('customer-updated'));
                    if (window.dispatchEvent) {
                        window.dispatchEvent(new CustomEvent('notify', { detail: { type: 'success', message: 'Profile updated successfully!' }}));
                    }
                } else {
                    // Feature Request: Redirect to orders create page with pre-selected customer
                    window.location.href = `/orders/create?customer_id=${data.data.id}`;
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
