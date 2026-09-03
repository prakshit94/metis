@extends('layouts.app')

@section('title', 'Teams (State/LOB)')
@section('page', 'teams')

@section('content')
<div class="teams-management" x-data="teamTable">
    <div class="d-flex justify-content-between align-items-center mb-4 mb-lg-5 mb-xl-6">
        <div>
            <h1 class="h3 mb-0"><i class="bi bi-buildings-fill text-primary me-2"></i>Teams (State / LOB)</h1>
            <p class="text-muted mb-0">Manage isolation segments for Line of Business and State operations</p>
        </div>
        <div class="d-flex gap-2">
            @can('team-create')
            <button type="button" class="btn btn-primary" @click="openCreateTeam()">
                <i class="bi bi-plus-circle me-2"></i>Add Team
            </button>
            @endcan
        </div>
    </div>

    <!-- Teams Table -->
    <div class="card">
        <div class="card-header">
            <div class="row align-items-center">
                <div class="col">
                    <h2 class="h5 card-title mb-0">Teams Directory</h2>
                </div>
                <div class="col-auto">
                    <div class="d-flex flex-wrap gap-2 justify-content-end">
                        <div class="position-relative">
                            <input type="search" class="form-control form-control-sm" placeholder="Search teams..." x-model="searchQuery" @input="filterTeams()" style="width: 200px;">
                            <i class="bi bi-search position-absolute top-50 end-0 translate-middle-y me-2 text-muted"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive" style="min-height: 350px;">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col">ID</th>
                            <th scope="col">Name</th>
                            <th scope="col">Code</th>
                            <th scope="col">Description</th>
                            <th scope="col">Status</th>
                            <th style="width: 120px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="team in teams" :key="team.id">
                            <tr>
                                <td x-text="team.id"></td>
                                <td class="fw-bold" x-text="team.name"></td>
                                <td><span class="badge bg-secondary" x-text="team.code || '—'"></span></td>
                                <td x-text="team.description || '—'"></td>
                                <td>
                                    <span class="badge px-2 py-1" :class="team.is_active ? 'bg-success-subtle text-success-emphasis' : 'bg-secondary-subtle text-secondary-emphasis'" x-text="team.is_active ? 'Active' : 'Inactive'"></span>
                                </td>
                                <td>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="bi bi-three-dots"></i></button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                            @can('team-edit')
                                            <li><a class="dropdown-item" href="#" @click.prevent="editTeam(team)"><i class="bi bi-pencil me-2"></i>Edit</a></li>
                                            @endcan
                                            @can('team-delete')
                                            <li><hr class="dropdown-divider"></li>
                                            <li><a class="dropdown-item text-danger" href="#" @click.prevent="deleteTeam(team)"><i class="bi bi-trash me-2"></i>Delete</a></li>
                                            @endcan
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="isLoading" style="display: none;">
                            <td colspan="6" class="text-center py-5">
                                <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                                <span class="text-muted">Loading teams...</span>
                            </td>
                        </tr>
                        <tr x-show="!isLoading && teams.length === 0" style="display: none;">
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="bi bi-buildings fs-1 d-block mb-2"></i>
                                <p class="mb-0">No teams found.</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div class="d-flex justify-content-between align-items-center p-3">
                <div class="text-muted">
                    Showing <span x-text="pageFrom"></span> to <span x-text="pageTo"></span> of <span x-text="totalTeams"></span> results
                </div>
                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <li class="page-item" :class="{ 'disabled': currentPage === 1 }">
                            <a class="page-link" href="#" @click.prevent="goToPage(currentPage - 1)">Previous</a>
                        </li>
                        <li class="page-item" :class="{ 'disabled': currentPage === totalPages }">
                            <a class="page-link" href="#" @click.prevent="goToPage(currentPage + 1)">Next</a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
    </div>

    <!-- Add / Edit Modal -->
    <div class="modal fade" id="teamModal" tabindex="-1" aria-labelledby="teamModalLabel" aria-hidden="true" x-data="teamForm">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="teamModalLabel" x-text="editingTeamId ? 'Edit Team' : 'Add Team'"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form @submit.prevent="saveTeam" id="saveTeamForm">
                        <div class="mb-3">
                            <label class="form-label fw-medium text-muted small">Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" x-model="form.name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-medium text-muted small">Code (Unique)</label>
                            <input type="text" class="form-control" x-model="form.code">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-medium text-muted small">Description</label>
                            <textarea class="form-control" rows="3" x-model="form.description"></textarea>
                        </div>
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="activeSwitch" x-model="form.is_active">
                                <label class="form-check-label fw-medium small" for="activeSwitch">Active</label>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" form="saveTeamForm" class="btn btn-primary" :disabled="saving">
                        <span x-show="saving" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                        <i class="bi bi-save me-2" x-show="!saving"></i><span x-text="editingTeamId ? 'Update' : 'Save'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('teamTable', () => ({
        teams: [],
        isLoading: false,
        searchQuery: '',
        currentPage: 1,
        itemsPerPage: 15,
        totalTeams: 0,
        pageFrom: 0,
        pageTo: 0,
        totalPages: 1,
        
        init() {
            this.loadTeams();
        },

        async loadTeams() {
            this.isLoading = true;
            try {
                let url = `/api/teams?page=${this.currentPage}&per_page=${this.itemsPerPage}`;
                if (this.searchQuery) url += `&search=${encodeURIComponent(this.searchQuery)}`;

                const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                const data = await res.json();
                
                this.teams = data.data;
                this.totalTeams = data.total;
                this.currentPage = data.current_page;
                this.pageFrom = data.from || 0;
                this.pageTo = data.to || 0;
                this.totalPages = data.last_page;
            } catch (err) {
                if (typeof showToast !== 'undefined') {
                    showToast('Failed to load teams.', 'danger');
                } else {
                    alert('Failed to load teams.');
                }
            } finally {
                this.isLoading = false;
            }
        },
        
        filterTeams() {
            this.currentPage = 1;
            this.loadTeams();
        },

        goToPage(page) {
            if (page >= 1 && page <= this.totalPages) {
                this.currentPage = page;
                this.loadTeams();
            }
        },

        openCreateTeam() {
            const formComp = Alpine.$data(document.querySelector('[x-data="teamForm"]'));
            if (formComp) {
                formComp.resetForm();
                new bootstrap.Modal(document.getElementById('teamModal')).show();
            }
        },

        editTeam(team) {
            const formComp = Alpine.$data(document.querySelector('[x-data="teamForm"]'));
            if (formComp) {
                formComp.resetForm();
                formComp.editingTeamId = team.id;
                formComp.form.name = team.name;
                formComp.form.code = team.code || '';
                formComp.form.description = team.description || '';
                formComp.form.is_active = !!team.is_active;
                new bootstrap.Modal(document.getElementById('teamModal')).show();
            }
        },

        async deleteTeam(team) {
            if (!confirm(`Are you sure you want to delete team: ${team.name}?`)) return;
            try {
                const res = await fetch(`/api/teams/${team.id}`, {
                    method: 'DELETE',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content }
                });
                
                if (res.ok) {
                    if (typeof showToast !== 'undefined') showToast(`Team ${team.name} deleted successfully.`, 'success');
                    this.loadTeams();
                } else {
                    const data = await res.json();
                    throw new Error(data.message || 'Delete failed.');
                }
            } catch (err) {
                if (typeof showToast !== 'undefined') showToast(err.message, 'danger');
                else alert(err.message);
            }
        }
    }));

    Alpine.data('teamForm', () => ({
        editingTeamId: null,
        saving: false,
        form: {
            name: '',
            code: '',
            description: '',
            is_active: true
        },

        resetForm() {
            this.editingTeamId = null;
            this.form = { name: '', code: '', description: '', is_active: true };
        },

        async saveTeam() {
            this.saving = true;
            try {
                const url = this.editingTeamId ? `/api/teams/${this.editingTeamId}` : '/api/teams';
                const method = this.editingTeamId ? 'PUT' : 'POST';
                
                const res = await fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                    },
                    body: JSON.stringify(this.form)
                });
                
                const data = await res.json();
                if (!res.ok) {
                    const errorMsg = data.errors ? Object.values(data.errors).flat().join(' ') : (data.message || 'Failed to save.');
                    throw new Error(errorMsg);
                }
                
                if (typeof showToast !== 'undefined') showToast(data.message || 'Saved successfully.', 'success');
                
                const modalEl = document.getElementById('teamModal');
                const modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) modal.hide();
                
                const tableComp = Alpine.$data(document.querySelector('[x-data="teamTable"]'));
                if (tableComp) tableComp.loadTeams();
                
            } catch (err) {
                if (typeof showToast !== 'undefined') showToast(err.message, 'danger');
                else alert(err.message);
            } finally {
                this.saving = false;
            }
        }
    }));
});
</script>
@endpush
