<?php
$content = file_get_contents('resources/views/orders/index.blade.php');
$search = <<<EOD
                                </template>

                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>
EOD;

$replace = <<<EOD
                                </template>

                                <!-- Order Status Timeline -->
                                <template x-if="selectedOrder.original && selectedOrder.original.status_logs && selectedOrder.original.status_logs.length > 0">
                                    <div class="card border-0 shadow-sm rounded-4 mb-4 bg-secondary bg-opacity-10 border border-secondary border-opacity-25">
                                        <div class="card-body p-4">
                                            <h6 class="fw-bold mb-3 text-secondary d-flex align-items-center gap-2">
                                                <i class="bi bi-clock-history fs-5"></i> Order Status Timeline
                                            </h6>
                                            <div class="position-relative ms-2 ps-3 border-start border-secondary border-opacity-25 border-2">
                                                <template x-for="log in selectedOrder.original.status_logs" :key="log.id">
                                                    <div class="position-relative mb-3">
                                                        <div class="position-absolute bg-secondary rounded-circle" style="width: 10px; height: 10px; left: -22px; top: 5px;"></div>
                                                        <div class="d-flex justify-content-between align-items-start">
                                                            <div>
                                                                <p class="fw-bold text-body-emphasis mb-0 small text-capitalize" x-text="log.status.replace(/_/g, ' ')"></p>
                                                                <p class="text-muted mb-0" style="font-size: 0.75rem;" x-text="formatDateTime(log.created_at)"></p>
                                                                <p class="text-secondary small mt-1 lh-sm mb-0" x-show="log.notes" x-text="log.notes"></p>
                                                                <p class="text-secondary opacity-75" style="font-size: 0.7rem; margin-top: 2px;" x-show="log.changed_by_user">
                                                                    <i class="bi bi-person me-1"></i><span x-text="log.changed_by_user.firstname + ' ' + (log.changed_by_user.lastname || '')"></span>
                                                                </p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                </template>

                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>
EOD;

if (strpos($content, $search) !== false) {
    file_put_contents('resources/views/orders/index.blade.php', str_replace($search, $replace, $content));
    echo "Successfully added Order Status Timeline to modal.\n";
} else {
    echo "Search string not found.\n";
}
