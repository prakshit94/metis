@extends('layouts.app')

@section('title', 'Manage Call Tags')

@section('content')
<div class="container-fluid py-4" x-data="callTagsAdmin()">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="d-flex align-items-center gap-3">
            <div class="bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-3 d-flex align-items-center justify-content-center shadow-sm" style="width: 48px; height: 48px;">
                <i class="bi bi-tags-fill fs-4"></i>
            </div>
            <div>
                <h4 class="mb-0 fw-bold text-body">Call Tags Manager</h4>
                <p class="mb-0 text-muted small" style="font-size: 13px;">Manage call outcome categories and dynamic form fields.</p>
            </div>
        </div>
        <button type="button" class="btn btn-primary rounded-pill px-4 shadow-sm hover-shadow transition-all fw-bold" @click="openModal()">
            <i class="bi bi-plus-circle-fill me-2"></i>Add Category
        </button>
    </div>

    <!-- Stats Row -->
    <div class="row g-4 g-lg-5 g-xl-6 mb-5 mb-lg-5 mb-xl-6">
        <div class="col-xl-3 col-lg-6">
            <div class="card border border-secondary border-opacity-25 shadow-sm rounded-4 h-100">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex align-items-center">
                        <div class="bg-primary bg-opacity-10 text-primary rounded-3 d-flex align-items-center justify-content-center me-3" style="width: 48px; height: 48px;">
                            <i class="bi bi-tags-fill fs-4"></i>
                        </div>
                        <div>
                            <p class="h6 mb-0 text-muted" style="font-size: 13px;">Total Tags</p>
                            <div class="h3 mb-0 fw-bold">{{ $stats['total'] ?? 0 }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6">
            <div class="card border border-secondary border-opacity-25 shadow-sm rounded-4 h-100">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex align-items-center">
                        <div class="bg-success bg-opacity-10 text-success rounded-3 d-flex align-items-center justify-content-center me-3" style="width: 48px; height: 48px;">
                            <i class="bi bi-check-circle-fill fs-4"></i>
                        </div>
                        <div>
                            <p class="h6 mb-0 text-muted" style="font-size: 13px;">Active</p>
                            <div class="h3 mb-0 fw-bold">{{ $stats['active'] ?? 0 }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6">
            <div class="card border border-secondary border-opacity-25 shadow-sm rounded-4 h-100">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex align-items-center">
                        <div class="bg-info bg-opacity-10 text-info rounded-3 d-flex align-items-center justify-content-center me-3" style="width: 48px; height: 48px;">
                            <i class="bi bi-folder-fill fs-4"></i>
                        </div>
                        <div>
                            <p class="h6 mb-0 text-muted" style="font-size: 13px;">Level 1 Categories</p>
                            <div class="h3 mb-0 fw-bold">{{ $stats['level_1'] ?? 0 }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6">
            <div class="card border border-secondary border-opacity-25 shadow-sm rounded-4 h-100">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex align-items-center">
                        <div class="bg-danger bg-opacity-10 text-danger rounded-3 d-flex align-items-center justify-content-center me-3" style="width: 48px; height: 48px;">
                            <i class="bi bi-x-circle-fill fs-4"></i>
                        </div>
                        <div>
                            <p class="h6 mb-0 text-muted" style="font-size: 13px;">Inactive</p>
                            <div class="h3 mb-0 fw-bold">{{ $stats['inactive'] ?? 0 }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Directory Card -->
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-body border-bottom py-3 rounded-top-4">
            <div class="row align-items-center">
                <div class="col">
                    <h2 class="h5 card-title mb-0 fw-bold text-body">Call Tags Directory</h2>
                </div>
                <div class="col-auto">
                    <div class="d-flex flex-wrap gap-2 justify-content-end">
                        <div class="position-relative">
                            <input type="search" class="form-control form-control-sm" placeholder="Search tag..." x-model="search" style="width: 200px;">
                            <i class="bi bi-search position-absolute top-50 end-0 translate-middle-y me-2 text-muted"></i>
                        </div>
                        <select x-select class="form-select form-select-sm" x-model="filterStatus" style="width: 150px;">
                            <option value="">All Status</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <!-- Bulk Actions Bar -->
            <div class="bulk-actions-bar p-3 bg-primary bg-opacity-10 border-bottom border-primary border-opacity-25" x-show="selected.length > 0" x-cloak>
                <div class="d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-check-circle-fill text-primary me-2"></i>
                        <span class="fw-medium text-primary">
                            <span x-text="selected.length"></span> selected
                        </span>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-success rounded-pill px-3 shadow-sm fw-bold" @click="bulkAction('activate')"><i class="bi bi-check-circle me-1"></i>Activate</button>
                        <button class="btn btn-sm btn-warning rounded-pill px-3 shadow-sm fw-bold" @click="bulkAction('deactivate')"><i class="bi bi-pause-circle me-1"></i>Deactivate</button>
                        <button class="btn btn-sm btn-danger rounded-pill px-3 shadow-sm fw-bold" @click="bulkAction('delete')"><i class="bi bi-trash me-1"></i>Delete</button>
                        <button class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center justify-content-center px-2 rounded-circle shadow-sm" @click="selected = []" title="Clear selection">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-body-secondary bg-opacity-75">
                        <tr>
                            <th style="width:40px" class="ps-4"><input type="checkbox" class="form-check-input shadow-sm border-secondary border-opacity-25" @change="$event.isTrusted && toggleAll($event)" :checked="allSelected"></th>
                            <th>Tag Hierarchy</th>
                            <th>Level</th>
                            <th>Status</th>
                            <th>Dynamic Fields</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tags as $l1)
                            {{-- LEVEL 1 --}}
                            <tr class="bg-body-tertiary" x-show="isVisible('{{ addslashes($l1->name) }}', {{ $l1->is_active }})" :class="{ 'bg-primary bg-opacity-10': selected.includes({{ $l1->id }}) }">
                                <td class="ps-4">
                                    <div class="d-flex align-items-center">
                                        <input type="checkbox" class="form-check-input shadow-sm border-secondary border-opacity-25 me-3" value="{{ $l1->id }}" x-model.number="selected">
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
                                    <button class="btn btn-sm btn-outline-secondary border shadow-sm me-1 rounded-circle transition-all hover-shadow" style="width: 32px; height: 32px; padding: 0;" @click="openModal({{ $l1->id }}, '{{ addslashes($l1->name) }}', 1, null, {{ $l1->is_active }})" title="Edit">
                                        <i class="bi bi-pencil text-secondary"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-primary shadow-sm me-1 rounded-pill fw-bold px-3 transition-all hover-shadow" style="font-size: 11px; letter-spacing: 0.5px;" @click="openModal(null, '', 2, {{ $l1->id }}, 1)" title="Add L2 Tag">
                                        <i class="bi bi-plus-lg me-1"></i> ADD L2
                                    </button>
                                    <button class="btn btn-sm btn-outline-secondary text-danger border shadow-sm rounded-circle transition-all hover-shadow" style="width: 32px; height: 32px; padding: 0;" @click="deleteTag({{ $l1->id }})" title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            
                            @foreach($l1->children as $l2)
                                {{-- LEVEL 2 --}}
                                <tr x-show="isVisible('{{ addslashes($l2->name) }}', {{ $l2->is_active }})" :class="{ 'bg-primary bg-opacity-10': selected.includes({{ $l2->id }}) }">
                                    <td class="ps-4">
                                        <div class="d-flex align-items-center ms-4">
                                            <input type="checkbox" class="form-check-input shadow-sm border-secondary border-opacity-25 me-3" value="{{ $l2->id }}" x-model.number="selected">
                                            <i class="bi bi-arrow-return-right text-muted me-2"></i>
                                            <i class="bi bi-folder2-open text-info me-2 fs-5"></i>
                                            <span class="fw-semibold text-body-emphasis">{{ $l2->name }}</span>
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
                                        <button class="btn btn-sm btn-outline-secondary border shadow-sm me-1 rounded-circle transition-all hover-shadow" style="width: 32px; height: 32px; padding: 0;" @click="openModal({{ $l2->id }}, '{{ addslashes($l2->name) }}', 2, {{ $l2->parent_id }}, {{ $l2->is_active }}, {{ json_encode($l2->formFields) }})" title="Edit">
                                            <i class="bi bi-pencil text-secondary"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-info shadow-sm me-1 rounded-pill fw-bold px-3 transition-all hover-shadow" style="font-size: 11px; letter-spacing: 0.5px;" @click="openModal(null, '', 3, {{ $l2->id }}, 1)" title="Add L3 Tag">
                                            <i class="bi bi-plus-lg me-1"></i> ADD L3
                                        </button>
                                        <button class="btn btn-sm btn-outline-secondary text-danger border shadow-sm rounded-circle transition-all hover-shadow" style="width: 32px; height: 32px; padding: 0;" @click="deleteTag({{ $l2->id }})" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                
                                @foreach($l2->children as $l3)
                                    {{-- LEVEL 3 --}}
                                    <tr x-show="isVisible('{{ addslashes($l3->name) }}', {{ $l3->is_active }})" :class="{ 'bg-primary bg-opacity-10': selected.includes({{ $l3->id }}) }">
                                        <td class="ps-4">
                                            <div class="d-flex align-items-center ms-5" style="padding-left: 2rem;">
                                                <input type="checkbox" class="form-check-input shadow-sm border-secondary border-opacity-25 me-3" value="{{ $l3->id }}" x-model.number="selected">
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
                                            <button class="btn btn-sm btn-outline-secondary border shadow-sm me-1 rounded-circle transition-all hover-shadow" style="width: 32px; height: 32px; padding: 0;" @click="openModal({{ $l3->id }}, '{{ addslashes($l3->name) }}', 3, {{ $l3->parent_id }}, {{ $l3->is_active }})" title="Edit">
                                                <i class="bi bi-pencil text-secondary"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-secondary text-danger border shadow-sm rounded-circle transition-all hover-shadow" style="width: 32px; height: 32px; padding: 0;" @click="deleteTag({{ $l3->id }})" title="Delete">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            @endforeach
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    <div class="mb-3 mt-3"><i class="bi bi-tags display-6 text-body-tertiary"></i></div>
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
    <div class="modal fade" id="tagFormModal" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-body-tertiary border-bottom d-flex align-items-center justify-content-between p-4">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-3 d-flex align-items-center justify-content-center shadow-sm" style="width: 48px; height: 48px;">
                            <i class="bi fs-4" :class="tagId ? 'bi-pencil-square' : 'bi-plus-circle'"></i>
                        </div>
                        <div>
                            <h4 class="mb-0 fw-bold text-body"><span x-text="tagId ? 'Edit Tag' : 'Add Tag'"></span></h4>
                            <p class="mb-0 small text-muted">Configure tag details and dynamic fields</p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 bg-body-tertiary">
                    <div class="card border border-secondary border-opacity-25 shadow-sm rounded-4 bg-body mb-4">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center gap-2 pb-2 mb-3 border-bottom border-light-subtle">
                                <div class="bg-primary bg-opacity-10 text-primary rounded-2 d-flex align-items-center justify-content-center" style="width: 24px; height: 24px;">
                                    <i class="bi bi-tag fs-6"></i>
                                </div>
                                <h6 class="mb-0 fw-bold text-uppercase text-body" style="font-size: 11px; letter-spacing: 1px;">Tag Configuration</h6>
                            </div>
                            <div class="row g-4">
                                <div class="col-md-12">
                                    <label class="form-label fw-bold text-muted text-uppercase" style="font-size: 10px; letter-spacing: 0.5px;">Tag Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" x-model="form.name" placeholder="e.g. Sales Enquiry">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold text-muted text-uppercase" style="font-size: 10px; letter-spacing: 0.5px;">Level</label>
                                    <input type="text" class="form-control bg-body-secondary" x-model="form.level" readonly>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold text-muted text-uppercase" style="font-size: 10px; letter-spacing: 0.5px;">Status</label>
                                    <select x-select class="form-select" x-model="form.is_active">
                                        <option value="1">Active</option>
                                        <option value="0">Inactive</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Dynamic Form Builder for Level 2 only --}}
                    <template x-if="form.level == 2">
                        <div class="card border border-secondary border-opacity-25 shadow-sm rounded-4 bg-body mb-4">
                            <div class="card-body p-4">
                                <div class="d-flex justify-content-between align-items-center pb-2 mb-3 border-bottom border-light-subtle">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="bg-primary bg-opacity-10 text-primary rounded-2 d-flex align-items-center justify-content-center" style="width: 24px; height: 24px;">
                                            <i class="bi bi-ui-radios fs-6"></i>
                                        </div>
                                        <h6 class="mb-0 fw-bold text-uppercase text-body" style="font-size: 11px; letter-spacing: 1px;">Dynamic Form Fields</h6>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-primary rounded-pill fw-bold px-3 transition-all hover-shadow" @click="addField()">
                                        <i class="bi bi-plus-lg"></i> Add Field
                                    </button>
                                </div>
                                
                                <template x-if="form.form_fields.length === 0">
                                    <div class="text-muted small fst-italic py-2 text-center my-3 bg-body-secondary rounded-3 p-3 border border-light-subtle">No dynamic fields defined. Add fields to capture extra data when this tag is selected.</div>
                                </template>
                                
                                <div class="d-flex flex-column gap-3">
                                    <template x-for="(field, index) in form.form_fields" :key="index">
                                        <div class="row g-3 p-3 border rounded-3 bg-body-secondary position-relative">
                                            <div class="col-md-3">
                                                <label class="form-label fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.5px;">Label</label>
                                                <input type="text" class="form-control form-control-sm bg-body" x-model="field.label" placeholder="e.g. Select Product">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.5px;">Key Name</label>
                                                <input type="text" class="form-control form-control-sm bg-body font-monospace text-primary" x-model="field.name" placeholder="e.g. search_product" @input="field.name = field.name.toLowerCase().replace(/[^a-z0-9_]/g, '_')">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.5px;">Type</label>
                                                <select x-select class="form-select form-select-sm bg-body" x-model="field.type">
                                                    <option value="text">Text Input</option>
                                                    <option value="textarea">Textarea</option>
                                                    <option value="date">Date</option>
                                                    <option value="select">Dropdown Select</option>
                                                    <option value="product_search">Product Search (API)</option>
                                                    <option value="agent_search">Agent Search (API)</option>
                                                </select>
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.5px;">Required</label>
                                                <select x-select class="form-select form-select-sm bg-body" x-model="field.is_required">
                                                    <option value="1">Yes</option>
                                                    <option value="0">No</option>
                                                </select>
                                            </div>
                                            <div class="col-md-1 d-flex align-items-end">
                                                <button type="button" class="btn btn-sm btn-outline-danger w-100 rounded-3" @click="removeField(index)" title="Remove Field">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                            <div class="col-md-12 mt-2" x-show="field.type === 'select'">
                                                <label class="form-label fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.5px;">Options (JSON Array)</label>
                                                <input type="text" class="form-control form-control-sm bg-body font-monospace" x-model="field.options" placeholder='["Option 1", "Option 2"]'>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
                <div class="modal-footer bg-body-tertiary border-top p-4 d-flex justify-content-end gap-3">
                    <button type="button" class="btn text-muted fw-bold text-uppercase" style="font-size: 11px; letter-spacing: 1px;" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm" @click="saveTag()" :disabled="saving">
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
        
        selected: [],
        search: '',
        filterStatus: '',
        
        form: {
            name: '',
            level: 1,
            parent_id: null,
            is_active: 1,
            form_fields: []
        },
        
        get allSelected() {
            const visibleCheckboxes = Array.from(document.querySelectorAll('.table tbody tr:not([style*="display: none"]) input[type="checkbox"]'));
            if (visibleCheckboxes.length === 0) return false;
            return visibleCheckboxes.every(cb => this.selected.includes(parseInt(cb.value)));
        },

        toggleAll(e) {
            const visibleCheckboxes = Array.from(document.querySelectorAll('.table tbody tr:not([style*="display: none"]) input[type="checkbox"]'));
            if (e.target.checked) {
                const visibleIds = visibleCheckboxes.map(cb => parseInt(cb.value));
                this.selected = [...new Set([...this.selected, ...visibleIds])];
            } else {
                const visibleIds = visibleCheckboxes.map(cb => parseInt(cb.value));
                this.selected = this.selected.filter(id => !visibleIds.includes(id));
            }
        },

        isVisible(name, isActive) {
            const matchesSearch = name.toLowerCase().includes(this.search.toLowerCase());
            let matchesStatus = true;
            if (this.filterStatus === 'active') matchesStatus = isActive == 1;
            if (this.filterStatus === 'inactive') matchesStatus = isActive == 0;
            return matchesSearch && matchesStatus;
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
        },
        
        async bulkAction(action) {
            if (!this.selected.length) return;
            if (action === 'delete') {
                const result = await Swal.fire({
                    title: 'Are you sure?',
                    text: `You are about to delete ${this.selected.length} tags. This cannot be undone!`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, delete them!'
                });
                if (!result.isConfirmed) return;
            }

            try {
                const res = await fetch('/call-tags-admin/bulk-action', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ action: action, ids: this.selected })
                });

                if (res.ok) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: 'Bulk action completed.',
                        timer: 1500,
                        showConfirmButton: false
                    });
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    const err = await res.json();
                    Swal.fire('Error', err.message || 'Bulk action failed.', 'error');
                }
            } catch (e) {
                console.error(e);
                Swal.fire('Error', 'Network error occurred.', 'error');
            }
        }
    }));
});
</script>
@endsection
