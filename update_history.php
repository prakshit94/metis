<?php
$file = 'resources/views/orders/create.blade.php';
$content = file_get_contents($file);

// Replace historyOrders block (from line 1287 to 1508)
$startTokenHistory = '<div class="list-group list-group-flush border-top" style="max-height: 500px; overflow-y: auto;">';
$endTokenHistory = '                        </div>
                    </template>
                    <template x-if="!historyOrders || !historyOrders.length">';

$historyTableReplacement = '<div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                            <table class="table table-hover table-sm align-middle mb-0" style="font-size: 0.85rem;">
                                <thead class="table-secondary sticky-top" style="z-index: 1;">
                                    <tr>
                                        <th scope="col" class="text-nowrap ps-4 py-2 border-bottom-0">Order #</th>
                                        <th scope="col" class="text-nowrap py-2 border-bottom-0">Date & Time</th>
                                        <th scope="col" class="text-nowrap py-2 border-bottom-0">Status</th>
                                        <th scope="col" class="text-nowrap py-2 border-bottom-0">Warehouse & Items</th>
                                        <th scope="col" class="text-nowrap text-end pe-4 py-2 border-bottom-0">Total</th>
                                    </tr>
                                </thead>
                                <tbody class="border-top-0">
                                    <template x-for="(order, index) in historyOrders" :key="\'history-\' + order.id">
                                        <tr @click="viewOrder(order.id)" class="transition-all" style="cursor: pointer;">
                                            <td class="text-nowrap ps-4 py-2 fw-bold text-body-emphasis">
                                                <span class="text-secondary opacity-75 me-1" x-text="(index + 1) + \'.\'"></span>
                                                <span x-text="order.order_no || order.order_number || (\'Order #\' + order.id)"></span>
                                            </td>
                                            <td class="text-nowrap py-2">
                                                <div class="fw-medium text-body-emphasis" x-text="order.order_date ? new Date(order.order_date).toLocaleDateString() : \'No date\'"></div>
                                                <div class="small text-body-secondary" x-show="order.order_date" x-text="new Date(order.order_date).toLocaleTimeString([], {hour: \'2-digit\', minute:\'2-digit\'})"></div>
                                                <div class="small text-body-secondary mt-1" x-show="order.creator" x-text="\'by \' + (order.creator?.first_name ? (order.creator.first_name + \' \' + (order.creator.last_name || \'\')) : (order.creator?.name || \'\'))"></div>
                                            </td>
                                            <td class="py-2">
                                                <span class="badge rounded-pill px-2" :class="`bg-${getStatusTheme(order.lifecycle_status || order.status)}-subtle text-${getStatusTheme(order.lifecycle_status || order.status)}-emphasis border border-${getStatusTheme(order.lifecycle_status || order.status)}-subtle`" x-text="order.status_label || order.lifecycle_status || order.status || \'Pending\'"></span>
                                            </td>
                                            <td class="py-2 text-body-secondary">
                                                <span x-text="order.warehouse?.name ? order.warehouse.name : \'N/A\'"></span>
                                                <span class="mx-1">•</span>
                                                <span x-text="(order.items ? order.items.length : 0) + \' items\'"></span>
                                            </td>
                                            <td class="text-end pe-4 py-2 fw-bold text-body-emphasis" x-text="\'₹ \' + Number(order.net_amount || 0).toFixed(2)"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </template>
                    <template x-if="!historyOrders || !historyOrders.length">';

$posHistoryStart = strpos($content, $startTokenHistory);
if ($posHistoryStart !== false) {
    $posHistoryEnd = strpos($content, $endTokenHistory, $posHistoryStart);
    if ($posHistoryEnd !== false) {
        $content = substr_replace($content, $historyTableReplacement, $posHistoryStart, $posHistoryEnd - $posHistoryStart + strlen($endTokenHistory));
    } else { echo "Error: endTokenHistory not found\n"; }
} else { echo "Error: startTokenHistory not found\n"; }


// Fix Future Orders (remove expanded row from lines 1556 to 1630)
// wait, the start tag is `<tr x-show="expandedOrderId === order.id" x-cloak>` and the closing is `</tr>` for the expanded block.
// And `</tbody>` and `</template>` and `</table>` are after it.
$startTokenFuture = '<tr x-show="expandedOrderId === order.id" x-cloak>';
$endTokenFuture = '                                        </tr>
                                    </tbody>
                                </template>
                            </table>';

$futureReplacement = '                                    </tbody>
                                </template>
                            </table>';

$posFutureStart = strpos($content, $startTokenFuture);
if ($posFutureStart !== false) {
    $posFutureEnd = strpos($content, $endTokenFuture, $posFutureStart);
    if ($posFutureEnd !== false) {
        $content = substr_replace($content, $futureReplacement, $posFutureStart, $posFutureEnd - $posFutureStart + strlen($endTokenFuture));
    } else { echo "Error: endTokenFuture not found\n"; }
} else { echo "Error: startTokenFuture not found\n"; }

// Add modal-footer to orderDetailModal
// Search for `                            </div>
//                        </div>
//                    </div>
//                </div>
//            </template>`

$modalEndToken = '                            </div>
                        </div>
                    </div>
                </div>
            </template>';

$modalFooterAddition = '                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-body-tertiary">
                        @can(\'complaints.create\')
                        <button type="button" x-show="[\'dispatched\', \'shipped\', \'delivered\', \'returned\', \'return_requested\', \'delivery_attempted\'].includes(selectedOrder.original.status || selectedOrder.original.lifecycle_status)" @click="$dispatch(\'open-complaint-modal\', { order_no: selectedOrder.original.order_no || selectedOrder.original.order_number || \'\', customer_id: selectedOrder.original.party_id || \'\' })" class="btn btn-outline-warning rounded-pill px-4 fw-bold">
                            <i class="bi bi-headset me-1"></i> Raise Complaint
                            <span x-show="selectedOrder.original.complaints_count > 0" x-cloak class="badge bg-warning text-dark border border-warning border-opacity-75 ms-1" x-text="selectedOrder.original.complaints_count"></span>
                        </button>
                        @endcan
                        @can(\'orders.edit\')
                        <button type="button" class="btn btn-outline-primary rounded-pill px-4 fw-bold" @click="editOrder(selectedOrder.original.id); bootstrap.Modal.getInstance(document.getElementById(\'orderDetailModal\')).hide()" x-show="![\'delivered\', \'cancelled\', \'returned\', \'return_requested\', \'shipped\', \'dispatched\', \'delivery_attempted\'].includes(selectedOrder.original.status || selectedOrder.original.lifecycle_status)">
                            <i class="bi bi-pencil-square me-1"></i> Edit Order
                        </button>
                        @endcan
                        @can(\'orders.cancel\')
                        <button type="button" class="btn btn-outline-danger rounded-pill px-4 fw-bold" @click="cancelOrder(selectedOrder.original.id, selectedOrder.original.order_no || selectedOrder.original.order_number); bootstrap.Modal.getInstance(document.getElementById(\'orderDetailModal\')).hide()" x-show="![\'delivered\', \'cancelled\', \'returned\', \'return_requested\', \'shipped\', \'dispatched\', \'delivery_attempted\'].includes(selectedOrder.original.status || selectedOrder.original.lifecycle_status)">
                            <i class="bi bi-x-circle me-1"></i> Cancel Order
                        </button>
                        @endcan
                        <button type="button" class="btn btn-secondary rounded-pill px-4 fw-bold" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </template>';

$posModalEnd = strpos($content, $modalEndToken);
if ($posModalEnd !== false) {
    $content = substr_replace($content, $modalFooterAddition, $posModalEnd, strlen($modalEndToken));
} else { echo "Error: modalEndToken not found\n"; }


$content = str_replace('Tap an order to expand its details.', 'Tap an order to view its details.', $content);

file_put_contents($file, $content);
echo "Done replacing.\n";
