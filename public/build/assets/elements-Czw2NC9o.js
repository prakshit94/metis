import{m as i,c as n}from"./main-Cp0KHK0O.js";/* empty css             */const l=[{id:"buttons",title:"Buttons",category:"components",icon:"bi bi-square",description:"Bootstrap button styles, sizes, and states",examples:12,url:"/elements-buttons.html",preview:`
            <div class="d-flex gap-2 flex-wrap">
                <button class="btn btn-primary btn-sm">Primary</button>
                <button class="btn btn-outline-secondary btn-sm">Secondary</button>
                <button class="btn btn-success btn-sm">Success</button>
            </div>
        `},{id:"alerts",title:"Alerts",category:"components",icon:"bi bi-exclamation-triangle",description:"Contextual feedback messages for user actions",examples:8,url:"/elements-alerts.html",preview:`
            <div class="alert alert-primary alert-sm py-2 px-3 mb-2" role="alert">
                <i class="bi bi-info-circle me-2"></i>Primary alert
            </div>
            <div class="alert alert-success alert-sm py-2 px-3 mb-0" role="alert">
                <i class="bi bi-check-circle me-2"></i>Success alert
            </div>
        `},{id:"badges",title:"Badges",category:"components",icon:"bi bi-award",description:"Small count and labeling components",examples:6,url:"/elements-badges.html",preview:`
            <div class="d-flex gap-2 flex-wrap">
                <span class="badge bg-primary">Primary</span>
                <span class="badge bg-secondary">Secondary</span>
                <span class="badge bg-success">Success</span>
                <span class="badge bg-danger">Danger</span>
            </div>
        `},{id:"cards",title:"Cards",category:"components",icon:"bi bi-card-text",description:"Flexible content containers with headers and footers",examples:10,url:"/elements-cards.html",preview:`
            <div class="card" style="width: 200px;">
                <div class="card-body p-3">
                    <h6 class="card-title mb-2">Card Title</h6>
                    <p class="card-text mb-2 small">Sample card content with text.</p>
                    <button class="btn btn-primary btn-sm">Action</button>
                </div>
            </div>
        `},{id:"modals",title:"Modals",category:"components",icon:"bi bi-window",description:"Streamlined modal dialogs with flexible content",examples:9,url:"/elements-modals.html",preview:`
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#exampleModal">
                Launch Modal
            </button>
            <div class="modal fade" id="exampleModal" tabindex="-1" style="display: none;">
                <div class="modal-dialog modal-sm">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h6 class="modal-title">Modal Title</h6>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p class="mb-0 small">Modal content goes here.</p>
                        </div>
                    </div>
                </div>
            </div>
        `},{id:"forms",title:"Form Controls",category:"forms",icon:"bi bi-ui-checks-grid",description:"Form inputs, selects, checkboxes, and validation",examples:15,url:"/elements-forms.html",preview:`
            <div class="mb-2">
                <input type="text" class="form-control form-control-sm" placeholder="Text input">
            </div>
            <div class="mb-2">
                <select class="form-select form-select-sm">
                    <option>Choose...</option>
                    <option>Option 1</option>
                </select>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" checked>
                <label class="form-check-label small">Check me</label>
            </div>
        `},{id:"tables",title:"Tables",category:"content",icon:"bi bi-table",description:"Responsive tables with various styling options",examples:8,url:"/elements-tables.html",preview:`
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>John Doe</td>
                        <td><span class="badge bg-success">Active</span></td>
                    </tr>
                    <tr>
                        <td>Jane Smith</td>
                        <td><span class="badge bg-warning">Pending</span></td>
                    </tr>
                </tbody>
            </table>
        `}];document.addEventListener("alpine:init",()=>{i.data("elementsComponent",()=>({components:l,filteredComponents:l,viewMode:"grid",searchQuery:"",categoryFilter:"",init(){this.filteredComponents=this.components},filterComponents(){let t=this.components;if(this.searchQuery){const e=this.searchQuery.toLowerCase();t=t.filter(a=>a.title.toLowerCase().includes(e)||a.description.toLowerCase().includes(e)||a.category.toLowerCase().includes(e))}this.categoryFilter&&(t=t.filter(e=>e.category===this.categoryFilter)),this.filteredComponents=t},toggleView(){this.viewMode=this.viewMode==="grid"?"list":"grid",localStorage.setItem("elements-view-mode",this.viewMode)},navigateToComponent(t){window.location.href=t.url},showAllComponents(){this.searchQuery="",this.categoryFilter="",this.filteredComponents=this.components},clearFilters(){this.searchQuery="",this.categoryFilter="",this.filterComponents()},getComponentCount(t){return this.components.filter(e=>e.category===t).length}})),i.data("searchComponent",n({getResults(t){const e=t.toLowerCase(),a=l.filter(s=>s.title.toLowerCase().includes(e)||s.description.toLowerCase().includes(e)).map(s=>({title:s.title,url:s.url,type:"element"})),o=[{title:"Dashboard",url:"/",type:"page"},{title:"Analytics",url:"/analytics",type:"page"},{title:"Users",url:"/users",type:"page"},{title:"Elements",url:"/elements",type:"page"}].filter(s=>s.title.toLowerCase().includes(e));return[...a,...o].slice(0,8)}}))});document.addEventListener("DOMContentLoaded",()=>{[...document.querySelectorAll('[data-bs-toggle="tooltip"]')].forEach(e=>new bootstrap.Tooltip(e))});export{l as elementsData};
