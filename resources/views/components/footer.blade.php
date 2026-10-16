<footer class="admin-footer border-top border-secondary border-opacity-10 py-3 mt-auto bg-body">
    <div class="container-fluid">
        <div class="row align-items-center">
            <div class="col-md-6 text-center text-md-start mb-2 mb-md-0">
                <span class="text-muted small fw-medium">
                    &copy; {{ date('Y') }} {{ config('app.name', 'Ecommerce') }}. All rights reserved.
                </span>
                <span class="text-muted mx-2 d-none d-md-inline">|</span>
                <span class="text-muted small">
                    <i class="bi bi-hdd-network text-success me-1"></i> System Status: Online
                </span>
            </div>
            <div class="footer-clock-group col-md-6 d-flex flex-wrap align-items-center justify-content-center justify-content-md-end gap-2"
                 x-data="{
                     currentTime: '', currentDate: '', clockTimer: null,
                     updateClock() {
                         const d = new Date();
                         this.currentTime = d.toLocaleTimeString('en-IN', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true });
                         this.currentDate = d.toLocaleDateString('en-IN', { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' });
                     },
                     init() { this.updateClock(); this.clockTimer = setInterval(() => this.updateClock(), 1000); },
                     destroy() { clearInterval(this.clockTimer); }
                 }">
                <span class="badge bg-secondary bg-opacity-10 text-secondary-emphasis border border-secondary border-opacity-25 px-2 py-1 shadow-sm fw-medium text-nowrap">
                    <i class="bi bi-calendar3 me-1"></i> <span x-text="currentDate"></span>
                </span>
                <span class="badge bg-primary bg-opacity-10 text-primary-emphasis border border-primary border-opacity-25 px-2 py-1 shadow-sm fw-medium" style="min-width: 90px; display: inline-block; font-variant-numeric: tabular-nums;">
                    <i class="bi bi-clock me-1"></i> <span x-text="currentTime"></span>
                </span>
            </div>
        </div>
    </div>
</footer>
