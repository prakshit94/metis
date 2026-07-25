@extends('layouts.app')

@section('title', 'Manage Call Tags')

@section('content')
<div class="container-fluid py-4" x-data="callTagsAdmin()">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold text-dark"><i class="bi bi-tags me-2 text-primary"></i>Call Tags Manager</h4>
            <p class="text-muted mb-0 small">Manage call outcome categories and dynamic form fields.</p>
        </div>
        <button class="btn btn-primary shadow-sm" @click="openModal()">
            <i class="bi bi-plus-circle me-2"></i>Add Level 1 Category
        </button>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light bg-opacity-75">
                        <tr>
                            <th class="ps-4">Tag Hierarchy</th>
                            <th>Level</th>
                            <th>Status</th>
                            <th>Dynamic Fields</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tags as $l1)
                            {{-- LEVEL 1 --}}
                            <tr class="bg-light bg-opacity-25">
                                <td class="ps-4">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-folder-fill text-warning me-2 fs-5"></i>
                                        <span class="fw-bold">{{ $l1->name }}</span>
                                    </div>
                                </td>
                                <td><span class="badge bg-secondary bg-opacity-10 text-secondary border">Level 1</span></td>
                                <td>
                                    @if($l1->is_active)
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle">Active</span>
                                    @else
                                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger-subtle">Inactive</span>
                                    @endif
                                </td>
                                <td>-</td>
                                <td class="text-end pe-4">
                                    <button class="btn btn-sm btn-light border shadow-sm me-1" @click="openModal({{ $l1->id }}, '{{ addslashes($l1->name) }}', 1, null, {{ $l1->is_active }})" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-primary shadow-sm me-1" @click="openModal(null, '', 2, {{ $l1->id }}, 1)" title="Add L2 Tag">
                                        <i class="bi bi-plus"></i> Add L2
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger shadow-sm" @click="deleteTag({{ $l1->id }})" title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            
                            @foreach($l1->children as $l2)
                                {{-- LEVEL 2 --}}
                                <tr>
                                    <td class="ps-5">
                                        <div class="d-flex align-items-center">
                                            <i class="bi bi-arrow-return-right text-muted me-2"></i>
                                            <i class="bi bi-folder2-open text-info me-2 fs-5"></i>
                                            <span class="fw-semibold text-dark">{{ $l2->name }}</span>
                                        </div>
                                    </td>
                                    <td><span class="badge bg-info bg-opacity-10 text-info border border-info-subtle">Level 2</span></td>
                                    <td>
                                        @if($l2->is_active)
                                            <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle">Active</span>
                                        @else
                                            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger-subtle">Inactive</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($l2->formFields->count() > 0)
                                            <span class="badge bg-primary rounded-pill">{{ $l2->formFields->count() }} Fields</span>
                                        @else
                                            <span class="text-muted small">-</span>
                                        @endif
                                    </td>
                                    <td class="text-end pe-4">
                                        <button class="btn btn-sm btn-light border shadow-sm me-1" @click="openModal({{ $l2->id }}, '{{ addslashes($l2->name) }}', 2, {{ $l2->parent_id }}, {{ $l2->is_active }}, {{ json_encode($l2->formFields) }})" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-info shadow-sm me-1" @click="openModal(null, '', 3, {{ $l2->id }}, 1)" title="Add L3 Tag">
                                            <i class="bi bi-plus"></i> Add L3
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger shadow-sm" @click="deleteTag({{ $l2->id }})" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                
                                @foreach($l2->children as $l3)
                                    {{-- LEVEL 3 --}}
                                    <tr>
                                        <td class="ps-5" style="padding-left: 4rem !important;">
                                            <div class="d-flex align-items-center">
                                                <i class="bi bi-arrow-return-right text-muted me-2"></i>
                                                <i class="bi bi-tag text-success me-2"></i>
                                                <span class="text-secondary">{{ $l3->name }}</span>
                                            </div>
                                        </td>
                                        <td><span class="badge bg-success bg-opacity-10 text-success border border-success-subtle">Level 3</span></td>
                                        <td>
                                            @if($l3->is_active)
                                                <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle">Active</span>
                                            @else
                                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger-subtle">Inactive</span>
                                            @endif
                                        </td>
                                        <td>-</td>
                                        <td class="text-end pe-4">
                                            <button class="btn btn-sm btn-light border shadow-sm me-1" @click="openModal({{ $l3->id }}, '{{ addslashes($l3->name) }}', 3, {{ $l3->parent_id }}, {{ $l3->is_active }})" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger shadow-sm" @click="deleteTag({{ $l3->id }})" title="Delete">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            @endforeach
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">
                                    <div class="mb-3 mt-3"><i class="bi bi-tags display-6 text-black-50"></i></div>
                                    No Call Tags found. Get started by adding a Level 1 Category.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Form Modal --}}
    <div class="modal fade" id="tagFormModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary bg-opacity-10 border-bottom-0">
                    <h5 class="modal-title fw-bold text-primary">
                        <i class="bi" :class="tagId ? 'bi-pencil-square' : 'bi-plus-circle'"></i> 
                        <span x-text="tagId ? 'Edit Tag' : 'Add Tag'"></span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label fw-bold">Tag Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" x-model="form.name" placeholder="e.g. Sales Enquiry">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Level</label>
                            <input type="text" class="form-control bg-light" x-model="form.level" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Status</label>
                            <select class="form-select" x-model="form.is_active">
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                    </div>
                    
                    {{-- Dynamic Form Builder for Level 2 only --}}
                    <template x-if="form.level == 2">
                        <div class="mt-4 p-3 border rounded bg-light">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="fw-bold mb-0"><i class="bi bi-ui-radios me-2"></i>Dynamic Form Fields</h6>
                                <button type="button" class="btn btn-sm btn-outline-primary" @click="addField()">
                                    <i class="bi bi-plus"></i> Add Field
                                </button>
                            </div>
                            
                            <template x-if="form.form_fields.length === 0">
                                <div class="text-muted small fst-italic py-2">No dynamic fields defined. You can add fields to capture extra data when this tag is selected.</div>
                            </template>
                            
                            <template x-for="(field, index) in form.form_fields" :key="index">
                                <div class="row g-2 mb-2 p-2 border rounded bg-white position-relative">
                                    <div class="col-md-3">
                                        <label class="form-label small text-muted mb-1">Label</label>
                                        <input type="text" class="form-control form-control-sm" x-model="field.label" placeholder="e.g. Select Product">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small text-muted mb-1">Key Name</label>
                                        <input type="text" class="form-control form-control-sm" x-model="field.name" placeholder="e.g. search_product" @input="field.name = field.name.toLowerCase().replace(/[^a-z0-9_]/g, '_')">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small text-muted mb-1">Type</label>
                                        <select class="form-select form-select-sm" x-model="field.type">
                                            <option value="text">Text Input</option>
                                            <option value="textarea">Textarea</option>
                                            <option value="date">Date</option>
                                            <option value="select">Dropdown Select</option>
                                            <option value="product_search">Product Search (API)</option>
                                            <option value="agent_search">Agent Search (API)</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small text-muted mb-1">Required</label>
                                        <select class="form-select form-select-sm" x-model="field.is_required">
                                            <option value="1">Yes</option>
                                            <option value="0">No</option>
                                        </select>
                                    </div>
                                    <div class="col-md-1 d-flex align-items-end">
                                        <button type="button" class="btn btn-sm btn-outline-danger w-100" @click="removeField(index)" title="Remove Field">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                    <div class="col-md-12 mt-2" x-show="field.type === 'select'">
                                        <label class="form-label small text-muted mb-1">Options (JSON Array)</label>
                                        <input type="text" class="form-control form-control-sm" x-model="field.options" placeholder='["Option 1", "Option 2"]'>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" @click="saveTag()" :disabled="saving">
                        <span x-show="!saving">Save Tag</span>
                        <span x-show="saving"><span class="spinner-border spinner-border-sm me-2"></span>Saving...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('callTagsAdmin', () => ({
        modalInstance: null,
        tagId: null,
        saving: false,
        
        form: {
            name: '',
            level: 1,
            parent_id: null,
            is_active: 1,
            form_fields: []
        },
        
        init() {
            this.modalInstance = new bootstrap.Modal(document.getElementById('tagFormModal'));
        },
        
        openModal(id = null, name = '', level = 1, parentId = null, isActive = 1, formFields = []) {
            this.tagId = id;
            this.form.name = name;
            this.form.level = level;
            this.form.parent_id = parentId;
            this.form.is_active = isActive;
            
            // Map form fields to ensure boolean values are strings for select inputs
            if (formFields && formFields.length > 0) {
                this.form.form_fields = formFields.map(f => ({
                    ...f,
                    is_required: f.is_required ? "1" : "0"
                }));
            } else {
                this.form.form_fields = [];
            }
            
            this.modalInstance.show();
        },
        
        addField() {
            this.form.form_fields.push({
                label: '',
                name: '',
                type: 'text',
                options: '',
                is_required: "0"
            });
        },
        
        removeField(index) {
            this.form.form_fields.splice(index, 1);
        },
        
        async saveTag() {
            if (!this.form.name) {
                Swal.fire('Error', 'Tag Name is required', 'error');
                return;
            }
            
            // Basic validation for form fields
            if (this.form.level == 2) {
                for (let i = 0; i < this.form.form_fields.length; i++) {
                    const f = this.form.form_fields[i];
                    if (!f.label || !f.name || !f.type) {
                        Swal.fire('Error', 'All dynamic fields must have a Label, Key Name, and Type.', 'error');
                        return;
                    }
                }
            }
            
            this.saving = true;
            
            const url = this.tagId ? `/call-tags-admin/${this.tagId}` : `/call-tags-admin`;
            const method = this.tagId ? 'PUT' : 'POST';
            
            try {
                const res = await fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify(this.form)
                });
                
                if (res.ok) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Saved',
                        text: 'Tag saved successfully.',
                        timer: 1500,
                        showConfirmButton: false
                    });
                    this.modalInstance.hide();
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    const err = await res.json();
                    Swal.fire('Error', err.message || 'Failed to save tag', 'error');
                }
            } catch (e) {
                console.error(e);
                Swal.fire('Error', 'Network error occurred.', 'error');
            }
            
            this.saving = false;
        },
        
        async deleteTag(id) {
            const result = await Swal.fire({
                title: 'Are you sure?',
                text: "Deleting this tag will also delete all its child tags and form fields. This cannot be undone!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            });
            
            if (result.isConfirmed) {
                try {
                    const res = await fetch(`/call-tags-admin/${id}`, {
                        method: 'DELETE',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    });
                    
                    if (res.ok) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Deleted',
                            text: 'Tag has been deleted.',
                            timer: 1500,
                            showConfirmButton: false
                        });
                        setTimeout(() => window.location.reload(), 1000);
                    } else {
                        Swal.fire('Error', 'Failed to delete tag', 'error');
                    }
                } catch (e) {
                    Swal.fire('Error', 'Network error occurred.', 'error');
                }
            }
        }
    }));
});
</script>
@endsection
