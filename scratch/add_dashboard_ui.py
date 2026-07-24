import re

with open('/home/user/metis/resources/views/dashboard.blade.php', 'r') as f:
    content = f.read()

new_html = """
                <!-- Fulfillment & Returns Summary -->
                <div class="row g-4 g-lg-5 g-xl-6 mb-5 mb-lg-5 mb-xl-6">
                    <div class="col-xl-6">
                        <div class="card metric-card border-0 shadow-sm rounded-4 overflow-hidden h-100">
                            <div class="card-body p-4 p-lg-5 position-relative">
                                <div class="position-absolute top-0 end-0 mt-4 me-4 text-success opacity-25">
                                    <i class="bi bi-box-seam" style="font-size: 4rem;"></i>
                                </div>
                                <div class="d-flex align-items-center mb-4 pb-3 border-bottom position-relative" style="z-index: 1;">
                                    <div class="d-inline-flex align-items-center justify-content-center bg-success bg-opacity-10 text-success rounded-circle me-3" style="width: 48px; height: 48px;">
                                        <i class="bi bi-check-circle-fill fs-5"></i>
                                    </div>
                                    <h6 class="text-body-secondary mb-0 fw-bold text-uppercase" style="letter-spacing: 1px;">Fulfillment Performance</h6>
                                </div>
                                
                                <div class="row g-4 position-relative" style="z-index: 1;">
                                    <div class="col-6 border-end">
                                        <span class="text-muted d-block small mb-2 fw-semibold text-uppercase" style="letter-spacing: 0.5px;">Delivered Orders</span>
                                        <div class="d-flex align-items-center gap-3">
                                            <span class="display-6 fw-bold text-success mb-0">{{ number_format($totalDelivered) }}</span>
                                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-3 py-2 fw-bold" style="font-size: 0.85rem;">
                                                <i class="bi bi-pie-chart-fill me-1"></i> {{ $deliveredPercent }}%
                                            </span>
                                        </div>
                                    </div>
                                    <div class="col-6 ps-4">
                                        <span class="text-muted d-block small mb-2 fw-semibold text-uppercase" style="letter-spacing: 0.5px;">Rev. Delivered</span>
                                        <div class="h3 fw-bold text-success mb-0">
                                            Rs {{ number_format($revDelivered, 2) }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-6">
                        <div class="card metric-card border-0 shadow-sm rounded-4 overflow-hidden h-100">
                            <div class="card-body p-4 p-lg-5 position-relative">
                                <div class="position-absolute top-0 end-0 mt-4 me-4 text-danger opacity-25">
                                    <i class="bi bi-arrow-return-left" style="font-size: 4rem;"></i>
                                </div>
                                <div class="d-flex align-items-center mb-4 pb-3 border-bottom position-relative" style="z-index: 1;">
                                    <div class="d-inline-flex align-items-center justify-content-center bg-danger bg-opacity-10 text-danger rounded-circle me-3" style="width: 48px; height: 48px;">
                                        <i class="bi bi-x-circle-fill fs-5"></i>
                                    </div>
                                    <h6 class="text-body-secondary mb-0 fw-bold text-uppercase" style="letter-spacing: 1px;">Returns Performance</h6>
                                </div>
                                
                                <div class="row g-4 position-relative" style="z-index: 1;">
                                    <div class="col-6 border-end">
                                        <span class="text-muted d-block small mb-2 fw-semibold text-uppercase" style="letter-spacing: 0.5px;">Returned Orders</span>
                                        <div class="d-flex align-items-center gap-3">
                                            <span class="display-6 fw-bold text-danger mb-0">{{ number_format($totalReturned) }}</span>
                                            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 rounded-pill px-3 py-2 fw-bold" style="font-size: 0.85rem;">
                                                <i class="bi bi-pie-chart-fill me-1"></i> {{ $returnedPercent }}%
                                            </span>
                                        </div>
                                    </div>
                                    <div class="col-6 ps-4">
                                        <span class="text-muted d-block small mb-2 fw-semibold text-uppercase" style="letter-spacing: 0.5px;">Rev. Returned</span>
                                        <div class="h3 fw-bold text-danger mb-0">
                                            Rs {{ number_format($revReturned, 2) }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
"""

pattern = r"(                </div>\n\n                <!-- Charts Row 1 -->)"
replacement = new_html + r"\1"

content = re.sub(pattern, replacement, content)

with open('/home/user/metis/resources/views/dashboard.blade.php', 'w') as f:
    f.write(content)
