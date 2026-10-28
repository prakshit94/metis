@extends('layouts.app')
@section('title', 'States & Offices')
@section('page', 'teams')
@section('content')
<div x-data="organizationTree" class="teams-management user-admin-page">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4 pb-3 border-bottom">
        <div><h1 class="h3 mb-1"><i class="bi bi-diagram-3-fill text-primary me-2" aria-hidden="true"></i>States &amp; Offices</h1><p class="text-body-secondary mb-0">Manage state teams and their offices. Agents keep state-wide visibility; all offices share their state’s order prefix.</p></div>
        @can('team-create')<button class="btn btn-primary" type="button" @click="openState()"><i class="bi bi-plus-circle me-2" aria-hidden="true"></i>Add state</button>@endcan
    </div>
    <div class="card shadow-sm mb-4"><div class="card-header bg-body-tertiary"><h2 class="h5 card-title mb-0"><i class="bi bi-buildings text-primary me-2" aria-hidden="true"></i>State &amp; Office Directory</h2></div><div class="card-body d-flex flex-wrap gap-3 align-items-end">
        <div class="flex-grow-1"><label for="organizationSearch" class="form-label small">Search states or state codes</label><input id="organizationSearch" type="search" class="form-control" maxlength="150" x-model="search" @input.debounce.300ms="page = 1; loadStates()"></div>
        <div><label for="organizationStatus" class="form-label small">State status</label><select id="organizationStatus" class="form-select" x-model="status" @change="page = 1; loadStates()"><option value="">All</option><option value="1">Active</option><option value="0">Inactive</option></select></div>
        <button type="button" class="btn btn-outline-secondary" :disabled="loading" @click="loadStates()">Refresh</button>
    </div></div>
    <div x-show="error" x-cloak class="alert alert-danger" role="alert" x-text="error"></div>
    <p x-show="loading" role="status" class="text-body-secondary">Loading states…</p>
    <p x-show="!loading && !teams.length" x-cloak class="card card-body text-body-secondary">No states found.</p>
    <template x-for="team in teams" :key="team.id">
        <section class="card shadow-sm mb-3" style="border-inline-start: 4px solid var(--bs-primary);" :aria-label="team.name">
            <div class="card-header bg-body-tertiary d-flex flex-wrap gap-2 align-items-center justify-content-between">
                <button type="button" class="btn text-start flex-grow-1 text-body" :aria-expanded="Boolean(expanded[team.id]).toString()" :aria-controls="'state-offices-' + team.id" @click="toggleState(team)">
                    <i class="bi me-2" :class="expanded[team.id] ? 'bi-chevron-down' : 'bi-chevron-right'" aria-hidden="true"></i><strong x-text="team.name"></strong>
                    <span class="badge bg-primary ms-2" x-text="team.code || 'No code'"></span>
                    <span class="badge ms-2" :class="team.is_active ? 'bg-success-subtle text-success-emphasis' : 'bg-secondary-subtle text-secondary-emphasis'" x-text="team.is_active ? 'Active' : 'Inactive'"></span>
                    <span class="d-block small text-body-secondary mt-1 ms-4"><span x-text="team.resolved_state_name"></span> · <span x-text="team.offices_count"></span> offices · <span x-text="team.members_count"></span> members</span>
                </button>
                <div class="d-flex flex-wrap gap-2">
                    @can('office-create')<button type="button" class="btn btn-sm btn-outline-primary" :disabled="!team.is_active" @click="openOffice(team)"><i class="bi bi-building-add me-1" aria-hidden="true"></i>Add office</button>@endcan
                    @can('team-edit')<button type="button" class="btn btn-sm btn-outline-secondary" @click="openState(team)" :aria-label="'Edit state ' + team.name">Edit state</button>@endcan
                    @can('team-delete')<button type="button" class="btn btn-sm btn-outline-danger" @click="removeState(team)">Delete state</button>@endcan
                </div>
            </div>
            <div class="card-body" x-show="expanded[team.id]" x-cloak :id="'state-offices-' + team.id">
                <p class="small text-body-secondary" x-show="team.description" x-text="team.description"></p>
                <p class="small text-warning-emphasis" x-show="!team.is_active">Inactive states cannot receive new office or agent assignments. Existing access and historical orders are retained.</p>
                <div class="d-flex flex-wrap gap-2 mb-3 align-items-center"><label class="small" :for="'office-search-' + team.id">Find office</label><input type="search" class="form-control form-control-sm w-auto flex-grow-1" :id="'office-search-' + team.id" maxlength="150" x-model="officeSearch[team.id]" @input.debounce.300ms="loadOffices(team.id, 1)"><button class="btn btn-sm btn-outline-secondary" type="button" :disabled="officeLoading[team.id]" @click="loadOffices(team.id, officePages[team.id] || 1)">Refresh offices</button></div>
                <p x-show="officeLoading[team.id]" role="status" class="small">Loading offices…</p>
                <p x-show="officeErrors[team.id]" class="text-danger small" role="alert" x-text="officeErrors[team.id]"></p>
                <div class="table-responsive"><table class="table table-hover align-middle mb-3"><thead><tr><th scope="col">Office</th><th scope="col">Code</th><th scope="col">Agents</th><th scope="col">Status</th><th scope="col">Actions</th></tr></thead><tbody>
                    <template x-for="office in (offices[team.id] || [])" :key="office.id"><tr>
                        <td><strong x-text="office.name"></strong><span class="d-block small text-body-secondary" x-text="[office.address_line_1 || office.address, office.address_line_2, office.village_name || office.city, office.pincode].filter(Boolean).join(' · ')"></span></td>
                        <td><span class="badge bg-secondary" x-text="office.code"></span><span class="d-block small text-body-secondary" x-text="'Order prefix: ' + team.code"></span></td>
                        <td><span x-text="office.members_count"></span> @can('user-view')<a :href="'/users?office_id=' + office.id" class="small ms-1">View users</a>@endcan</td>
                        <td><span class="badge" :class="office.is_active ? 'bg-success-subtle text-success-emphasis' : 'bg-secondary-subtle text-secondary-emphasis'" x-text="office.is_active ? 'Active' : 'Inactive'"></span></td>
                        <td><div class="d-flex flex-wrap gap-2">@can('office-edit')<button type="button" class="btn btn-sm btn-outline-secondary" @click="openOffice(team, office)" :aria-label="'Edit office ' + office.name">Edit</button>@endcan @can('office-delete')<button type="button" class="btn btn-sm btn-outline-danger" @click="removeOffice(team, office)" :aria-label="'Delete office ' + office.name">Delete</button>@endcan</div></td>
                    </tr></template>
                    <tr x-show="!officeLoading[team.id] && !(offices[team.id] || []).length"><td colspan="5" class="text-body-secondary">No offices found. Existing state agents may remain unassigned to an office.</td></tr>
                </tbody></table></div>
                <div class="d-flex justify-content-between align-items-center gap-2"><span class="small text-body-secondary" x-text="(officeTotals[team.id] || 0) + ' offices'"></span><div class="btn-group"><button type="button" class="btn btn-sm btn-outline-secondary" :disabled="officeLoading[team.id] || (officePages[team.id] || 1) <= 1" @click="loadOffices(team.id, officePages[team.id] - 1)">Previous</button><button type="button" class="btn btn-sm btn-outline-secondary" :disabled="officeLoading[team.id] || (officePages[team.id] || 1) >= (officeLastPages[team.id] || 1)" @click="loadOffices(team.id, officePages[team.id] + 1)">Next</button></div></div>
            </div>
        </section>
    </template>
    <div class="d-flex flex-wrap justify-content-between gap-2 align-items-center mb-3"><span class="small text-body-secondary" x-text="'Showing ' + from + ' to ' + to + ' of ' + total + ' states'"></span><div class="btn-group"><button type="button" class="btn btn-outline-secondary" :disabled="loading || page <= 1" @click="page--; loadStates()">Previous</button><button type="button" class="btn btn-outline-secondary" :disabled="loading || page >= lastPage" @click="page++; loadStates()">Next</button></div></div>
    <div class="modal fade" id="organizationModal" tabindex="-1" aria-labelledby="organizationModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" :class="{ 'modal-lg': kind === 'office' }"><div class="modal-content shadow"><div class="modal-header bg-body-tertiary"><h2 class="modal-title fs-5" id="organizationModalTitle" x-text="(editingId ? 'Edit ' : 'Add ') + (kind === 'office' ? 'office' : 'state')"></h2><button type="button" class="btn-close" data-bs-dismiss="modal" :disabled="saving" aria-label="Close"></button></div>
        <form @submit.prevent="save()" class="d-flex flex-column overflow-hidden"><div class="modal-body">
            <div x-show="formError" class="alert alert-danger" x-cloak role="alert" x-text="formError"></div>
            <p class="small text-primary-emphasis bg-primary-subtle border rounded p-2" x-show="kind === 'office'" x-text="'Parent state: ' + parentName + '. Orders use state code ' + parentCode + '.'"></p>
            <div class="card shadow-sm mb-3 bg-body-tertiary" style="border-inline-start: 4px solid var(--bs-primary);"><div class="card-body p-3"><h3 class="h6 fw-bold border-bottom pb-2 mb-3"><i class="bi bi-building text-primary me-2" aria-hidden="true"></i><span x-text="kind === 'office' ? 'Office Details' : 'State Details'"></span></h3><div class="row g-3"><div class="col-md-6"><label for="organizationName" class="form-label">Name <span class="text-danger">*</span></label><input id="organizationName" class="form-control" x-model="form.name" :maxlength="kind === 'office' ? 150 : 255" required></div>
            <div class="col-md-6"><label for="organizationCode" class="form-label" x-text="kind === 'office' ? 'Office code (unique within state)' : 'State code (unique order prefix)'"></label><input id="organizationCode" class="form-control text-uppercase" x-model="form.code" :maxlength="kind === 'office' ? 30 : 50" required @input="syncStateCode()" :pattern="kind === 'office' ? '[A-Za-z0-9][A-Za-z0-9_-]*' : '[A-Za-z][A-Za-z0-9]*'"></div>
            <div x-show="kind === 'state'" class="col-12"><label for="organizationState" class="form-label">State mapping</label><select id="organizationState" class="form-select" x-model="form.state_name" :required="kind === 'state'"><option value="">Select state</option><template x-for="state in states" :key="state"><option :value="state" x-text="state"></option></template></select><p class="form-text">Mapping controls existing state-wide access. Codes and mappings are protected when dependencies exist.</p></div>
            </div></div></div>
            <section x-show="kind === 'office'" class="card shadow-sm mb-3 bg-body-tertiary" style="border-inline-start: 4px solid var(--bs-warning);" aria-labelledby="officeAddressHeading">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center mb-3 pb-2 border-bottom"><i class="bi bi-geo-alt-fill text-success fs-5 me-2" aria-hidden="true"></i><h3 class="h6 card-title mb-0 fw-bold" id="officeAddressHeading">Address Details</h3></div>
                    <div class="row g-3">
                        <div class="col-md-6"><label for="officeAddressLine1" class="form-label fw-medium text-muted small">Address Line 1</label><input id="officeAddressLine1" class="form-control form-control-sm" maxlength="255" placeholder="House/Flat No., Street" x-model="form.address_line_1"></div>
                        <div class="col-md-6"><label for="officeAddressLine2" class="form-label fw-medium text-muted small">Address Line 2</label><input id="officeAddressLine2" class="form-control form-control-sm" maxlength="255" placeholder="Landmark, Area" x-model="form.address_line_2"></div>
                        <div class="col-12" @click.outside="resetVillageSearch()" @keydown.escape.stop="resetVillageSearch()">
                            <label for="officeVillageSearch" class="form-label fw-medium text-muted small">Village Search</label>
                            <div class="input-group input-group-sm"><span class="input-group-text bg-body-secondary"><i class="bi bi-search" aria-hidden="true"></i></span><input id="officeVillageSearch" class="form-control form-control-sm" placeholder="Type 3 letters to search village..." maxlength="150" x-model="villageSearchQuery" @input.debounce.300ms="searchVillages()" :aria-expanded="(villageResults.length > 0).toString()" aria-controls="officeVillageResults" autocomplete="off"></div>
                            <p x-show="villageLoading" x-cloak class="small text-body-secondary mt-1 mb-0" role="status">Searching villages…</p>
                            <p x-show="villageError" x-cloak class="small text-warning-emphasis mt-1 mb-0" role="status" x-text="villageError"></p>
                            <div id="officeVillageResults" x-show="villageResults.length" x-cloak class="list-group border rounded shadow-sm overflow-auto mt-1" style="max-height: 200px;" aria-label="Village search results">
                                <template x-for="v in villageResults" :key="v.id"><button type="button" class="list-group-item list-group-item-action py-2 px-3 text-wrap" @click="selectVillage(v)"><span class="d-flex justify-content-between align-items-center gap-2"><strong class="text-primary" x-text="v.village_name"></strong><span class="badge bg-secondary" x-text="v.pincode"></span></span><span class="d-block text-body-secondary small" x-text="[v.post_so_name ? 'PO: ' + v.post_so_name : '', v.taluka_name, v.district_name, v.state_name].filter(Boolean).join(' · ')"></span></button></template>
                            </div>
                        </div>
                        <div x-show="form.village_name" class="col-12">
                            <div class="card border border-info border-opacity-25 bg-info bg-opacity-10 shadow-sm"><div class="card-body p-3"><div class="d-flex justify-content-between align-items-center mb-2"><span class="small fw-semibold">Selected village details</span><button type="button" class="btn btn-sm btn-link p-0" @click="clearVillage()">Clear selection</button></div><div class="row g-2 small">
                                <template x-for="item in addressDetailFields" :key="item.key"><div class="col-6 col-md-4"><div class="fw-bold text-body-secondary text-uppercase small" x-text="item.label"></div><div class="fw-medium text-break" :class="item.key === 'pincode' ? 'text-primary' : 'text-body'" x-text="form[item.key] || '—'"></div></div></template>
                            </div></div></div>
                        </div>
                        <div class="col-12"><details :open="!form.village_name"><summary class="small fw-medium text-primary">Enter or adjust address details manually</summary><div class="row g-3 mt-0">
                            <div class="col-md-4"><label for="officeVillageName" class="form-label small">Village</label><input id="officeVillageName" class="form-control form-control-sm" maxlength="255" x-model="form.village_name" @input="form.village_id = null"></div>
                            <div class="col-md-4"><label for="officePostOffice" class="form-label small">Post Office</label><input id="officePostOffice" class="form-control form-control-sm" maxlength="255" x-model="form.post_office"></div>
                            <div class="col-md-4"><label for="officeTaluka" class="form-label small">Taluka</label><input id="officeTaluka" class="form-control form-control-sm" maxlength="255" x-model="form.taluka"></div>
                            <div class="col-md-4"><label for="officeDistrict" class="form-label small">District</label><input id="officeDistrict" class="form-control form-control-sm" maxlength="255" x-model="form.district"></div>
                            <div class="col-md-4"><label for="officeCity" class="form-label small">City</label><input id="officeCity" class="form-control form-control-sm" maxlength="100" x-model="form.city"></div>
                            <div class="col-md-4"><label for="officePostalState" class="form-label small">State</label><input id="officePostalState" class="form-control form-control-sm" maxlength="255" x-model="form.state"></div>
                            <div class="col-md-4"><label for="officePincode" class="form-label small">Pincode</label><input id="officePincode" class="form-control form-control-sm" maxlength="20" x-model="form.pincode"></div>
                        </div></details><p class="form-text mb-0">Village selection fills postal details, just like Users. This address does not change the office’s parent state or access mapping.</p></div>
                    </div>
                </div>
            </section>
            <div class="mb-3"><label for="organizationDescription" class="form-label">Description</label><textarea id="organizationDescription" class="form-control" rows="3" maxlength="5000" x-model="form.description"></textarea></div>
            <div class="form-check form-switch"><input id="organizationActive" type="checkbox" role="switch" class="form-check-input" x-model="form.is_active"><label for="organizationActive" class="form-check-label">Active</label></div><p class="small text-body-secondary mt-2">Inactive records remain in history. New offices and assignments require active parents. Existing logins and state visibility are unchanged.</p>
        </div><div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" :disabled="saving">Cancel</button><button type="submit" class="btn btn-primary" :disabled="saving" x-text="saving ? 'Saving…' : 'Save'"></button></div></form>
        </div></div>
    </div>
</div>
@endsection
@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    const notify = (message, type = 'success') => {
        if (window.AdminApp?.notificationManager) window.AdminApp.notificationManager.show(message, type);
        else window.alert(message);
    };
    const api = async (url, method = 'GET', body) => {
        const response = await fetch(url, {method, credentials: 'same-origin', cache: 'no-store',
            headers: {'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''},
            ...(body ? {body: JSON.stringify(body)} : {})});
        const data = await response.json().catch(() => ({}));
        if (!response.ok) throw new Error(Object.values(data.errors || {}).flat().join(' ') || data.message || 'Request failed. Refresh and try again.');
        return data;
    };
    Alpine.data('organizationTree', () => ({
        teams: [], states: [], codes: {}, search: '', status: '', page: 1, lastPage: 1, from: 0, to: 0, total: 0,
        loading: false, error: '', expanded: {}, offices: {}, officeLoading: {}, officeErrors: {}, officeSearch: {}, officePages: {}, officeLastPages: {}, officeTotals: {}, officeRequestIds: {},
        kind: 'state', editingId: null, parentId: null, parentName: '', parentCode: '', saving: false, formError: '', form: {}, deleting: false,
        villageSearchQuery: '', villageResults: [], villageLoading: false, villageError: '', villageRequestId: 0,
        addressDetailFields: [{key: 'village_name', label: 'Village'}, {key: 'post_office', label: 'Post Office'}, {key: 'taluka', label: 'Taluka'}, {key: 'district', label: 'District'}, {key: 'state', label: 'State'}, {key: 'pincode', label: 'Pincode'}],
        init() { this.loadStates(); document.getElementById('organizationModal').addEventListener('hidden.bs.modal', () => { this.formError = ''; this.resetVillageSearch(); this.form = this.baseForm(); }); },
        async loadStates() {
            const id = this.requestId = (this.requestId || 0) + 1;
            this.loading = true; this.error = '';
            try {
                const params = new URLSearchParams({page: this.page, per_page: 15, search: this.search});
                if (this.status !== '') params.set('is_active', this.status);
                const data = await api('/api/teams?' + params);
                if (id !== this.requestId) return;
                this.teams = data.data; this.states = data.states; this.codes = data.state_codes;
                this.page = data.current_page; this.lastPage = data.last_page; this.from = data.from; this.to = data.to; this.total = data.total;
            } catch (e) { if (id === this.requestId) this.error = e.message; }
            finally { if (id === this.requestId) this.loading = false; }
        },
        async toggleState(team) { this.expanded[team.id] = !this.expanded[team.id]; if (this.expanded[team.id]) await this.loadOffices(team.id, 1); },
        async loadOffices(teamId, page = 1) {
            const id = this.officeRequestIds[teamId] = (this.officeRequestIds[teamId] || 0) + 1;
            this.officeLoading[teamId] = true; this.officeErrors[teamId] = '';
            try {
                const data = await api('/api/offices?' + new URLSearchParams({team_id: teamId, page, per_page: 10, search: this.officeSearch[teamId] || ''}));
                if (id !== this.officeRequestIds[teamId]) return;
                if (!data.data.length && page > data.last_page) { await this.loadOffices(teamId, data.last_page); return; }
                this.offices[teamId] = data.data; this.officePages[teamId] = data.current_page; this.officeLastPages[teamId] = data.last_page; this.officeTotals[teamId] = data.total;
            } catch (e) { if (id === this.officeRequestIds[teamId]) this.officeErrors[teamId] = e.message; }
            finally { if (id === this.officeRequestIds[teamId]) this.officeLoading[teamId] = false; }
        },
        baseForm() { return {name: '', code: '', state_name: '', city: '', address: '', address_line_1: '', address_line_2: '', village_id: null, village_name: '', post_office: '', taluka: '', district: '', state: '', pincode: '', description: '', is_active: true}; },
        openState(team = null) { this.resetVillageSearch(); this.kind = 'state'; this.editingId = team?.id || null; this.formError = ''; this.form = {...this.baseForm(), ...(team || {})}; this.showModal(); },
        openOffice(team, office = null) { this.resetVillageSearch(); this.kind = 'office'; this.parentId = team.id; this.parentName = team.name; this.parentCode = team.code; this.editingId = office?.id || null; this.formError = ''; this.form = {...this.baseForm(), ...(office || {}), address_line_1: office?.address_line_1 ?? office?.address ?? '', state: office?.state ?? team.resolved_state_name ?? team.state_name ?? ''}; this.showModal(); },
        resetVillageSearch() { this.villageRequestId++; this.villageSearchQuery = ''; this.villageResults = []; this.villageLoading = false; this.villageError = ''; },
        async searchVillages() {
            const id = ++this.villageRequestId;
            this.villageResults = []; this.villageError = ''; this.villageLoading = false;
            const query = this.villageSearchQuery.trim();
            if (this.kind !== 'office' || query.length < 3) return;
            this.villageLoading = true;
            try {
                const data = await api('/api/villages/search?' + new URLSearchParams({q: query}));
                if (id !== this.villageRequestId) return;
                this.villageResults = data.data || [];
                if (!this.villageResults.length) this.villageError = 'No villages found. Try another search or enter the address manually.';
            } catch { if (id === this.villageRequestId) this.villageError = 'Village search is unavailable or not permitted. You can enter the address details manually.'; }
            finally { if (id === this.villageRequestId) this.villageLoading = false; }
        },
        selectVillage(v) {
            this.resetVillageSearch();
            this.form.village_id = v.id;
            this.form.village_name = v.village_name || v.name || '';
            this.form.post_office = v.post_so_name || v.post_office || '';
            this.form.taluka = v.taluka_name || v.taluka || '';
            this.form.district = v.district_name || v.district || '';
            this.form.city = v.district_name || v.district || v.city || '';
            this.form.state = v.state_name || v.state || '';
            this.form.pincode = v.pincode || '';
        },
        clearVillage() { this.resetVillageSearch(); this.form.village_id = null; this.form.village_name = ''; this.form.post_office = ''; this.form.taluka = ''; this.form.district = ''; },
        showModal() { bootstrap.Modal.getOrCreateInstance(document.getElementById('organizationModal'), {backdrop: 'static'}).show(); },
        syncStateCode() { if (this.kind === 'state' && this.codes[this.form.code.toUpperCase().trim()]) this.form.state_name = this.codes[this.form.code.toUpperCase().trim()]; },
        async save() {
            if (this.saving) return; this.saving = true; this.formError = '';
            try {
                const payload = {name: this.form.name.trim(), code: this.form.code.trim().toUpperCase(), description: this.form.description || '', is_active: Boolean(this.form.is_active)};
                if (this.kind === 'state') payload.state_name = this.form.state_name;
                else Object.assign(payload, {team_id: this.parentId, city: this.form.city || '', address: this.form.address_line_1 || '', address_line_1: this.form.address_line_1 || '', address_line_2: this.form.address_line_2 || '', village_id: this.form.village_id || null, village_name: this.form.village_name || '', post_office: this.form.post_office || '', taluka: this.form.taluka || '', district: this.form.district || '', state: this.form.state || '', pincode: this.form.pincode || ''});
                const data = await api('/api/' + (this.kind === 'state' ? 'teams' : 'offices') + (this.editingId ? '/' + this.editingId : ''), this.editingId ? 'PATCH' : 'POST', payload);
                bootstrap.Modal.getOrCreateInstance(document.getElementById('organizationModal')).hide(); notify(data.message);
                await this.loadStates(); if (this.kind === 'office') { this.expanded[this.parentId] = true; await this.loadOffices(this.parentId, 1); }
            } catch (e) { this.formError = e.message; notify(e.message, 'danger'); }
            finally { this.saving = false; }
        },
        async removeState(team) {
            if (this.deleting || !window.confirm('Delete unused state ' + team.name + '? States with dependencies cannot be deleted.')) return;
            this.deleting = true;
            try { const data = await api('/api/teams/' + team.id, 'DELETE'); notify(data.message); await this.loadStates(); }
            catch (e) { notify(e.message, 'danger'); } finally { this.deleting = false; }
        },
        async removeOffice(team, office) {
            if (this.deleting || !window.confirm('Delete unused office ' + office.name + '? Offices with users or historical orders cannot be deleted.')) return;
            this.deleting = true;
            try { const data = await api('/api/offices/' + office.id, 'DELETE'); notify(data.message); await this.loadStates(); await this.loadOffices(team.id, this.officePages[team.id] || 1); }
            catch (e) { notify(e.message, 'danger'); } finally { this.deleting = false; }
        }
    }));
});
</script>
@endpush
