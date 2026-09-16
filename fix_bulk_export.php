<?php
$file = 'app/Modules/Orders/Controllers/OrderController.php';
$content = file_get_contents($file);

// I need to replace from the start of bulkExport up to the orderBy
$targetStart = '    public function bulkExport(Request $request)
    {
        $query = Order::with([\'party\', \'warehouse\', \'items.product\', \'shipments\', \'billingAddress\', \'shippingAddress\']);

        $user = auth()->user();
        $this->applyOrderActionPermissionScope($query, $user);

            $requestedStatuses';

$replacementStart = '    public function bulkExport(Request $request)
    {
        $query = Order::with([\'party\', \'warehouse\', \'items.product\', \'shipments\', \'billingAddress\', \'shippingAddress\']);

        $user = auth()->user();
        $this->applyOrderActionPermissionScope($query, $user);

        if ($request->filled(\'search\')) {
            $s = trim($request->search);
            $query->where(function ($subQuery) use ($s) {
                $subQuery->where(\'order_no\', \'LIKE\', "%{$s}%")
                    ->orWhereHas(\'party\', function ($q) use ($s) {
                        $q->where(\'firstname\', \'LIKE\', "%{$s}%")
                            ->orWhere(\'lastname\', \'LIKE\', "%{$s}%")
                            ->orWhere(\'company_name\', \'LIKE\', "%{$s}%")
                            ->orWhere(\'phone\', \'LIKE\', "%{$s}%");
                    });
            });
        }

        if ($request->filled(\'status\')) {
            $requestedStatuses';

$content = str_replace($targetStart, $replacementStart, $content);

// Also need to close the if statement for status!
$targetEnd = '            });
        }

        if ($request->filled(\'product\')) {';

$replacementEnd = '            });
        }
        }

        if ($request->filled(\'product\')) {';

$content = str_replace($targetEnd, $replacementEnd, $content);

file_put_contents($file, $content);
echo "Fixed missing if wrappers.\n";
