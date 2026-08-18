<div class="card border-0 shadow-sm rounded-4 h-100" 
     x-data="{ 
         searchActivity: '', 
         isLoading: false,
         warehouseId: '{{ $warehouseId ?? '' }}',
         async fetchActivities() {
             this.isLoading = true;
             try {
                 const params = new URLSearchParams();
                 if (this.searchActivity.trim()) {
                     params.append('search', this.searchActivity.trim());
                 }
                 if (this.warehouseId) {
                     params.append('warehouse_id', this.warehouseId);
                 }
                 
                 const response = await fetch(`/inventory/dashboard/activities?${params.toString()}`);
                 if (response.ok) {
                     const html = await response.text();
                     this.$refs.activityContainer.innerHTML = html;
                 }
             } catch (error) {
                 console.error('Error fetching activities:', error);
             } finally {
                 this.isLoading = false;
             }
         }
     }">
    <div class="card-header bg-transparent border-bottom-0 pt-4 pb-0 px-4 d-flex flex-column gap-3">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0">
                <i class="bi bi-activity text-primary me-2"></i>Real-Time Activity
                <span x-show="isLoading" class="spinner-border spinner-border-sm text-primary ms-2" role="status">
                    <span class="visually-hidden">Loading...</span>
                </span>
            </h5>
        </div>
        <div class="position-relative">
            <input type="text" class="form-control form-control-sm bg-body border border-secondary border-opacity-25 ps-5 rounded-pill py-2" placeholder="Search across all activities (e.g., ORD-123)..." x-model="searchActivity" @input.debounce.300ms="fetchActivities()">
            <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
        </div>
    </div>
    <div class="card-body p-4">
        <div class="timeline pe-2" x-ref="activityContainer" style="max-height: 400px; overflow-y: auto; overflow-x: hidden;">
            @include('inventory.dashboard.partials.activity-feed-items', ['recentActivity' => $recentActivity])
        </div>
    </div>
</div>
