<?php
$file = 'resources/views/orders/create.blade.php';
$content = file_get_contents($file);

$target = '<td class="py-2">
                                                <span class="badge rounded-pill px-2" :class="`bg-${getStatusTheme(order.lifecycle_status || order.status)}-subtle text-${getStatusTheme(order.lifecycle_status || order.status)}-emphasis border border-${getStatusTheme(order.lifecycle_status || order.status)}-subtle`" x-text="order.status_label || order.lifecycle_status || order.status || \'Pending\'"></span>
                                            </td>';

$replacement = '<td class="py-2">
                                                <div class="mb-1">
                                                    <span class="badge rounded-pill px-2" :class="`bg-${getStatusTheme(order.lifecycle_status || order.status)}-subtle text-${getStatusTheme(order.lifecycle_status || order.status)}-emphasis border border-${getStatusTheme(order.lifecycle_status || order.status)}-subtle`" x-text="order.status_label || order.lifecycle_status || order.status || \'Pending\'"></span>
                                                </div>
                                                <template x-if="order.open_complaints_count > 0">
                                                    <span class="badge bg-danger-subtle text-danger-emphasis border border-danger-subtle rounded-pill" style="font-size: 0.7rem;" title="Open Complaints">
                                                        <i class="bi bi-exclamation-triangle-fill me-1"></i>
                                                        <span x-text="order.open_complaints_count + \' Open Complaint\' + (order.open_complaints_count > 1 ? \'s\' : \'\')"></span>
                                                    </span>
                                                </template>
                                            </td>';

$count = 0;
$content = str_replace($target, $replacement, $content, $count);
file_put_contents($file, $content);
echo "Replaced $count occurrences.\n";
