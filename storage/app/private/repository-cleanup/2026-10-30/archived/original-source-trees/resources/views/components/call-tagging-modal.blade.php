<style>
    #callTaggingModal .form-label { font-size: .8125rem !important; letter-spacing: normal !important; text-transform: none !important; color: var(--bs-body-color) !important; }
    #callTaggingModal .form-control, #callTaggingModal .form-select { font-size: .875rem !important; min-height: 2.5rem; color: var(--bs-body-color); background-color: var(--bs-body-bg); }
    #callTaggingModal .form-control::placeholder { color: var(--bs-secondary-color); opacity: 1; }
    #callTaggingModal .card { z-index: auto !important; background-color: var(--bs-body-bg) !important; }
    #callTaggingModal [data-searchable-select] > .dropdown-menu { position: static !important; float: none; margin-top: .5rem; }
    #callTaggingModal .call-choice-panel { background: var(--bs-body-bg); color: var(--bs-body-color); }
    #callTaggingModal .call-choice-list { max-height: 12rem; overflow-y: auto; overscroll-behavior: contain; }
    #callTaggingModal .call-choice-panel .dropdown-item { color: var(--bs-body-color); white-space: normal; }
    #callTaggingModal .call-choice-panel .dropdown-item:hover, #callTaggingModal .call-choice-panel .dropdown-item:focus-visible { background: var(--bs-secondary-bg); color: var(--bs-emphasis-color); }
    #callTaggingModal .call-choice-panel .dropdown-item[aria-pressed="true"] { background: var(--bs-primary-bg-subtle); color: var(--bs-primary-text-emphasis); }
    #callTaggingModal .call-selected-item { background: var(--bs-secondary-bg); color: var(--bs-body-color); max-width: 100%; white-space: normal; text-align: left; }
</style>
<div class="modal fade" id="callTaggingModal" tabindex="-1" aria-labelledby="callTaggingModalLabel" x-data="callTaggingApp()" @open-call-tagging-modal.window="openModal($event.detail.customerId)" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            {{-- GLOSSY STYLE HEADER --}}
            <div class="modal-header bg-body-tertiary border-bottom d-flex align-items-center justify-content-between p-4">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-3 d-flex align-items-center justify-content-center shadow-sm" style="width: 48px; height: 48px;">
                        <i class="bi bi-headset fs-4"></i>
                    </div>
                    <div>
                        <h4 id="callTaggingModalLabel" class="mb-0 fw-bold text-body">Log Call Details</h4>
                        <p class="mb-0 small text-muted">Record outcomes and interactions</p>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" @click="resetForm()" :disabled="submitting"></button>
            </div>
            
            <div class="modal-body p-4 bg-body-tertiary">
                <div class="alert alert-danger" role="alert" x-show="error" x-cloak><span x-text="error"></span><button type="button" class="btn btn-sm btn-outline-danger ms-2" x-show="!fieldsReady && !submitting" @click="selectedL2 ? selectL2(selectedL2) : selectedL1 ? selectL1(selectedL1) : fetchL1Tags()">Reload choices</button></div>
                
                {{-- LEVEL 1 CARD --}}
                <div class="card mb-3 border border-secondary border-opacity-25 shadow-sm rounded-4 bg-body-secondary" style="z-index: 30;">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center gap-2 pb-2 mb-3 border-bottom">
                            <div class="bg-primary bg-opacity-10 text-primary rounded-2 d-flex align-items-center justify-content-center" style="width: 24px; height: 24px;">
                                <i class="bi bi-diagram-3 fs-6"></i>
                            </div>
                            <h6 class="mb-0 fw-bold text-uppercase text-body" style="font-size: 11px; letter-spacing: 1px;">Primary Category</h6>
                        </div>
                        
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">Primary Reason for Call *</label>
                                <div class="d-flex align-items-center gap-2">
                                    <x-searchable-select model="selectedL1" label="Primary reason for call">
<select x-ref="native" tabindex="-1" aria-hidden="true" class="visually-hidden" style="font-size: 12px;"  x-model="selectedL1" :disabled="loadingL1 || submitting">
                                        <option value="">Select Call Category...</option>
                                        <template x-for="tag in l1Tags" :key="tag.id">
                                            <option :value="tag.id" x-text="tag.name"></option>
                                        </template>
                                    </select>
</x-searchable-select>
                                    <template x-if="loadingL1">
                                        <span class="spinner-border spinner-border-sm text-primary"></span>
                                    </template>
                                </div>
                                <template x-if="selectedL1Data?.description">
                                    <div class="mt-2 small text-muted fst-italic">
                                        <i class="bi bi-info-circle me-1 text-primary"></i> <span x-text="selectedL1Data.description"></span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- LEVEL 2 CARD & DYNAMIC FIELDS --}}
                <div class="card mb-3 border border-secondary border-opacity-25 shadow-sm rounded-4 bg-body-secondary" style="z-index: 20;" x-show="selectedL1" x-transition>
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center gap-2 pb-2 mb-3 border-bottom">
                            <div class="bg-info bg-opacity-10 text-info rounded-2 d-flex align-items-center justify-content-center" style="width: 24px; height: 24px;">
                                <i class="bi bi-list-nested fs-6"></i>
                            </div>
                            <h6 class="mb-0 fw-bold text-uppercase text-body" style="font-size: 11px; letter-spacing: 1px;">Sub Category & Details</h6>
                        </div>
                        
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">Specific Subject *</label>
                                <div class="d-flex align-items-center gap-2">
                                    <template x-if="l2Tags.length > 0">
                                        <x-searchable-select model="selectedL2" label="Specific call subject">
<select x-ref="native" tabindex="-1" aria-hidden="true" class="visually-hidden" style="font-size: 12px;"  x-model="selectedL2" :disabled="loadingL2 || submitting">
                                            <option value="">Select Sub Category...</option>
                                            <template x-for="tag in l2Tags" :key="tag.id">
                                                <option :value="tag.id" x-text="tag.name"></option>
                                            </template>
                                        </select>
</x-searchable-select>
                                    </template>
                                    <template x-if="loadingL2">
                                        <span class="spinner-border spinner-border-sm text-info"></span>
                                    </template>
                                    <template x-if="!loadingL2 && l2Tags.length === 0">
                                        <span class="text-muted small fst-italic">No sub-categories available.</span>
                                    </template>
                                </div>
                                <template x-if="selectedL2Data?.description">
                                    <div class="mt-2 small text-muted fst-italic">
                                        <i class="bi bi-info-circle me-1 text-info"></i> <span x-text="selectedL2Data.description"></span>
                                    </div>
                                </template>
                            </div>

                            {{-- Dynamic Fields integrated into Level 2 Card --}}
                            <template x-if="formFields.length > 0">
                                <div class="col-md-12 mt-4">
                                    <h6 class="fw-bold text-muted text-uppercase mb-3" style="font-size: 10px; letter-spacing: 1px;">Additional Details</h6>
                                    <div class="row g-3">
                                        <template x-for="field in formFields" :key="selectedL2 + ':' + field.id">
                                            <div class="col-md-6">
                                                <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">
                                                    <span x-text="field.label"></span>
                                                    <span x-show="field.is_required"> *</span>
                                                </label>
                                                
                                                <template x-if="['text', 'date', 'datetime-local', 'number'].includes(field.type)">
                                                    <input :type="field.type" class="form-control form-control-sm fw-semibold" style="font-size: 12px;" :aria-label="field.label" x-model="formData[field.name]" :disabled="submitting" :required="field.is_required" :placeholder="field.placeholder || ''">
                                                </template>

                                                <template x-if="field.type === 'select'">
                                                    <x-searchable-select model="formData[field.name]" label="Call detail option">
<select x-ref="native" tabindex="-1" aria-hidden="true" class="visually-hidden" style="font-size: 12px;" :aria-label="field.label" x-model="formData[field.name]" :disabled="submitting" :required="field.is_required">
                                                        <option value="">Select option...</option>
                                                        <template x-for="opt in fieldOptions(field)" :key="opt">
                                                            <option :value="opt" x-text="opt"></option>
                                                        </template>
                                                    </select>
</x-searchable-select>
                                                </template>

                                                <template x-if="field.type === 'multi_select'">
                                                    <div x-data="{ open: false, query: '', get selectedItems() { return Array.isArray(formData[field.name]) ? formData[field.name] : []; }, get choices() { return fieldOptions(field).filter(option => String(option).toLowerCase().includes(this.query.trim().toLowerCase())); }, toggle(option) { if(submitting) return; let values = [...this.selectedItems]; formData[field.name] = values.includes(option) ? values.filter(value => value !== option) : [...values, option]; } }"
                                                         @click.outside="open = false" @focusout="if (!$el.contains($event.relatedTarget)) open = false"
                                                         @keydown.escape="if(open) { $event.stopPropagation(); $event.preventDefault(); open = false; $refs.trigger.focus(); }">
                                                        <button type="button" class="form-select text-start" x-ref="trigger" :aria-label="field.label" :aria-expanded="open" :disabled="submitting" @click="open = !open; if(open) { query = ''; $nextTick(() => $refs.query.focus()); }" @keydown.arrow-down.stop.prevent="open = true; $nextTick(() => $refs.query.focus())">
                                                            <span x-text="selectedItems.length ? selectedItems.length + ' selected' : 'Select options…'"></span>
                                                        </button>
                                                        <div class="d-flex flex-wrap gap-1 mt-2" x-show="selectedItems.length">
                                                            <template x-for="item in selectedItems" :key="item"><button type="button" class="btn btn-sm call-selected-item" :disabled="submitting" :aria-label="'Remove ' + item" @click="toggle(item)"><span x-text="item"></span><i class="bi bi-x ms-1" aria-hidden="true"></i></button></template>
                                                        </div>
                                                        <div class="call-choice-panel border rounded-3 p-2 mt-2" x-show="open" x-cloak
                                                             @keydown.arrow-down.stop.prevent="const buttons = [...$el.querySelectorAll('button:not(:disabled)')]; buttons[Math.min(buttons.indexOf($event.target) + 1, buttons.length - 1)]?.focus()"
                                                             @keydown.arrow-up.stop.prevent="const buttons = [...$el.querySelectorAll('button:not(:disabled)')]; const index = buttons.indexOf($event.target); if(index <= 0) $refs.query.focus(); else buttons[index - 1]?.focus()">
                                                            <input type="search" class="form-control mb-2" x-ref="query" x-model="query" :aria-label="'Search ' + field.label" placeholder="Search options…" autocomplete="off" @keydown.enter.prevent>
                                                            <div class="call-choice-list"><template x-for="option in choices" :key="option"><button type="button" class="dropdown-item rounded py-2" :aria-pressed="selectedItems.includes(option)" :disabled="submitting" @click="toggle(option)"><i class="bi me-2" :class="selectedItems.includes(option) ? 'bi-check-square-fill' : 'bi-square'" aria-hidden="true"></i><span x-text="option"></span></button></template></div>
                                                            <p class="small text-body-secondary mb-0 p-2" role="status" x-show="!choices.length">No matching options.</p>
                                                        </div>
                                                    </div>
                                                </template>

                                                <template x-if="field.type === 'product_search' || field.type === 'agent_search'">
                                                    <div x-data="{ searchOpen: false }" @click.outside="searchOpen = false" @focusout="if (!$el.contains($event.relatedTarget)) searchOpen = false" @keydown.escape="if(searchOpen) { $event.stopPropagation(); $event.preventDefault(); searchOpen = false; $refs.lookup.focus(); }">
                                                        <div class="d-flex flex-wrap gap-1 mb-2" x-show="(selectedSearchItems[field.name] || []).length">
                                                            <template x-for="item in (selectedSearchItems[field.name] || [])" :key="item.id"><button type="button" class="btn btn-sm call-selected-item" :disabled="submitting" :aria-label="'Remove ' + item.name" @click="removeSearchItem(field.name, item.id)"><span x-text="item.name"></span><i class="bi bi-x ms-1" aria-hidden="true"></i></button></template>
                                                        </div>
                                                        <input type="text" class="form-control" x-ref="lookup" :aria-label="field.label" :aria-expanded="searchOpen" :disabled="submitting" :placeholder="field.placeholder || ('Search ' + field.label.replace('Search ', '') + '…')" x-model="searchQuery[field.name]" autocomplete="off"
                                                               @input="searchOpen = true; doSearch(field.name, field.type, $event.target.value)" @focus="searchOpen = true; doSearch(field.name, field.type, searchQuery[field.name])" @keydown.arrow-down.stop.prevent="$el.nextElementSibling?.querySelector('button')?.focus()" @keydown.enter.prevent>
                                                        <div class="call-choice-panel border rounded-3 p-2 mt-2" x-show="searchOpen" x-cloak
                                                             @keydown.arrow-down.stop.prevent="const buttons = [...$el.querySelectorAll('button:not(:disabled)')]; buttons[Math.min(buttons.indexOf($event.target) + 1, buttons.length - 1)]?.focus()"
                                                             @keydown.arrow-up.stop.prevent="const buttons = [...$el.querySelectorAll('button:not(:disabled)')]; const index = buttons.indexOf($event.target); if(index <= 0) $refs.lookup.focus(); else buttons[index - 1]?.focus()">
                                                            <p class="small text-body-secondary p-2 mb-0" role="status" x-show="searchState[field.name]?.loading">Searching…</p>
                                                            <p class="small text-danger p-2 mb-0" role="alert" x-show="searchState[field.name]?.error" x-text="searchState[field.name]?.error"></p>
                                                            <p class="small text-body-secondary p-2 mb-0" x-show="(searchQuery[field.name] || '').length === 1">Type at least 2 characters.</p>
                                                            <div class="call-choice-list"><template x-for="item in (searchResults[field.name] || [])" :key="item.id"><button type="button" class="dropdown-item rounded py-2" :disabled="submitting" :aria-pressed="(selectedSearchItems[field.name] || []).some(selected => String(selected.id) === String(item.id))" @click="selectSearchResult(field.name, item, 'name')"><span class="fw-semibold" x-text="item.name"></span><small class="d-block text-body-secondary" x-show="field.type === 'product_search'" x-text="item.sku || ''"></small></button></template></div>
                                                            <p class="small text-body-secondary p-2 mb-0" role="status" x-show="searchState[field.name]?.done && !searchState[field.name]?.error && !(searchResults[field.name] || []).length">No matching results.</p>
                                                        </div>
                                                    </div>
                                                </template>

                                                <template x-if="field.type === 'textarea'">
                                                    <textarea class="form-control form-control-sm fw-semibold" style="font-size: 12px;" rows="2" :aria-label="field.label" x-model="formData[field.name]" :disabled="submitting" :required="field.is_required" :placeholder="field.placeholder || ''"></textarea>
                                                </template>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                {{-- LEVEL 3 CARD --}}
                <div class="card mb-3 border border-secondary border-opacity-25 shadow-sm rounded-4 bg-body-secondary" style="z-index: 10;" x-show="selectedL2" x-transition>
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center gap-2 pb-2 mb-3 border-bottom">
                            <div class="bg-success bg-opacity-10 text-success rounded-2 d-flex align-items-center justify-content-center" style="width: 24px; height: 24px;">
                                <i class="bi bi-check-circle fs-6"></i>
                            </div>
                            <h6 class="mb-0 fw-bold text-uppercase text-body" style="font-size: 11px; letter-spacing: 1px;">Resolution & Notes</h6>
                        </div>
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">Final Outcome / Resolution</label>
                                <div class="d-flex align-items-center gap-2">
                                    <template x-if="l3Tags.length > 0">
                                        <x-searchable-select model="selectedL3" label="Call outcome (optional)">
<select x-ref="native" tabindex="-1" aria-hidden="true" class="visually-hidden" style="font-size: 12px;"  x-model="selectedL3" :disabled="loadingL3 || submitting">
                                            <option value="">Select Call Outcome...</option>
                                            <template x-for="tag in l3Tags" :key="tag.id">
                                                <option :value="tag.id" x-text="tag.name"></option>
                                            </template>
                                        </select>
</x-searchable-select>
                                    </template>
                                    <template x-if="loadingL3">
                                        <span class="spinner-border spinner-border-sm text-success"></span>
                                    </template>
                                    <template x-if="!loadingL3 && l3Tags.length === 0">
                                        <span class="text-muted small fst-italic">No outcomes available for this category.</span>
                                    </template>
                                </div>
                                <template x-if="selectedL3Data?.description">
                                    <div class="mt-2 small text-muted fst-italic">
                                        <i class="bi bi-info-circle me-1 text-success"></i> <span x-text="selectedL3Data.description"></span>
                                    </div>
                                </template>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">Agent Notes</label>
                                <textarea class="form-control form-control-sm fw-semibold" style="font-size: 12px;" rows="1" placeholder="Any general comments about the call..." aria-label="Agent notes" x-model="notes"></textarea>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
            
            <div class="modal-footer bg-body-tertiary border-top">
                <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal" @click="resetForm()" :disabled="submitting">Cancel</button>
                <button type="button" class="btn btn-primary px-4 fw-bold shadow-sm" @click="submitCallLog()" :disabled="!isFormValid() || submitting">
                    <template x-if="!submitting">
                        <span><i class="bi bi-save me-2"></i> Save Call Log</span>
                    </template>
                    <template x-if="submitting">
                        <span><span class="spinner-border spinner-border-sm me-2"></span>Saving...</span>
                    </template>
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.custom-hover-bg:hover { background-color: rgba(var(--bs-primary-rgb), 0.1); }
.cursor-pointer { cursor: pointer; }
</style>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('callTaggingApp', () => ({
        customerId: null,
        
        l1Tags: [],
        l2Tags: [],
        l3Tags: [],
        
        selectedL1: null,
        selectedL2: null,
        selectedL3: null,
        
        loadingL1: false,
        loadingL2: false,
        loadingL3: false,
        
        formFields: [],
        formData: {},
        notes: '',
        
        searchQuery: {},
        searchResults: {},
        searchState: {},
        searchTimeout: {},
        selectedSearchItems: {},
        
        submitting: false,
        
        modalInstance: null,
        error: '', fieldsReady: false, saved: false, _version: 0, _l1Request: 0, _l2Request: 0, _searchVersion: {},
        async jsonRequest(url, options = {}) {
            const response = await fetch(url, { ...options, headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', ...(options.headers || {}) } });
            if (response.redirected || response.status === 401 || response.status === 419) throw new Error('Your session expired. Reload the page and sign in again.');
            let result;
            try { result = await response.json(); } catch { throw new Error('Unexpected server response. Please retry.'); }
            if (!response.ok) throw new Error(result.errors ? Object.values(result.errors).flat().join(' ') : result.message || result.error || 'Request failed.');
            return result;
        },
        fieldOptions(field) {
            try { const options = Array.isArray(field.options) ? field.options : JSON.parse(field.options || '[]'); return Array.isArray(options) ? options : []; }
            catch { return []; }
        },
        
        get selectedL1Data() {
            return this.selectedL1 ? this.l1Tags.find(t => t.id == this.selectedL1) : null;
        },
        
        get selectedL2Data() {
            return this.selectedL2 ? this.l2Tags.find(t => t.id == this.selectedL2) : null;
        },
        
        get selectedL3Data() {
            return this.selectedL3 ? this.l3Tags.find(t => t.id == this.selectedL3) : null;
        },

        init() {
            this.modalInstance = new bootstrap.Modal(document.getElementById('callTaggingModal'));
            document.getElementById('callTaggingModal').addEventListener('hide.bs.modal', event => { if (this.submitting) event.preventDefault(); });
            this.$watch('selectedL1', value => this.selectL1(value));
            this.$watch('selectedL2', value => this.selectL2(value));
        },
        
        async openModal(custId) {
            if (this.submitting) return;
            this.customerId = custId;
            this.resetForm();
            this.modalInstance.show();
            await this.fetchL1Tags();
        },
        
        resetForm() {
            if (this.submitting) return;
            this._version++; this._l1Request++; this._l2Request++;
            Object.values(this.searchTimeout).forEach(timer => clearTimeout(timer));
            this.searchTimeout = {}; this._searchVersion = {};
            this.loadingL1 = this.loadingL2 = this.loadingL3 = false;
            this.error = ''; this.fieldsReady = false; this.saved = false; this.l1Tags = [];
            this.selectedL1 = null;
            this.selectedL2 = null;
            this.selectedL3 = null;
            this.l2Tags = [];
            this.l3Tags = [];
            this.formFields = [];
            this.formData = {};
            this.notes = '';
            this.searchQuery = {};
            this.searchResults = {}; this.searchState = {};
            this.selectedSearchItems = {};
            this.submitting = false;
        },
        
        async fetchL1Tags() {
            const version = this._version;
            this.loadingL1 = true; this.error = '';
            try {
                const result = await this.jsonRequest('/call-tags?level=1');
                if (version !== this._version) return;
                this.l1Tags = result;
                if (!result.length) this.error = 'No active call categories are available. Contact your administrator.';
            } catch (error) { if (version === this._version) this.error = error.message; }
            finally { if (version === this._version) this.loadingL1 = false; }
        },
        async selectL1(id) {
            const version = this._version, request = ++this._l1Request;
            this._l2Request++;
            this.selectedL2 = this.selectedL3 = null;
            this.l2Tags = []; this.l3Tags = []; this.formFields = []; this.formData = {};
            this.searchQuery = {}; this.searchResults = {}; this.searchState = {}; this.selectedSearchItems = {};
            this.fieldsReady = false; this.loadingL3 = false; this.loadingL2 = Boolean(id); this.error = '';
            if (!id) return;
            try {
                const result = await this.jsonRequest('/call-tags?parent_id=' + encodeURIComponent(id));
                if (version !== this._version || request !== this._l1Request || String(id) !== String(this.selectedL1)) return;
                this.l2Tags = result;
            } catch (error) { if (version === this._version && request === this._l1Request) this.error = error.message; }
            finally { if (version === this._version && request === this._l1Request) this.loadingL2 = false; }
        },
        async selectL2(id) {
            const version = this._version, request = ++this._l2Request;
            this.selectedL3 = null; this.l3Tags = []; this.formFields = []; this.formData = {};
            this.searchQuery = {}; this.searchResults = {}; this.searchState = {}; this.selectedSearchItems = {};
            this.fieldsReady = false; this.loadingL3 = Boolean(id); this.error = '';
            if (!id) return;
            try {
                const [tags, fields] = await Promise.all([
                    this.jsonRequest('/call-tags?parent_id=' + encodeURIComponent(id)),
                    this.jsonRequest('/call-tags/' + encodeURIComponent(id) + '/form')
                ]);
                if (version !== this._version || request !== this._l2Request || String(id) !== String(this.selectedL2)) return;
                this.l3Tags = tags; this.formFields = fields; this.fieldsReady = true;
                fields.forEach(field => {
                    this.formData[field.name] = ['multi_select', 'product_search', 'agent_search'].includes(field.type) ? [] : '';
                    this.searchQuery[field.name] = ''; this.searchResults[field.name] = []; this.selectedSearchItems[field.name] = [];
                });
            } catch (error) { if (version === this._version && request === this._l2Request) this.error = error.message; }
            finally { if (version === this._version && request === this._l2Request) this.loadingL3 = false; }
        },
        async doSearch(fieldName, fieldType, query) {
            const version = this._version, subject = this.selectedL2, fieldVersion = this._l2Request;
            const searchRequest = (this._searchVersion[fieldName] || 0) + 1;
            this._searchVersion[fieldName] = searchRequest;
            this.searchQuery[fieldName] = String(query || '');
            query = this.searchQuery[fieldName].trim();
            this.searchResults[fieldName] = [];
            this.searchState[fieldName] = { loading: query.length !== 1, done: false, error: '' };
            
            clearTimeout(this.searchTimeout[fieldName]);
            
            // Allow empty query to fetch default list (e.g., first few products)
            if (query && query.length === 1) {
                this.searchResults[fieldName] = [];
                return;
            }
            
            this.searchTimeout[fieldName] = setTimeout(async () => {
                let url = '';
                if (fieldType === 'product_search') {
                    url = `/products-search-api?q=${encodeURIComponent(query || '')}`;
                } else if (fieldType === 'agent_search') {
                    url = `/api/chat/users?q=${encodeURIComponent(query || '')}`;
                }
                
                try {
                    const json = await this.jsonRequest(url);
                    if (version !== this._version || fieldVersion !== this._l2Request || subject !== this.selectedL2 || searchRequest !== this._searchVersion[fieldName]) return;
                    this.searchResults[fieldName] = Array.isArray(json) ? json : json.data || [];
                    this.searchState[fieldName] = { loading: false, done: true, error: '' };
                } catch (e) {
                    if (version !== this._version || fieldVersion !== this._l2Request || subject !== this.selectedL2 || searchRequest !== this._searchVersion[fieldName]) return;
                    this.searchState[fieldName] = { loading: false, done: true, error: e.message }; this.searchResults[fieldName] = [];
                }
            }, 300);
        },
        
        selectSearchResult(fieldName, item, displayKey) {
            if (!Array.isArray(this.selectedSearchItems[fieldName])) {
                this.selectedSearchItems[fieldName] = [];
            }
            
            // If already selected, toggle it off (like a checkbox)
            if (this.selectedSearchItems[fieldName].find(i => String(i.id) === String(item.id))) {
                this.selectedSearchItems[fieldName] = this.selectedSearchItems[fieldName].filter(i => String(i.id) !== String(item.id));
            } else {
                this.selectedSearchItems[fieldName].push({ id: item.id, name: item[displayKey] });
            }
            
            this.formData[fieldName] = this.selectedSearchItems[fieldName].map(i => i.name);
        },
        
        removeSearchItem(fieldName, id) {
            if (!Array.isArray(this.selectedSearchItems[fieldName])) return;
            this.selectedSearchItems[fieldName] = this.selectedSearchItems[fieldName].filter(i => String(i.id) !== String(id));
            this.formData[fieldName] = this.selectedSearchItems[fieldName].map(i => i.name);
        },
        
        isFormValid() {
            if (!this.customerId || !this.selectedL1 || !this.selectedL2 || this.loadingL1 || this.loadingL2 || this.loadingL3 || !this.fieldsReady || this.saved) return false;
            
            // Check required dynamic fields
            for (let field of this.formFields) {
                if (field.is_required) {
                    if (field.type === 'multi_select' || field.type === 'product_search' || field.type === 'agent_search') {
                        if (!this.formData[field.name] || this.formData[field.name].length === 0) return false;
                    } else if (this.formData[field.name] == null || String(this.formData[field.name]).trim() === '') {
                        return false;
                    }
                }
            }
            return true;
        },
        
        async submitCallLog() {
            if (this.submitting || this.saved || !this.isFormValid()) return;
            const invalid = [...document.getElementById('callTaggingModal').querySelectorAll('input, select, textarea')].find(input => !input.disabled && !input.checkValidity());
            if (invalid) { invalid.reportValidity(); return; }
            
            this.submitting = true; this.error = '';
            
            const payload = {
                customer_id: this.customerId,
                tag_l1_id: this.selectedL1,
                tag_l2_id: this.selectedL2,
                tag_l3_id: this.selectedL3,
                meta: this.formData,
                notes: this.notes
            };
            
            try {
                const data = await this.jsonRequest('/call-logs', {
                    method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') },
                    body: JSON.stringify(payload)
                });
                this.saved = true;
                try { sessionStorage.setItem('pending_toast', 'Call logged and profile closed successfully'); } catch (_) {}
                window.dispatchEvent(new CustomEvent('call-log-added', { detail: data }));
            } catch (error) {
                this.error = error.message;
            }

            this.submitting = false;
        }
    }));
});
</script>
