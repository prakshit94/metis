<div class="modal fade" id="headerProfileModal" tabindex="-1" aria-labelledby="headerProfileModalLabel" aria-hidden="true"
     x-data="headerProfileModal"
     @open-profile-modal.window="openModal($event.detail)">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">

            {{-- Loading State --}}
            <div class="modal-body text-center py-5" x-show="loading" style="display:none!important">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-3 text-muted">Loading profile...</p>
            </div>

            {{-- Profile Content --}}
            <div class="modal-body pt-3" x-show="!loading && form.id" style="display:none!important">
                <div class="view-profile-container pb-4">

                    {{-- Profile Header --}}
                    <div class="d-flex align-items-start justify-content-between mb-4 pb-4 border-bottom">
                        <div class="d-flex align-items-center gap-4">
                            <div class="position-relative group" style="cursor: pointer;" @click="if (form.id == {{ auth()->id() }}) $refs.photoInput.click()">
                                <img :src="form.photo || (form.gender === 'Male' ? '/assets/images/default_male.png' : (form.gender === 'Female' ? '/assets/images/default_female.png' : '/assets/images/default_avatar.jpeg'))"
                                     class="rounded-circle border border-3 shadow-sm bg-body-tertiary"
                                     style="width: 110px; height: 110px; object-fit: cover; border-color: var(--bs-border-color) !important;"
                                     alt="Profile Picture">
                                <span class="position-absolute bottom-0 end-0 p-2 border border-2 rounded-circle shadow-sm"
                                      :class="form.is_active ? 'bg-success' : 'bg-secondary'"
                                      style="width: 22px; height: 22px; right: 6px !important; bottom: 6px !important; border-color: var(--bs-body-bg) !important;"></span>
                                
                                <template x-if="form.id == {{ auth()->id() }}">
                                    <div class="position-absolute top-0 start-0 w-100 h-100 rounded-circle bg-dark bg-opacity-50 d-flex align-items-center justify-content-center opacity-0 hover-opacity-100 transition-all" style="opacity: 0; transition: opacity 0.2s;" onmouseover="this.style.opacity=1" onmouseout="this.style.opacity=0">
                                        <i class="bi bi-camera-fill text-white fs-4" x-show="!uploadingPhoto"></i>
                                        <div class="spinner-border spinner-border-sm text-white" role="status" x-show="uploadingPhoto" x-cloak></div>
                                    </div>
                                </template>
                                
                                <input type="file" x-ref="photoInput" class="d-none" accept="image/*" @change="uploadPhoto($event)">
                            </div>
                            <div>
                                <h3 class="mb-1 fw-bold text-body" x-text="`${form.first_name || ''} ${form.middle_name || ''} ${form.last_name || ''}`.trim() || form.name"></h3>
                                <div class="text-muted mb-2 d-flex align-items-center gap-2" style="font-size: 0.95rem;">
                                    <span class="fw-medium text-body d-flex align-items-center gap-1"><i class="bi bi-briefcase text-muted"></i> <span x-text="form.designation || 'No Designation'"></span></span>
                                    <span class="text-muted">•</span>
                                    <span class="d-flex align-items-center gap-1"><i class="bi bi-geo-alt text-muted"></i> <span x-text="form.city || form.district || 'Location Unknown'"></span></span>
                                </div>
                                <span class="badge bg-primary-subtle text-primary-emphasis rounded-pill px-3 py-1 fw-medium border border-primary-subtle"
                                      x-text="(form.roles && form.roles.length) ? form.roles[0].name : 'User'"></span>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    {{-- Details Row --}}
                    <div class="row g-4 mt-2">

                        {{-- Left Column --}}
                        <div class="col-lg-4">
                            {{-- Core Identity --}}
                            <div class="card border-start border-4 border-success shadow-sm mb-4 bg-body-tertiary">
                                <div class="card-header bg-transparent border-0 pt-4 pb-0">
                                    <h6 class="fw-bold text-uppercase text-muted mb-0" style="letter-spacing: 0.5px; font-size: 0.8rem;"><i class="bi bi-person-badge me-2"></i>Core Identity</h6>
                                </div>
                                <div class="card-body">
                                    <ul class="list-unstyled mb-0">
                                        <li class="d-flex align-items-center mb-4">
                                            <div class="bg-primary-subtle text-primary-emphasis rounded-circle p-2 me-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                <i class="bi bi-hash fs-5"></i>
                                            </div>
                                            <div>
                                                <small class="text-muted d-block fw-medium" style="font-size: 0.75rem;">Employee ID</small>
                                                <span class="fw-semibold text-body" x-text="form.employee_id || '—'"></span>
                                            </div>
                                        </li>
                                        <li class="d-flex align-items-center mb-4">
                                            <div class="bg-info-subtle text-info-emphasis rounded-circle p-2 me-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                <i class="bi bi-envelope-fill fs-5"></i>
                                            </div>
                                            <div>
                                                <small class="text-muted d-block fw-medium" style="font-size: 0.75rem;">Email Address</small>
                                                <a :href="`mailto:${form.email}`" class="fw-semibold text-body text-decoration-none" x-text="form.email || '—'"></a>
                                            </div>
                                        </li>
                                        <li class="d-flex align-items-center mb-4">
                                            <div class="bg-success-subtle text-success-emphasis rounded-circle p-2 me-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                <i class="bi bi-telephone-fill fs-5"></i>
                                            </div>
                                            <div>
                                                <small class="text-muted d-block fw-medium" style="font-size: 0.75rem;">Phone Number</small>
                                                <a :href="`tel:${form.phone}`" class="fw-semibold text-body text-decoration-none" x-text="form.phone || '—'"></a>
                                            </div>
                                        </li>
                                        <li class="d-flex align-items-center">
                                            <div class="bg-warning-subtle text-warning-emphasis rounded-circle p-2 me-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                <i class="bi bi-shield-check fs-5"></i>
                                            </div>
                                            <div>
                                                <small class="text-muted d-block fw-medium" style="font-size: 0.75rem;">Account Status</small>
                                                <span class="badge rounded-pill px-3 py-1 mt-1"
                                                      :class="form.is_active ? 'bg-success-subtle text-success-emphasis' : 'bg-secondary-subtle text-secondary-emphasis'"
                                                      x-text="form.is_active ? 'Active' : 'Inactive'"></span>
                                            </div>
                                        </li>
                                    </ul>
                                </div>
                            </div>

                            {{-- Emergency Contact --}}
                            <div class="card border-start border-4 border-warning shadow-sm bg-danger-subtle border border-danger-subtle">
                                <div class="card-body p-4">
                                    <h6 class="fw-bold text-danger-emphasis mb-3 d-flex align-items-center gap-2"><i class="bi bi-heart-pulse-fill"></i> Emergency Contact</h6>
                                    <div class="mb-3">
                                        <small class="text-danger-emphasis text-opacity-75 d-block mb-1 fw-medium" style="font-size: 0.75rem;">Contact Name</small>
                                        <div class="fw-semibold text-body-emphasis" x-text="form.emergency_contact_name || 'No contact provided'"></div>
                                    </div>
                                    <div>
                                        <small class="text-danger-emphasis text-opacity-75 d-block mb-1 fw-medium" style="font-size: 0.75rem;">Phone Number</small>
                                        <div class="fw-semibold text-body-emphasis" x-text="form.emergency_contact_phone || '—'"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Right Column --}}
                        <div class="col-lg-8">

                            {{-- Personal Information --}}
                            <div class="card border-start border-4 border-info shadow-sm mb-4">
                                <div class="card-header bg-transparent border-bottom pt-4 pb-3 px-4">
                                    <h6 class="fw-bold text-uppercase text-primary-emphasis mb-0" style="letter-spacing: 0.5px; font-size: 0.8rem;"><i class="bi bi-person-vcard me-2"></i>Personal Information</h6>
                                </div>
                                <div class="card-body p-4">
                                    <div class="row g-4">
                                        <div class="col-sm-4">
                                            <div class="d-flex align-items-center gap-3">
                                                <div class="bg-body-secondary rounded p-2 text-body-secondary"><i class="bi bi-calendar-event"></i></div>
                                                <div>
                                                    <small class="text-muted text-uppercase d-block fw-semibold mb-1" style="font-size: 0.7rem; letter-spacing: 0.5px;">Date of Birth</small>
                                                    <span class="fw-semibold text-body" x-text="form.date_of_birth ? new Date(form.date_of_birth).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' }) : '—'"></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-sm-4">
                                            <div class="d-flex align-items-center gap-3">
                                                <div class="bg-body-secondary rounded p-2 text-body-secondary"><i class="bi bi-gender-ambiguous"></i></div>
                                                <div>
                                                    <small class="text-muted text-uppercase d-block fw-semibold mb-1" style="font-size: 0.7rem; letter-spacing: 0.5px;">Gender</small>
                                                    <span class="fw-semibold text-body" x-text="form.gender || '—'"></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-sm-4">
                                            <div class="d-flex align-items-center gap-3">
                                                <div class="bg-body-secondary rounded p-2 text-danger"><i class="bi bi-droplet-fill"></i></div>
                                                <div>
                                                    <small class="text-muted text-uppercase d-block fw-semibold mb-1" style="font-size: 0.7rem; letter-spacing: 0.5px;">Blood Group</small>
                                                    <span class="fw-semibold text-body" x-text="form.blood_group || '—'"></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Employment Details --}}
                            <div class="card border-start border-4 border-danger shadow-sm mb-4">
                                <div class="card-header bg-transparent border-bottom pt-4 pb-3 px-4">
                                    <h6 class="fw-bold text-uppercase text-success-emphasis mb-0" style="letter-spacing: 0.5px; font-size: 0.8rem;"><i class="bi bi-briefcase me-2"></i>Employment Details</h6>
                                </div>
                                <div class="card-body p-4">
                                    <div class="row g-4">
                                        <div class="col-sm-6">
                                            <div class="p-3 bg-body-tertiary rounded-3 h-100 border border-secondary-subtle">
                                                <small class="text-muted text-uppercase d-block fw-semibold mb-2" style="font-size: 0.7rem; letter-spacing: 0.5px;">Department</small>
                                                <div class="d-flex align-items-center gap-2">
                                                    <i class="bi bi-diagram-3 text-success"></i>
                                                    <span class="fw-semibold text-body" x-text="(form.department && form.department.name) ? form.department.name : '—'"></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="p-3 bg-body-tertiary rounded-3 h-100 border border-secondary-subtle">
                                                <small class="text-muted text-uppercase d-block fw-semibold mb-2" style="font-size: 0.7rem; letter-spacing: 0.5px;">Designation</small>
                                                <div class="d-flex align-items-center gap-2">
                                                    <i class="bi bi-person-workspace text-success"></i>
                                                    <span class="fw-semibold text-body" x-text="form.designation || '—'"></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="p-3 bg-body-tertiary rounded-3 h-100 border border-secondary-subtle">
                                                <small class="text-muted text-uppercase d-block fw-semibold mb-2" style="font-size: 0.7rem; letter-spacing: 0.5px;">Manager</small>
                                                <div class="d-flex align-items-center gap-2">
                                                    <i class="bi bi-person-check text-success"></i>
                                                    <span class="fw-semibold text-body" x-text="(form.manager && form.manager.name) ? form.manager.name : '—'"></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="p-3 bg-body-tertiary rounded-3 h-100 border border-secondary-subtle">
                                                <small class="text-muted text-uppercase d-block fw-semibold mb-2" style="font-size: 0.7rem; letter-spacing: 0.5px;">Employment Status</small>
                                                <div class="d-flex align-items-center justify-content-between">
                                                    <span class="badge bg-info-subtle text-info-emphasis px-3 py-2" x-text="form.employment_type || '—'"></span>
                                                    <small class="text-muted">Joined <span x-text="form.joining_date ? new Date(form.joining_date).toLocaleString('en-US', { year: 'numeric', month: 'short', day: 'numeric' }) : '—'"></span></small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Address Details --}}
                            <div class="card border-start border-4 border-secondary shadow-sm">
                                <div class="card-header bg-transparent border-bottom pt-4 pb-3 px-4">
                                    <h6 class="fw-bold text-uppercase text-info-emphasis mb-0" style="letter-spacing: 0.5px; font-size: 0.8rem;"><i class="bi bi-geo-alt me-2"></i>Address Details</h6>
                                </div>
                                <div class="card-body p-4">
                                    <div class="row g-4">
                                        <div class="col-sm-6">
                                            <small class="text-muted text-uppercase d-block fw-semibold mb-1" style="font-size: 0.7rem; letter-spacing: 0.5px;">Address Line 1</small>
                                            <span class="fw-semibold text-body" x-text="form.address_line_1 || '—'"></span>
                                        </div>
                                        <div class="col-sm-6">
                                            <small class="text-muted text-uppercase d-block fw-semibold mb-1" style="font-size: 0.7rem; letter-spacing: 0.5px;">Address Line 2</small>
                                            <span class="fw-semibold text-body" x-text="form.address_line_2 || '—'"></span>
                                        </div>
                                        <div class="col-sm-4">
                                            <small class="text-muted text-uppercase d-block fw-semibold mb-1" style="font-size: 0.7rem; letter-spacing: 0.5px;">Village / City</small>
                                            <span class="fw-semibold text-body" x-text="form.village_name || form.city || '—'"></span>
                                        </div>
                                        <div class="col-sm-4">
                                            <small class="text-muted text-uppercase d-block fw-semibold mb-1" style="font-size: 0.7rem; letter-spacing: 0.5px;">Post Office</small>
                                            <span class="fw-semibold text-body" x-text="form.post_office || '—'"></span>
                                        </div>
                                        <div class="col-sm-4">
                                            <small class="text-muted text-uppercase d-block fw-semibold mb-1" style="font-size: 0.7rem; letter-spacing: 0.5px;">Taluka</small>
                                            <span class="fw-semibold text-body" x-text="form.taluka || '—'"></span>
                                        </div>
                                        <div class="col-sm-4">
                                            <small class="text-muted text-uppercase d-block fw-semibold mb-1" style="font-size: 0.7rem; letter-spacing: 0.5px;">District</small>
                                            <span class="fw-semibold text-body" x-text="form.district || '—'"></span>
                                        </div>
                                        <div class="col-sm-4">
                                            <small class="text-muted text-uppercase d-block fw-semibold mb-1" style="font-size: 0.7rem; letter-spacing: 0.5px;">State</small>
                                            <span class="fw-semibold text-body" x-text="form.state || '—'"></span>
                                        </div>
                                        <div class="col-sm-4">
                                            <small class="text-muted text-uppercase d-block fw-semibold mb-1" style="font-size: 0.7rem; letter-spacing: 0.5px;">Pincode</small>
                                            <span class="fw-semibold text-body" x-text="form.pincode || '—'"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>{{-- /Right Column --}}
                    </div>{{-- /row --}}
                </div>{{-- /view-profile-container --}}
            </div>{{-- /modal-body --}}

        </div>{{-- /modal-content --}}
    </div>{{-- /modal-dialog --}}
</div>{{-- /modal --}}

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('headerProfileModal', () => ({
        loading: false,
        uploadingPhoto: false,
        form: {},

        openModal(userId) {
            this.loading = true;
            this.form = {};

            const modalEl = document.getElementById('headerProfileModal');
            let modal = bootstrap.Modal.getInstance(modalEl);
            if (!modal) {
                modal = new bootstrap.Modal(modalEl);
            }
            modal.show();

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
            fetch('/api/users/' + userId, {
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken,
                }
            })
            .then(res => res.json())
            .then(data => {
                this.form = data.data || data;
                this.loading = false;
            })
            .catch(err => {
                console.error('Profile load error:', err);
                this.loading = false;
                if (window.Swal) {
                    Swal.fire('Error', 'Failed to load profile details.', 'error');
                }
            });
        },

        uploadPhoto(event) {
            const file = event.target.files[0];
            if (!file) return;

            if (window.Swal) {
                Swal.fire({
                    title: 'Update Profile Picture?',
                    text: 'Are you sure you want to change your profile picture?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, update it',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        this.processUpload(file, event);
                    } else {
                        event.target.value = '';
                    }
                });
            } else {
                if (confirm('Are you sure you want to change your profile picture?')) {
                    this.processUpload(file, event);
                } else {
                    event.target.value = '';
                }
            }
        },

        processUpload(file, event) {
            this.uploadingPhoto = true;
            const formData = new FormData();
            formData.append('photo_file', file);

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

            fetch('/api/users/' + this.form.id + '/update-photo', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                this.uploadingPhoto = false;
                event.target.value = '';
                if (data.photo) {
                    this.form.photo = data.photo;
                    
                    // Update header images dynamically without page refresh
                    document.querySelectorAll('img[alt="User"]').forEach(img => {
                        img.src = data.photo;
                    });
                    if (window.Swal) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: data.message || 'Profile photo updated successfully.',
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 3000
                        });
                    }
                } else if (data.message) {
                    if (window.Swal) Swal.fire('Error', data.message, 'error');
                }
            })
            .catch(err => {
                console.error('Photo upload error:', err);
                this.uploadingPhoto = false;
                event.target.value = '';
                if (window.Swal) {
                    Swal.fire('Error', 'Failed to upload photo.', 'error');
                }
            });
        }
    }));
});
</script>
@endpush