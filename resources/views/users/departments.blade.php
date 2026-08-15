@extends('layouts.app')

@section('title', '🏢 Departments')
@section('page', 'departments')

@section('content')
<div class="user-management" x-data="departmentsTable">
    <div class="d-flex justify-content-between align-items-center mb-4 mb-lg-5 mb-xl-6">
        <div>
            <h1 class="h3 mb-0"><i class="bi bi-diagram-3-fill text-primary me-2"></i>Departments</h1>
            <p class="text-muted mb-0">Manage organizational structure and departments</p>
        </div>
        <div class="d-flex gap-2">
            @can('department-create')
            <button type="button" class="btn btn-primary" @click="openCreate()">
                <i class="bi bi-plus-circle me-2"></i>Add Department
            </button>
            @endcan
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="row align-items-center g-3">
                <div class="col">
                    <h2 class="h5 card-title mb-0">Department Directory</h2>
                </div>
                <div class="col-auto">
                    <div class="d-flex flex-wrap gap-2 justify-content-end">
                        <button class="btn btn-sm btn-outline-secondary" type="button" @click="loadItems()" :disabled="isLoading" title="Refresh">
                            <i class="bi bi-arrow-clockwise"></i> Refresh
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col" class="ps-4">ID</th>
                            <th scope="col">Name</th>
                            <th scope="col">Manager</th>
                            <th scope="col">Status</th>
                            <th scope="col">Created At</th>
                            <th style="width: 120px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-if="isLoading">
                            <tr>
                                <td colspan="6" class="text-center py-4">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <template x-if="!isLoading && items.length === 0">
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    No departments found.
                                </td>
                            </tr>
                        </template>
                        <template x-for="item in items" :key="item.id">
                            <tr>
                                <td class="ps-4" x-text="item.id"></td>
                                <td>
                                    <div class="fw-medium" x-text="item.name"></div>
                                </td>
                                <td>
                                    <span x-text="item.manager ? item.manager.name : '—'"></span>
                                </td>
                                <td>
                                    <span class="badge" :class="item.is_active ? 'bg-success' : 'bg-danger'" x-text="item.is_active ? 'Active' : 'Inactive'"></span>
                                </td>
                                <td x-text="new Date(item.created_at).toLocaleDateString()"></td>
                                <td>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Actions">
                                            <i class="bi bi-three-dots"></i>
                                        </button>
                                        <ul class="dropdown-menu">
                                            @can('department-edit')
                                            <li>
                                                <a class="dropdown-item" href="#" @click.prevent="editItem(item)">
                                                    <i class="bi bi-pencil me-2"></i>Edit
                                                </a>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            @endcan
                                            @can('department-delete')
                                            <li>
                                                <a class="dropdown-item text-danger" href="#" @click.prevent="deleteItem(item.id)">
                                                    <i class="bi bi-trash me-2"></i>Delete
                                                </a>
                                            </li>
                                            @endcan
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-between align-items-center p-3 border-top" x-show="totalItems > 0" style="display: none;">
                <div class="text-muted small">
                    Showing <span x-text="pageFrom"></span> to
                    <span x-text="pageTo"></span> of
                    <span x-text="totalItems"></span> results
                </div>
                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <li class="page-item" :class="{ 'disabled': currentPage === 1 }">
                            <a class="page-link" href="#" @click.prevent="goToPage(currentPage - 1)">Previous</a>
                        </li>
                        <template x-for="(page, index) in visiblePages" :key="`${page}-${index}`">
                            <li class="page-item" :class="{ 'active': page === currentPage }">
                                <template x-if="page === '...'">
                                    <span class="page-link px-2 text-muted">...</span>
                                </template>
                                <template x-if="page !== '...'">
                                    <a class="page-link" href="#" @click.prevent="goToPage(page)" x-text="page"></a>
                                </template>
                            </li>
                        </template>
                        <li class="page-item" :class="{ 'disabled': currentPage === totalPages }">
                            <a class="page-link" href="#" @click.prevent="goToPage(currentPage + 1)">Next</a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="departmentModal" tabindex="-1" aria-labelledby="departmentModalLabel">
    <div class="modal-dialog modal-dialog-centered" x-data="departmentForm">
        <form class="modal-content" @submit.prevent="saveItem()">
            <div class="modal-header">
                <h5 class="modal-title d-flex align-items-center" id="departmentModalLabel">
                    <i class="bi bi-diagram-3-fill text-primary me-2"></i>
                    <span x-text="editingId ? 'Edit Department' : 'Create Department'"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger" x-show="error" x-text="error" style="display: none;"></div>
                
                <div class="form-floating mb-3">
                    <input type="text" class="form-control" id="deptName" x-model="form.name" required placeholder="e.g. Engineering">
                    <label for="deptName">Department Name <span class="text-danger">*</span></label>
                </div>
                
                <div class="form-floating mb-3">
                    <select class="form-select" id="deptManager" x-model="form.manager_id">
                        <option value="">None</option>
                        <template x-for="user in users" :key="user.id">
                            <option :value="user.id" x-text="user.name"></option>
                        </template>
                    </select>
                    <label for="deptManager">Department Head / Manager</label>
                </div>

                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" id="deptActive" x-model="form.is_active">
                    <label class="form-check-label" for="deptActive">Active Status</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary px-4" :disabled="saving">
                    <span x-show="saving" class="spinner-border spinner-border-sm me-2"></span>
                    <span x-text="editingId ? 'Save Changes' : 'Create'"></span>
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        let modalInstance = null;
        
        Alpine.data('departmentsTable', () => ({
            items: [],
            isLoading: true,
            currentPage: 1,
            totalPages: 1,
            totalItems: 0,
            perPage: 15,
            
            init() {
                this.loadItems();
                modalInstance = new bootstrap.Modal(document.getElementById('departmentModal'));
                
                window.addEventListener('department-saved', () => {
                    this.loadItems();
                    if(modalInstance) modalInstance.hide();
                });
            },
            
            async loadItems() {
                this.isLoading = true;
                try {
                    const res = await fetch(`/api/departments?page=${this.currentPage}&per_page=${this.perPage}`);
                    if (res.ok) {
                        const json = await res.json();
                        this.items = json.data || [];
                        this.currentPage = json.current_page || 1;
                        this.totalPages = json.last_page || 1;
                        this.totalItems = json.total || 0;
                    }
                } catch(e) {
                    console.error('Failed to load departments', e);
                }
                this.isLoading = false;
            },
            
            goToPage(page) {
                if (page >= 1 && page <= this.totalPages) {
                    this.currentPage = page;
                    this.loadItems();
                }
            },
            
            get visiblePages() {
                const delta = 2;
                const range = [];
                for (let i = Math.max(2, this.currentPage - delta); i <= Math.min(this.totalPages - 1, this.currentPage + delta); i++) {
                    range.push(i);
                }
                const result = [];
                if (this.currentPage - delta > 2) result.push(1, '...');
                else result.push(1);
                result.push(...range);
                if (this.currentPage + delta < this.totalPages - 1) result.push('...', this.totalPages);
                else if (this.totalPages > 1) result.push(this.totalPages);
                return result.filter((v, i, a) => a.indexOf(v) === i && (typeof v === 'string' || v <= this.totalPages));
            },
            
            get pageFrom() {
                return this.totalItems === 0 ? 0 : (this.currentPage - 1) * this.perPage + 1;
            },
            
            get pageTo() {
                return Math.min(this.currentPage * this.perPage, this.totalItems);
            },
            
            openCreate() {
                window.dispatchEvent(new CustomEvent('open-department-modal', { detail: null }));
                modalInstance.show();
            },
            
            editItem(item) {
                window.dispatchEvent(new CustomEvent('open-department-modal', { detail: item }));
                modalInstance.show();
            },
            
            async deleteItem(id) {
                const result = await Swal.fire({
                    title: 'Delete Department?',
                    text: 'Are you sure you want to delete this department?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    confirmButtonText: 'Yes, delete it'
                });
                
                if (!result.isConfirmed) return;
                
                try {
                    const res = await fetch(`/api/departments/${id}`, {
                        method: 'DELETE',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    });
                    if(res.ok) {
                        this.loadItems();
                    } else {
                        Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to delete department.' });
                    }
                } catch(e) {
                    Swal.fire({ icon: 'error', title: 'Error', text: 'An unexpected error occurred.' });
                }
            }
        }));
        
        Alpine.data('departmentForm', () => ({
            editingId: null,
            saving: false,
            error: null,
            users: [],
            form: {
                name: '',
                manager_id: '',
                is_active: true
            },
            
            init() {
                this.loadUsers();
                window.addEventListener('open-department-modal', (e) => {
                    this.error = null;
                    if (e.detail) {
                        this.editingId = e.detail.id;
                        this.form.name = e.detail.name;
                        this.form.manager_id = e.detail.manager_id || '';
                        this.form.is_active = e.detail.is_active === undefined ? true : !!e.detail.is_active;
                    } else {
                        this.editingId = null;
                        this.form.name = '';
                        this.form.manager_id = '';
                        this.form.is_active = true;
                    }
                });
            },
            
            async loadUsers() {
                try {
                    // Fetch users for manager dropdown (could be a specialized endpoint, but we'll use /api/users if available)
                    const res = await fetch('/api/users?per_page=100');
                    if (res.ok) {
                        const json = await res.json();
                        this.users = json.data || json;
                    }
                } catch(e) {}
            },
            
            async saveItem() {
                this.saving = true;
                this.error = null;
                
                try {
                    const method = this.editingId ? 'PUT' : 'POST';
                    const url = this.editingId ? `/api/departments/${this.editingId}` : '/api/departments';
                    
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
                        window.dispatchEvent(new CustomEvent('department-saved'));
                    } else {
                        const err = await res.json();
                        this.error = err.message || 'Validation error.';
                    }
                } catch(e) {
                    this.error = 'Network error saving department.';
                }
                
                this.saving = false;
            }
        }));
    });
</script>
@endpush
@endsection
