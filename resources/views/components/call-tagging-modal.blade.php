<style>
    .custom-hover-bg { transition: background-color 0.2s; }
    .custom-hover-bg:hover { background-color: var(--bs-secondary-bg); }
</style>
<div class="modal fade" id="callTaggingModal" tabindex="-1" aria-hidden="true" x-data="callTaggingApp()" @open-call-tagging-modal.window="openModal($event.detail.customerId)">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            {{-- GLOSSY STYLE HEADER --}}
            <div class="modal-header bg-body-tertiary border-bottom d-flex align-items-center justify-content-between p-4">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-3 d-flex align-items-center justify-content-center shadow-sm" style="width: 48px; height: 48px;">
                        <i class="bi bi-headset fs-4"></i>
                    </div>
                    <div>
                        <h4 class="mb-0 fw-bold text-body">Log Call Details</h4>
                        <p class="mb-0 small text-muted">Record outcomes and interactions</p>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" @click="resetForm()"></button>
            </div>
            
            <div class="modal-body p-4 bg-body-tertiary">
                
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
                                <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">Call Category (Level 1) *</label>
                                <div class="d-flex align-items-center gap-2">
                                    <select class="form-select form-select-sm fw-semibold" style="font-size: 12px;" x-model="selectedL1">
                                        <option value="">Select Call Category...</option>
                                        <template x-for="tag in l1Tags" :key="tag.id">
                                            <option :value="tag.id" x-text="tag.name"></option>
                                        </template>
                                    </select>
                                    <template x-if="loadingL1">
                                        <span class="spinner-border spinner-border-sm text-primary"></span>
                                    </template>
                                </div>
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
                                <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">Sub Category (Level 2) *</label>
                                <div class="d-flex align-items-center gap-2">
                                    <template x-if="l2Tags.length > 0">
                                        <select class="form-select form-select-sm fw-semibold" style="font-size: 12px;" x-model="selectedL2">
                                            <option value="">Select Sub Category...</option>
                                            <template x-for="tag in l2Tags" :key="tag.id">
                                                <option :value="tag.id" x-text="tag.name"></option>
                                            </template>
                                        </select>
                                    </template>
                                    <template x-if="loadingL2">
                                        <span class="spinner-border spinner-border-sm text-info"></span>
                                    </template>
                                    <template x-if="!loadingL2 && l2Tags.length === 0">
                                        <span class="text-muted small fst-italic">No sub-categories available.</span>
                                    </template>
                                </div>
                            </div>

                            {{-- Dynamic Fields integrated into Level 2 Card --}}
                            <template x-if="formFields.length > 0">
                                <div class="col-md-12 mt-4">
                                    <h6 class="fw-bold text-muted text-uppercase mb-3" style="font-size: 10px; letter-spacing: 1px;">Additional Details</h6>
                                    <div class="row g-3">
                                        <template x-for="field in formFields" :key="field.id">
                                            <div class="col-md-6">
                                                <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">
                                                    <span x-text="field.label"></span>
                                                    <span x-show="field.is_required"> *</span>
                                                </label>
                                                
                                                <template x-if="['text', 'date', 'datetime-local', 'number'].includes(field.type)">
                                                    <input :type="field.type" class="form-control form-control-sm fw-semibold" style="font-size: 12px;" x-model="formData[field.name]" :required="field.is_required">
                                                </template>

                                                <template x-if="field.type === 'select'">
                                                    <select class="form-select form-select-sm fw-semibold" style="font-size: 12px;" x-model="formData[field.name]" :required="field.is_required">
                                                        <option value="">Select option...</option>
                                                        <template x-for="opt in (field.options ? JSON.parse(field.options) : [])" :key="opt">
                                                            <option :value="opt" x-text="opt"></option>
                                                        </template>
                                                    </select>
                                                </template>

                                                <template x-if="field.type === 'multi_select'">
                                                    <div x-data="{ showDropdown: false, get selectedItems() { return Array.isArray(formData[field.name]) ? formData[field.name] : []; }, toggleItem(opt) { let arr = Array.isArray(formData[field.name]) ? [...formData[field.name]] : []; if(arr.includes(opt)) { arr = arr.filter(i => i !== opt); } else { arr.push(opt); } formData[field.name] = arr; } }" class="position-relative" @click.away="showDropdown = false">
                                                        <div @click="showDropdown = !showDropdown" class="form-control form-control-sm d-flex flex-wrap align-items-center gap-1 bg-body" style="min-height: 31px; cursor: pointer;">
                                                            <template x-if="selectedItems.length === 0">
                                                                <span class="text-muted" style="font-size: 12px;">Select...</span>
                                                            </template>
                                                            <template x-for="item in selectedItems" :key="item">
                                                                <div class="badge bg-success bg-opacity-10 text-success d-flex align-items-center gap-1">
                                                                    <span x-text="item" style="font-size: 10px;"></span>
                                                                    <i class="bi bi-x cursor-pointer" @click.stop="toggleItem(item)" style="font-size: 12px;"></i>
                                                                </div>
                                                            </template>
                                                        </div>
                                                        <div x-show="showDropdown" class="position-absolute w-100 bg-body-tertiary border rounded shadow-lg mt-1" style="max-height: 150px; overflow-y: auto; z-index: 1050;">
                                                            <template x-for="opt in (field.options ? JSON.parse(field.options) : [])" :key="opt">
                                                                <div class="px-3 py-1 cursor-pointer custom-hover-bg d-flex align-items-center" @click.stop="toggleItem(opt)">
                                                                    <input type="checkbox" :checked="selectedItems.includes(opt)" class="me-2" style="cursor: pointer;">
                                                                    <span style="font-size: 12px;" x-text="opt"></span>
                                                                </div>
                                                            </template>
                                                        </div>
                                                    </div>
                                                </template>

                                                <template x-if="field.type === 'product_search' || field.type === 'agent_search'">
                                                    <div class="position-relative" @click.away="searchResults[field.name] = []">
                                                        <div class="form-control form-control-sm d-flex flex-wrap align-items-center gap-1 bg-body" style="min-height: 31px; cursor: text;" @click="$refs['search_' + field.name].focus()">
                                                            
                                                            <template x-for="item in (selectedSearchItems[field.name] || [])" :key="item.id">
                                                                <div class="badge bg-success bg-opacity-10 text-success d-flex align-items-center gap-1">
                                                                    <span x-text="item.name" style="font-size: 10px;"></span>
                                                                    <i class="bi bi-x cursor-pointer" @click.stop="removeSearchItem(field.name, item.id)" style="font-size: 12px;"></i>
                                                                </div>
                                                            </template>
                                                            
                                                            <div class="flex-grow-1 position-relative" style="min-width: 100px;">
                                                                <input :x-ref="'search_' + field.name" type="text" class="border-0 w-100 outline-none" 
                                                                       :placeholder="((selectedSearchItems[field.name] || []).length === 0) ? ('Search ' + field.label.replace('Search ', '') + '...') : 'Search more...'"
                                                                       x-model="searchQuery[field.name]"
                                                                       @input="doSearch(field.name, field.type, $event.target.value)"
                                                                       @focus="doSearch(field.name, field.type, searchQuery[field.name])"
                                                                       style="font-size: 12px; outline: none !important; box-shadow: none; background: transparent;">
                                                            </div>
                                                        </div>
                                                               
                                                        <input type="hidden" x-model="formData[field.name]" :required="field.is_required">
                                                        
                                                        <div class="position-absolute w-100 bg-body-tertiary border rounded shadow-lg mt-1" 
                                                             style="max-height: 200px; overflow-y: auto; z-index: 1050;" 
                                                             x-show="searchResults[field.name] && searchResults[field.name].length > 0"
                                                             x-transition>
                                                            
                                                            <template x-for="item in searchResults[field.name]" :key="item.id">
                                                                <div class="px-3 py-1 cursor-pointer custom-hover-bg d-flex align-items-center" 
                                                                        @click.stop="selectSearchResult(field.name, item, 'name')">
                                                                    <input type="checkbox" :checked="(selectedSearchItems[field.name] || []).find(i => i.id === item.id) !== undefined" class="me-2" style="cursor: pointer;">
                                                                    <span class="fw-bold" style="font-size: 12px;" x-text="item.name"></span>
                                                                    <template x-if="field.type === 'product_search'">
                                                                        <span class="text-muted ms-1" style="font-size: 11px;" x-text="'- ' + item.sku"></span>
                                                                    </template>
                                                                </div>
                                                            </template>
                                                        </div>
                                                    </div>
                                                </template>

                                                <template x-if="field.type === 'textarea'">
                                                    <textarea class="form-control form-control-sm fw-semibold" style="font-size: 12px;" rows="2" x-model="formData[field.name]" :required="field.is_required"></textarea>
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
                                <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">Call Outcome (Level 3)</label>
                                <div class="d-flex align-items-center gap-2">
                                    <template x-if="l3Tags.length > 0">
                                        <select class="form-select form-select-sm fw-semibold" style="font-size: 12px;" x-model="selectedL3">
                                            <option value="">Select Call Outcome...</option>
                                            <template x-for="tag in l3Tags" :key="tag.id">
                                                <option :value="tag.id" x-text="tag.name"></option>
                                            </template>
                                        </select>
                                    </template>
                                    <template x-if="loadingL3">
                                        <span class="spinner-border spinner-border-sm text-success"></span>
                                    </template>
                                    <template x-if="!loadingL3 && l3Tags.length === 0">
                                        <span class="text-muted small fst-italic">No outcomes available for this category.</span>
                                    </template>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label mb-1 fw-bold text-muted text-uppercase" style="font-size: 9px; letter-spacing: 0.1em;">Agent Notes</label>
                                <textarea class="form-control form-control-sm fw-semibold" style="font-size: 12px;" rows="1" placeholder="Any general comments about the call..." x-model="notes"></textarea>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
            
            <div class="modal-footer bg-body-tertiary border-top">
                <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal" @click="resetForm()">Cancel</button>
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
        searchTimeout: {},
        selectedSearchItems: {},
        
        submitting: false,
        
        modalInstance: null,

        init() {
            this.modalInstance = new bootstrap.Modal(document.getElementById('callTaggingModal'));
            this.$watch('selectedL1', value => this.selectL1(value));
            this.$watch('selectedL2', value => this.selectL2(value));
        },
        
        async openModal(custId) {
            this.customerId = custId;
            this.resetForm();
            this.modalInstance.show();
            await this.fetchL1Tags();
        },
        
        resetForm() {
            this.selectedL1 = null;
            this.selectedL2 = null;
            this.selectedL3 = null;
            this.l2Tags = [];
            this.l3Tags = [];
            this.formFields = [];
            this.formData = {};
            this.notes = '';
        },
        
        async fetchL1Tags() {
            this.loadingL1 = true;
            try {
                const res = await fetch('/call-tags?level=1', {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (res.ok) {
                    this.l1Tags = await res.json();
                }
            } catch (e) {
                console.error('Failed to fetch L1 tags:', e);
            }
            this.loadingL1 = false;
        },
        
        async selectL1(id) {
            this.selectedL2 = null;
            this.selectedL3 = null;
            this.l3Tags = [];
            this.formFields = [];
            this.formData = {};
            this.searchQuery = {};
            this.searchResults = {};
            this.selectedSearchItems = {};
            
            if (!id) {
                this.l2Tags = [];
                return;
            }
            
            this.loadingL2 = true;
            try {
                const res = await fetch(`/call-tags?parent_id=${id}`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (res.ok) {
                    this.l2Tags = await res.json();
                }
            } catch (e) {
                console.error('Failed to fetch L2 tags:', e);
            }
            this.loadingL2 = false;
        },
        
        async selectL2(id) {
            this.selectedL3 = null;
            this.formData = {};
            this.searchQuery = {};
            this.searchResults = {};
            this.selectedSearchItems = {};
            
            if (!id) {
                this.l3Tags = [];
                this.formFields = [];
                return;
            }
            
            this.loadingL3 = true;
            try {
                // Fetch L3 Tags
                const resTags = await fetch(`/call-tags?parent_id=${id}`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (resTags.ok) {
                    this.l3Tags = await resTags.json();
                }
                
                // Fetch Form Fields
                const resFields = await fetch(`/call-tags/${id}/form`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (resFields.ok) {
                    this.formFields = await resFields.json();
                    
                    // Initialize form data
                    this.formFields.forEach(f => {
                        this.formData[f.name] = (f.type === 'multi_select' || f.type === 'product_search' || f.type === 'agent_search') ? [] : '';
                        this.searchQuery[f.name] = '';
                        this.searchResults[f.name] = [];
                        this.selectedSearchItems[f.name] = [];
                    });
                }
                
            } catch (e) {
                console.error('Failed to fetch L3 tags and fields:', e);
            }
            this.loadingL3 = false;
        },
        
        async doSearch(fieldName, fieldType, query) {
            this.searchQuery[fieldName] = query;
            
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
                    const res = await fetch(url);
                    const json = await res.json();
                    this.searchResults[fieldName] = json.data || [];
                } catch (e) {
                    console.error(e);
                    this.searchResults[fieldName] = [];
                }
            }, 300);
        },
        
        selectSearchResult(fieldName, item, displayKey) {
            if (!Array.isArray(this.selectedSearchItems[fieldName])) {
                this.selectedSearchItems[fieldName] = [];
            }
            
            // If already selected, toggle it off (like a checkbox)
            if (this.selectedSearchItems[fieldName].find(i => i.id === item.id)) {
                this.selectedSearchItems[fieldName] = this.selectedSearchItems[fieldName].filter(i => i.id !== item.id);
            } else {
                this.selectedSearchItems[fieldName].push({ id: item.id, name: item[displayKey] });
            }
            
            this.formData[fieldName] = this.selectedSearchItems[fieldName].map(i => i.name);
        },
        
        removeSearchItem(fieldName, id) {
            if (!Array.isArray(this.selectedSearchItems[fieldName])) return;
            this.selectedSearchItems[fieldName] = this.selectedSearchItems[fieldName].filter(i => i.id !== id);
            this.formData[fieldName] = this.selectedSearchItems[fieldName].map(i => i.name);
        },
        
        isFormValid() {
            if (!this.selectedL1 || !this.selectedL2) return false;
            
            // Check required dynamic fields
            for (let field of this.formFields) {
                if (field.is_required) {
                    if (field.type === 'multi_select' || field.type === 'product_search' || field.type === 'agent_search') {
                        if (!this.formData[field.name] || this.formData[field.name].length === 0) return false;
                    } else if (!this.formData[field.name]) {
                        return false;
                    }
                }
            }
            return true;
        },
        
        async submitCallLog() {
            if (!this.isFormValid()) return;
            
            this.submitting = true;
            
            const payload = {
                customer_id: this.customerId,
                tag_l1_id: this.selectedL1,
                tag_l2_id: this.selectedL2,
                tag_l3_id: this.selectedL3,
                meta: this.formData,
                notes: this.notes
            };
            
            try {
                const res = await fetch('/call-logs', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                    },
                    body: JSON.stringify(payload)
                });
                
                if (res.ok) {
                    const data = await res.json();
                    
                    // Set a session storage toast flag so the dashboard picks it up and shows it
                    sessionStorage.setItem('pending_toast', 'Profile closed successfully');
                    
                    // Redirect to the dashboard
                    window.location.href = '/';
                } else {
                    const err = await res.json();
                    alert(err.message || 'Failed to save call log');
                }
            } catch (e) {
                console.error(e);
                alert('A network error occurred.');
            }
            
            this.submitting = false;
        }
    }));
});
</script>
