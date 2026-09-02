<div class="modal fade" id="addCustomerModal" aria-labelledby="addCustomerModalLabel" aria-hidden="true" 
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
                                    <div class="d-flex align-items-center gap-3 mb-3">
                                        <div class="position-relative">
                                            <div class="bg-secondary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center overflow-hidden border" style="width: 60px; height: 60px;">
                                                <template x-if="avatarPreview">
                                                    <img :src="avatarPreview" class="w-100 h-100 object-fit-cover">
                                                </template>
                                                <template x-if="!avatarPreview">
                                                    <i class="bi bi-camera text-secondary fs-4"></i>
                                                </template>
                                            </div>
                                            <input type="file" name="avatar" class="position-absolute top-0 start-0 w-100 h-100 opacity-0 cursor-pointer" accept="image/*" @change="handleAvatarUpload">
                                        </div>
                                        <div>
                                            <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">Profile Photo</label>
                                            <p class="mb-0 text-muted" style="font-size: 10px;">Click the icon to upload. Max 2MB (JPG, PNG)</p>
                                        </div>
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
                                            <select x-select name="status" x-model="form.status" class="form-select form-select-sm fw-semibold" style="font-size: 12px;">
                                                <option value="active">Active</option>
                                                <option value="inactive">Inactive</option>
                                                <option value="suspended">Suspended</option>
                                            </select>
                                        </div>
                                        <div class="col-sm-4">
                                            <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">Category</label>
                                            <select x-select name="category" x-model="form.category" class="form-select form-select-sm fw-semibold" style="font-size: 12px;">
                                                <option value="">— Select —</option>
                                                <option value="individual">Individual</option>
                                                <option value="business">Business</option>
                                            </select>
                                        </div>
                                        <div class="col-sm-4">
                                            <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">Referral Code</label>
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text bg-body text-muted border-end-0"><i class="bi bi-person-heart"></i></span>
                                                <input type="text" name="referred_by_code" x-model="form.referred_by_code" @input.debounce.500ms="checkReferralCode" class="form-control border-start-0 ps-0 fw-semibold text-uppercase font-monospace" style="font-size: 12px;" placeholder="Optional">
                                                <span class="input-group-text bg-body border-start-0" x-show="form.referred_by_code && isValidatingReferral === false" x-cloak>
                                                    <i class="bi bi-check-circle-fill text-success" x-show="referralValid"></i>
                                                    <i class="bi bi-x-circle-fill text-danger" x-show="!referralValid && form.referred_by_code.length > 0"></i>
                                                </span>
                                                <span class="input-group-text bg-body border-start-0" x-show="isValidatingReferral" x-cloak>
                                                    <span class="spinner-border spinner-border-sm text-primary" role="status"></span>
                                                </span>
                                            </div>
                                            <div x-show="referralName" class="text-success small fw-bold mt-1" style="font-size: 10px;" x-text="'Referrer: ' + referralName" x-cloak></div>
                                            <div x-show="!referralValid && form.referred_by_code && isValidatingReferral === false" class="text-danger small fw-bold mt-1" style="font-size: 10px;" x-cloak>Invalid Referral Code</div>
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
                            <div class="card mb-3 mb-lg-0 border border-secondary border-opacity-25 shadow-sm rounded-4 bg-body-secondary" style="z-index: 20;">
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
                                                <input type="email" name="email" x-model="form.email" class="form-control border-start-0 ps-0 fw-semibold" style="font-size: 12px;" pattern="[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}" title="Please enter a valid email address">
                                            </div>
                                        </div>
                                        <div class="col-sm-4">
                                            <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">Primary Phone *</label>
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text text-muted border-end-0" :class="isEdit ? 'bg-body-secondary' : 'bg-body'"><i class="bi bi-phone"></i></span>
                                                <input type="text" name="phone" x-model="form.phone" class="form-control border-start-0 ps-0 fw-semibold" :class="isEdit ? 'bg-body-secondary text-muted opacity-75' : ''" style="font-size: 12px;" required pattern="\d{10}" minlength="10" maxlength="10" title="Must be exactly 10 digits" oninput="this.value = this.value.replace(/\D/g, '').substring(0,10)" :readonly="isEdit">
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
                            
                        </div>

                        {{-- RIGHT COLUMN --}}
                        <div class="col-lg-6">
                            
                            {{-- Primary Address (Only for New Customers) --}}
                            <div class="card mb-3 border border-secondary border-opacity-25 shadow-sm rounded-4 bg-body-secondary" style="z-index: 40;" x-show="!isEdit" x-cloak>
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center gap-2 pb-2 mb-3 border-bottom">
                                        <div class="bg-success bg-opacity-10 text-success rounded-2 d-flex align-items-center justify-content-center" style="width: 24px; height: 24px;">
                                            <i class="bi bi-geo fs-6"></i>
                                        </div>
                                        <h6 class="mb-0 fw-bold text-uppercase text-body" style="font-size: 11px; letter-spacing: 1px;">Primary Address</h6>
                                    </div>
                                    <div class="row g-2">
                                        <div class="col-sm-6">
                                            <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">Address Line 1</label>
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text bg-body text-muted border-end-0"><i class="bi bi-house"></i></span>
                                                <input type="text" class="form-control border-start-0 ps-0 fw-semibold" style="font-size: 12px;" x-model="form.address_line_1">
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">Address Line 2</label>
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text bg-body text-muted border-end-0"><i class="bi bi-signpost"></i></span>
                                                <input type="text" class="form-control border-start-0 ps-0 fw-semibold" style="font-size: 12px;" x-model="form.address_line_2">
                                            </div>
                                        </div>
                            
                                        <div class="col-12 position-relative" @click.away="villageResults = []">
                                            <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">Village Search</label>
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text bg-body text-muted border-end-0"><i class="bi bi-search"></i></span>
                                                <input type="text" class="form-control border-start-0 ps-0 fw-semibold" style="font-size: 12px;" placeholder="Type 3 letters to search village..." 
                                                       x-model="villageSearchQuery" @input.debounce.300ms="searchVillages()">
                                            </div>
                                            <div class="position-absolute w-100 bg-body border rounded shadow-lg mt-1 overflow-auto" style="max-height: 200px; z-index: 1060;" x-show="villageResults.length > 0">
                                                <template x-for="v in villageResults" :key="v.id">
                                                    <button type="button" class="dropdown-item w-100 text-start py-2 px-3 border-bottom custom-hover-bg" @click="selectVillage(v)">
                                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                                            <span class="fw-bold text-primary" style="font-size: 12px;" x-text="v.village_name"></span>
                                                            <span class="badge bg-secondary-subtle text-secondary-emphasis" x-text="v.pincode"></span>
                                                        </div>
                                                        <div class="text-muted small" style="font-size: 0.75rem;">
                                                            <span x-show="v.post_so_name" x-text="'PO: ' + v.post_so_name + ' · '"></span>
                                                            <span x-show="v.taluka_name" x-text="'Taluka: ' + v.taluka_name + ' · '"></span>
                                                            <span x-show="v.district_name" x-text="'District: ' + v.district_name"></span>
                                                        </div>
                                                    </button>
                                                </template>
                                            </div>
                                        </div>
                            
                                        <!-- Selected Village Details -->
                                        <template x-if="form.village_name">
                                            <div class="col-12 mt-2">
                                                <div class="card bg-body border-0 border-start border-4 border-primary shadow-sm">
                                                    <div class="card-body p-2">
                                                        <div class="row g-1">
                                                            <div class="col-4">
                                                                <div class="small fw-bold text-muted text-uppercase" style="font-size: 9px;">VILLAGE</div>
                                                                <div class="fw-semibold text-truncate" style="font-size: 11px;" x-text="form.village_name || '—'"></div>
                                                            </div>
                                                            <div class="col-4">
                                                                <div class="small fw-bold text-muted text-uppercase" style="font-size: 9px;">POST OFFICE</div>
                                                                <div class="fw-semibold text-truncate" style="font-size: 11px;" x-text="form.post_office || '—'"></div>
                                                            </div>
                                                            <div class="col-4">
                                                                <div class="small fw-bold text-muted text-uppercase" style="font-size: 9px;">TALUKA</div>
                                                                <div class="fw-semibold text-truncate" style="font-size: 11px;" x-text="form.taluka || '—'"></div>
                                                            </div>
                                                            <div class="col-4 mt-2">
                                                                <div class="small fw-bold text-muted text-uppercase" style="font-size: 9px;">DISTRICT</div>
                                                                <div class="fw-semibold text-truncate" style="font-size: 11px;" x-text="form.district || '—'"></div>
                                                            </div>
                                                            <div class="col-4 mt-2">
                                                                <div class="small fw-bold text-muted text-uppercase" style="font-size: 9px;">STATE</div>
                                                                <div class="fw-semibold text-truncate" style="font-size: 11px;" x-text="form.state || '—'"></div>
                                                            </div>
                                                            <div class="col-4 mt-2">
                                                                <div class="small fw-bold text-muted text-uppercase" style="font-size: 9px;">PINCODE</div>
                                                                <div class="fw-bold text-primary" style="font-size: 11px;" x-text="form.pincode || '—'"></div>
                                                            </div>
                                                        </div>
                                                        <div class="mt-2 pt-2 border-top">
                                                            <div class="small text-body-secondary fw-semibold text-uppercase mb-1" style="font-size: 10px; letter-spacing: .5px;">Available services</div>
                                                            <div class="d-flex flex-wrap gap-1" x-show="selectedVillageServices && selectedVillageServices.length > 0">
                                                                <template x-for="(service, index) in selectedVillageServices" :key="service.id">
                                                                    <span class="badge text-bg-success" x-text="`${Number(service.pivot?.priority) > 0 ? Number(service.pivot.priority) : index + 1}. ${service.name}`"></span>
                                                                </template>
                                                            </div>
                                                            <span class="small text-body-secondary" x-show="!selectedVillageServices || selectedVillageServices.length === 0">No service available for this address</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </template>
                            
                                        <!-- Manual Input Fields (no village selected) -->
                                        <template x-if="!form.village_name && form.address_line_1">
                                            <div class="col-12 mt-2">
                                                <div class="row g-2">
                                                    <div class="col-md-4">
                                                        <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">City *</label>
                                                        <input type="text" class="form-control form-control-sm fw-semibold" style="font-size: 12px;" x-model="form.city" :required="!isEdit && !form.village_name && form.address_line_1.length > 0">
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">State *</label>
                                                        <input type="text" class="form-control form-control-sm fw-semibold" style="font-size: 12px;" x-model="form.state" :required="!isEdit && !form.village_name && form.address_line_1.length > 0">
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">Pincode *</label>
                                                        <input type="text" class="form-control form-control-sm fw-semibold" style="font-size: 12px;" x-model="form.pincode" :required="!isEdit && !form.village_name && form.address_line_1.length > 0">
                                                    </div>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                            
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
                                                <select x-select name="land_unit" x-model="form.land_unit" class="form-select w-50 fw-semibold" style="font-size: 12px;">
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

                            {{-- Financial Terms --}}
                            <div class="card d-none border border-secondary border-opacity-25 shadow-sm rounded-4 bg-body-secondary" style="z-index: 10;">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center gap-2 pb-2 mb-3 border-bottom">
                                        <div class="bg-danger bg-opacity-10 text-danger rounded-2 d-flex align-items-center justify-content-center" style="width: 24px; height: 24px;">
                                            <i class="bi bi-credit-card fs-6"></i>
                                        </div>
                                        <h6 class="mb-0 fw-bold text-uppercase text-body" style="font-size: 11px; letter-spacing: 1px;">Financial Terms</h6>
                                    </div>
                                    <div class="row g-2">
                                        <div class="col-sm-6">
                                            <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">Credit Limit (₹ )</label>
                                            <input type="number" step="0.01" name="credit_limit" x-model="form.credit_limit" class="form-control form-control-sm fw-semibold" style="font-size: 12px;">
                                        </div>
                                        <div class="col-sm-6">
                                            <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">Credit Days</label>
                                            <input type="number" name="credit_days" x-model="form.credit_days" class="form-control form-control-sm fw-semibold" style="font-size: 12px;">
                                        </div>
                                        <div class="col-sm-6">
                                            <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">Wallet Balance</label>
                                            <input type="number" step="0.01" name="wallet_balance" x-model="form.wallet_balance" class="form-control form-control-sm fw-semibold" style="font-size: 12px;">
                                        </div>
                                        <div class="col-sm-6">
                                            <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">Outstanding Bal.</label>
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
        isEdit: false,
        customerId: null,
        isValidatingReferral: false,
        referralValid: false,
        referralName: '',
        villageSearchQuery: '',
        villageResults: [],
        selectedVillageServices: [],
        form: {
            firstname: '', middlename: '', lastname: '', email: '', phone: '', alternatemobile: '', relative_name: '', relative_phone: '',
            category: 'individual', status: 'active', internal_notes: '', company_name: '', gst_no: '', pan_no: '', tax_no: '', aadhaar_last4: '', kyc_completed: false, is_blacklisted: false,
            land_area: '', land_unit: 'Acre', credit_limit: '', credit_days: '', outstanding_balance: '', wallet_balance: '', credit_valid_till: '', referred_by_code: '',
            address_line_1: '', address_line_2: '', village_id: '', village_name: '', post_office: '', taluka: '', district: '', city: '', state: '', pincode: ''
        },
        avatarPreview: '{{ asset('assets/images/farmersprofileimage.png') }}',

        handleAvatarUpload(e) {
            const file = e.target.files[0];
            if (!file) {
                this.avatarPreview = '{{ asset('assets/images/farmersprofileimage.png') }}';
                return;
            }
            if (file.size > 2 * 1024 * 1024) {
                this.formError = 'Photo must be less than 2MB';
                e.target.value = '';
                this.avatarPreview = '{{ asset('assets/images/farmersprofileimage.png') }}';
                return;
            }
            this.formError = '';
            const reader = new FileReader();
            reader.onload = e => this.avatarPreview = e.target.result;
            reader.readAsDataURL(file);
        },

        get filteredCrops() {
            if (!this.cropSearch) return this.allCrops;
            return this.allCrops.filter(c => c.toLowerCase().includes(this.cropSearch.toLowerCase()));
        },

        async checkReferralCode() {
            if (!this.form.referred_by_code || this.form.referred_by_code.trim().length < 3) {
                this.referralValid = false;
                this.referralName = '';
                return;
            }
            this.isValidatingReferral = true;
            try {
                const res = await fetch(`/api/customers/check-referral/${this.form.referred_by_code.trim().toUpperCase()}`);
                const data = await res.json();
                this.referralValid = data.valid;
                this.referralName = data.name || '';
            } catch(e) {
                this.referralValid = false;
                this.referralName = '';
            }
            this.isValidatingReferral = false;
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
            this.villageSearchQuery = '';
            this.villageResults = [];
            this.selectedVillageServices = [];
            this.form = {
                firstname: '', middlename: '', lastname: '', email: '', phone: '', alternatemobile: '', relative_name: '', relative_phone: '',
                category: 'individual', status: 'active', internal_notes: '', company_name: '', gst_no: '', pan_no: '', tax_no: '', aadhaar_last4: '', kyc_completed: false, is_blacklisted: false,
                land_area: '', land_unit: 'Acre', credit_limit: '', credit_days: '', outstanding_balance: '', wallet_balance: '', credit_valid_till: '', referred_by_code: '',
                address_line_1: '', address_line_2: '', village_id: '', village_name: '', post_office: '', taluka: '', district: '', city: '', state: '', pincode: ''
            };
            this.selectedSources = [];
            this.selectedIrrigation = [];
            this.selectedCrops = [];
            this.isEdit = false;
            this.customerId = null;
            this.isValidatingReferral = false;
            this.referralValid = false;
            this.referralName = '';
            this.avatarPreview = '{{ asset('assets/images/farmersprofileimage.png') }}';
            const fileInput = document.querySelector('input[name="avatar"]');
            if (fileInput) fileInput.value = '';
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
                // extract referral code from referrer if it exists
                if (detail.customer.referrer && detail.customer.referrer.referral_code) {
                    this.form.referred_by_code = detail.customer.referrer.referral_code;
                    this.checkReferralCode();
                }
                // Date formatting for valid_till
                if (detail.customer.credit_valid_till) {
                    this.form.credit_valid_till = new Date(detail.customer.credit_valid_till).toISOString().split('T')[0];
                }
                // Arrays
                this.selectedSources = detail.customer.source || [];
                this.selectedIrrigation = detail.customer.irrigation_type || [];
                this.selectedCrops = detail.customer.crops || [];
                
                if (detail.customer.avatar) {
                    this.avatarPreview = '/storage/' + detail.customer.avatar;
                } else {
                    this.avatarPreview = '{{ asset('assets/images/farmersprofileimage.png') }}';
                }
            } else if (detail && detail.phone) {
                this.form.phone = detail.phone;
            }
            
            this.formError = '';
            bootstrap.Modal.getOrCreateInstance(document.getElementById('addCustomerModal')).show();
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
                
                // Orchestrate Address Creation (Only for New Customers if address provided)
                if (!this.isEdit && (this.form.address_line_1 || this.form.village_id || this.form.city)) {
                    try {
                        const addrFormData = new FormData();
                        addrFormData.append('label', 'Home');
                        addrFormData.append('is_default', '1');
                        addrFormData.append('status', 'active');
                        addrFormData.append('address_line_1', this.form.address_line_1);
                        if (this.form.address_line_2) addrFormData.append('address_line_2', this.form.address_line_2);
                        if (this.form.village_id) addrFormData.append('village_id', this.form.village_id);
                        if (this.form.city) addrFormData.append('city', this.form.city);
                        if (this.form.state) addrFormData.append('state', this.form.state);
                        if (this.form.pincode) addrFormData.append('pincode', this.form.pincode);

                        const addrResponse = await fetch(`/api/customers/${data.data.id}/addresses`, {
                            method: 'POST',
                            credentials: 'same-origin',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: addrFormData
                        });
                        
                        if (!addrResponse.ok) {
                            const errData = await addrResponse.json();
                            console.error('Failed to create address:', errData);
                            
                            // Customer was created, but address failed.
                            // We shouldn't stop the redirect completely, but we should alert the user.
                            alert('Customer profile was created, but the address failed to save. Please add it manually from the customer management screen.');
                        }
                    } catch (addrErr) {
                        console.error('Failed to create address:', addrErr);
                        alert('Customer profile was created, but a network error prevented the address from saving.');
                    }
                }
                
                this.isSubmitting = false;

                const modal = bootstrap.Modal.getInstance(document.getElementById('addCustomerModal'));
                if (modal) modal.hide();
                
                if (this.isEdit) {
                    window.dispatchEvent(new CustomEvent('customer-updated'));
                    if (window.dispatchEvent) {
                        window.dispatchEvent(new CustomEvent('notify', { detail: { type: 'success', message: 'Profile updated successfully!' }}));
                    }
                } else {
                    window.dispatchEvent(new CustomEvent('customer-created', { detail: { customer: data.data } }));
                    window.dispatchEvent(new CustomEvent('notify', { detail: { type: 'success', message: 'Customer added successfully!' }}));
                    
                    const path = window.location.pathname;
                    if (path === '/' || path === '/dashboard' || path.includes('/orders/create')) {
                        window.location.href = `/orders/create?customer_id=${data.data.id}`;
                    } else {
                        setTimeout(() => window.location.reload(), 1000);
                    }
                }
            } catch (err) {
                this.formError = 'Network error occurred. Please try again.';
                this.isSubmitting = false;
            }
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
            
            this.selectedVillageServices = (v.services || []).filter(service => {
                const today = new Date().toISOString().slice(0, 10);
                const pivot = service.pivot || {};
                const hasStarted = !pivot.serviceable_from_date || pivot.serviceable_from_date <= today;
                const hasNotEnded = !pivot.serviceable_to_date || pivot.serviceable_to_date >= today;
                return hasStarted && hasNotEnded;
            }).sort((a, b) => {
                const priorityA = Number(a.pivot?.priority ?? 0);
                const priorityB = Number(b.pivot?.priority ?? 0);
                return priorityA - priorityB || String(a.name).localeCompare(String(b.name));
            });
            
            this.villageSearchQuery = '';
            this.villageResults = [];
        }
    }));
});
</script>
@endpush
