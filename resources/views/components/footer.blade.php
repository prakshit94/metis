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
            <div class="col-md-6 text-center text-md-end" 
                 x-data="{ currentTime: new Date().toLocaleTimeString('en-IN', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true }), currentDate: new Date().toLocaleDateString('en-IN', { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' }) }" 
                 x-init="setInterval(() => { const d = new Date(); currentTime = d.toLocaleTimeString('en-IN', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true }); currentDate = d.toLocaleDateString('en-IN', { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' }); }, 1000)">
                <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 px-2 py-1 shadow-sm fw-medium">
                    <i class="bi bi-calendar3 me-1"></i> <span x-text="currentDate"></span>
                </span>
                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-2 py-1 shadow-sm fw-medium ms-2" style="min-width: 90px; display: inline-block;">
                    <i class="bi bi-clock me-1"></i> <span x-text="currentTime"></span>
                </span>
            </div>
        </div>
    </div>
</footer>
