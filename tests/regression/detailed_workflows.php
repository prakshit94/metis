<?php

use App\Modules\Orders\Models\OrderReturn;
use App\Modules\Users\Models\Permission;
use App\Modules\Users\Models\User;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

$user->givePermissionTo(Permission::findOrCreate('complaints.view-all', 'web'));

// Authenticated read endpoints: exercise every static application API GET, excluding downloads.
$readFailures = [];
$readCount = 0;
foreach ($app['router']->getRoutes() as $route) {
    $uri = $route->uri();
    if (! str_starts_with($uri, 'api/') || str_contains($uri, '{') || ! in_array('GET', $route->methods(), true)
        || ! str_starts_with($route->getActionName(), 'App\\')
        || preg_match('~(?:export|pdf|download|template|/sample|files/preview|bulk-print)~', $uri)) {
        continue;
    }
    $response = $http('GET', '/'.$uri, [], $token);
    $code = $response->getStatusCode();
    if ($code >= 500 || $code === 401 || ($code >= 300 && $code < 400)
        || ! str_contains($response->headers->get('Content-Type', ''), 'application/json')) {
        $readFailures[] = $uri.' returned '.$code.' '.substr($response->getContent(), 0, 200);
    }
    echo "READ: {$uri} {$code}\n";
    $readCount++;
}
check($readFailures === [], 'static API read sweep ('.$readCount.' endpoints): '.implode('; ', $readFailures));
$response = $http('GET', '/api/orders', [], $token);
$json = json_decode($response->getContent(), true);
check(! array_key_exists(0, $json['stats']) && isset($json['stats']['total']), 'order statistics contain only named business fields');
foreach ($json['warehouseStats'] as $row) {
    check(! array_key_exists(0, $row) && array_key_exists('warehouse_id', $row), 'warehouse statistics contain only named business fields');
}
$response = $http('GET', '/api/orders/'.$ownPending.'/edit-options', [], $token);
check($response->getStatusCode() === 200, 'editable-order form options load through bearer authentication');
foreach (['brands' => ['name' => 'Workflow Brand', 'status' => 'active'], 'categories' => ['name' => 'Workflow Category', 'status' => 'active'], 'uom' => ['name' => 'Workflow Unit', 'status' => 'active'], 'hsn-codes' => ['code' => 'WF-123', 'status' => 'active'], 'tax-rates' => ['name' => 'Workflow Tax', 'rate' => 5, 'status' => 'active'], 'departments' => ['name' => 'Workflow Department'], 'holidays' => ['name' => 'Workflow Holiday', 'date' => '2026-10-27', 'type' => 'National']] as $resource => $body) {
    $response = $http('POST', '/api/'.$resource, $body, $token);
    $record = json_decode($response->getContent(), true)['data'] ?? [];
    check(in_array($response->getStatusCode(), [200, 201], true) && isset($record['id']), 'create valid '.$resource.' record');
    $uri = '/api/'.$resource.'/'.$record['id'];
    $response = $http('GET', $uri, [], $token);
    check($response->getStatusCode() === 200, 'read created '.$resource.' record');
    $response = $http('PUT', $uri, $body, $token);
    check($response->getStatusCode() === 200, 'update created '.$resource.' record');
    $response = $http('DELETE', $uri, [], $token);
    check(in_array($response->getStatusCode(), [200, 204], true), 'delete unused '.$resource.' record');
}
// Real multipart uploads, not just missing-file validation.
$uploadHttp = function (string $uri, array $body, string $content, string $bearer) use ($app) {
    $path = tempnam(sys_get_temp_dir(), 'metis-csv-');
    file_put_contents($path, $content);
    try {
        $app['auth']->forgetGuards();
        $app['auth']->shouldUse('web');
        $request = Request::create($uri, 'POST', $body, [], ['file' => new UploadedFile($path, 'workflow.csv', 'text/csv', null, true)], ['HTTP_AUTHORIZATION' => 'Bearer '.$bearer]);

        return $app->make(Kernel::class)->handle($request);
    } finally {
        unlink($path);
    }
};
foreach (['deliver', 'return'] as $action) {
    $response = $uploadHttp('/api/orders/import-'.$action, [], '', $token);
    check($response->getStatusCode() === 400 && ! $response->headers->has('Location'), 'empty '.$action.' import returns JSON without redirect');
}
$response = $http('POST', '/api/returns/bulk-qc-import', ['file' => 'not-an-upload'], $token);
check($response->getStatusCode() === 422, 'QC import rejects a text field in place of an uploaded file');
$csvOrder = DB::table('orders')->insertGetId(['order_no' => 'CSV-VISIBLE', 'type' => 'sale', 'party_id' => $party, 'warehouse_id' => $wh, 'order_date' => now(), 'status' => 'dispatched', 'created_by' => $uid, 'net_amount' => 100]);
$csvInvoice = DB::table('invoices')->insertGetId(['invoice_no' => 'CSV-INVOICE', 'order_id' => $csvOrder, 'invoice_date' => now(), 'total_amount' => 100, 'tax_amount' => 0, 'net_amount' => 100, 'status' => 'unpaid']);
$response = $uploadHttp('/api/payments/import/preview', [], "\xEF\xBB\xBFreference_type,reference_no,amount,payment_method,transaction_id,payment_date\ninvoice,CSV-INVOICE,10,cash,\"TXN\nMULTILINE\",2026-10-27\n", $token);
$json = json_decode($response->getContent(), true);
check($response->getStatusCode() === 200 && count($json['preview'] ?? []) === 1 && ($json['preview'][0]['transaction_id'] ?? '') === "TXN\nMULTILINE" && empty($json['errors']), 'payment CSV preview accepts UTF-8 BOM and quoted multiline fields');
$operator = User::create(['name' => 'CSV operator', 'email' => 'csv-operator@example.invalid', 'password' => bcrypt('Example123!')]);
foreach (['orders.deliver', 'orders.return', 'orders.view.dispatched', 'orders.view.delivered'] as $grant) {
    $operator->givePermissionTo(Permission::findOrCreate($grant, 'web'));
}
$operatorToken = $operator->createToken('csv-regression')->plainTextToken;
foreach (['deliver', 'return'] as $action) {
    $response = $uploadHttp('/api/orders/import-'.$action, ['preview' => true], "order_no\nCSV-VISIBLE\n", $operatorToken);
    $json = json_decode($response->getContent(), true);
    check($response->getStatusCode() === 200 && ($json['preview'][0]['current_status'] ?? '') === 'Not Found', 'restricted '.$action.' preview does not expose another creator order');
    $response = $uploadHttp('/api/orders/import-'.$action, [], "order_no\nCSV-VISIBLE\n", $operatorToken);
    check($response->getStatusCode() === 200 && DB::table('orders')->where('id', $csvOrder)->value('status') === 'dispatched', 'restricted '.$action.' CSV cannot mutate another creator order');
}
$qcReturn = DB::table('order_returns')->insertGetId(['return_no' => 'QC-CSV', 'order_id' => $csvOrder, 'status' => 'approved', 'reason' => 'CSV check']);
DB::table('order_return_items')->insert(['order_return_id' => $qcReturn, 'product_id' => $prod, 'requested_qty' => 1]);
$response = $uploadHttp('/api/returns/bulk-qc-import', ['is_preview' => true], "order_id\n{$csvOrder}\n", $operatorToken);
$json = json_decode($response->getContent(), true);
check($response->getStatusCode() === 400 && ! isset($json['returns']), 'restricted QC preview cannot expose another creator return');
$response = $uploadHttp('/api/returns/bulk-qc-import', [], "order_no,sku,received_qty,restocked_qty,damaged_qty\nCSV-VISIBLE,AUDIT,1,1,0\n", $operatorToken);
check($response->getStatusCode() === 400 && OrderReturn::find($qcReturn)->status === 'approved', 'restricted QC CSV cannot restock another creator return');
$response = $uploadHttp('/api/returns/bulk-qc-import', ['is_preview' => true], "order_id\n{$csvOrder}\n", $token);
check($response->getStatusCode() === 200 && count(json_decode($response->getContent(), true)['preview'] ?? []) === 1, 'authorized QC preview retains its existing item defaults');
// Complaint statistics obey the same assignee visibility as its list and stats endpoint.
$operator->givePermissionTo(Permission::findOrCreate('complaints.view', 'web'));
foreach ([$uid, $operator->id] as $assignee) {
    DB::table('order_complaints')->insert(['order_id' => $csvOrder, 'assigned_to' => $assignee, 'complaint_number' => 'RECHECK-'.$assignee, 'category' => 'Support', 'priority' => 'medium', 'status' => 'open', 'subject' => 'Visibility', 'description' => 'Fixture']);
}
$response = $http('GET', '/api/complaints?per_page=0', [], $operatorToken);
$json = json_decode($response->getContent(), true);
check($response->getStatusCode() === 200 && ($json['stats']['total'] ?? null) === 1 && ($json['complaints']['total'] ?? null) === 1, 'complaint totals and zero-page-size handling respect assignee visibility');
// Complete native chat exchange with a fresh default auth guard on every request.
$response = $http('POST', '/api/chat/conversations', ['type' => 'direct', 'user_id' => $operator->id], $token);
$chatId = json_decode($response->getContent(), true)['data']['id'] ?? null;
check($response->getStatusCode() === 201 && $chatId, 'native token creates a direct conversation');
$response = $http('POST', '/api/chat/conversations/'.$chatId.'/messages', ['content' => 'Workflow message'], $token);
$messageId = json_decode($response->getContent(), true)['data']['id'] ?? null;
check($response->getStatusCode() === 201 && $messageId, 'native token sends a chat message');
$response = $http('GET', '/api/chat/conversations/'.$chatId.'/messages', [], $operatorToken);
check($response->getStatusCode() === 200 && str_contains($response->getContent(), 'Workflow message'), 'recipient reads the native conversation');
$response = $http('PUT', '/api/chat/messages/'.$messageId, ['content' => 'Edited message'], $operatorToken);
check($response->getStatusCode() === 403, 'chat recipient cannot edit another sender message');
$response = $http('PUT', '/api/chat/messages/'.$messageId, ['content' => 'Edited message'], $token);
check($response->getStatusCode() === 200 && DB::table('chat_messages')->where('id', $messageId)->value('content') === 'Edited message', 'chat sender can edit their own message');
$response = $http('POST', '/api/chat/conversations/'.$chatId.'/read', ['message_id' => $messageId], $operatorToken);
check($response->getStatusCode() === 200 && DB::table('chat_message_reads')->where('message_id', $messageId)->where('user_id', $operator->id)->exists(), 'native read receipts are recorded');
$outsider = User::create(['name' => 'Chat outsider', 'email' => 'chat-outsider@example.invalid', 'password' => bcrypt('Example123!')]);
$outsiderToken = $outsider->createToken('chat-outsider')->plainTextToken;
$response = $http('GET', '/api/chat/conversations/'.$chatId.'/messages', [], $outsiderToken);
check($response->getStatusCode() === 404, 'non-member cannot read a private conversation');
$path = tempnam(sys_get_temp_dir(), 'metis-chat-upload-');
file_put_contents($path, 'private attachment');
try {
    $file = new class($path, 'attachment.txt', 'text/plain', null, true) extends UploadedFile
    {
        public bool $stored = false;

        public function store($path = '', $options = [])
        {
            $this->stored = true;
            throw new RuntimeException('Unauthorized attachment reached storage');
        }
    };
    $app['auth']->forgetGuards();
    $app['auth']->shouldUse('web');
    $request = Request::create('/api/chat/conversations/'.$chatId.'/messages', 'POST', ['content' => 'Intrusion'], [], ['files' => [$file]], ['HTTP_AUTHORIZATION' => 'Bearer '.$outsiderToken]);
    $response = $app->make(Kernel::class)->handle($request);
    check($response->getStatusCode() === 404 && ! $file->stored, 'non-member attachments are rejected before storage writes');
} finally {
    unlink($path);
}
$response = $http('DELETE', '/api/chat/messages/'.$messageId, [], $token);
check($response->getStatusCode() === 200 && DB::table('chat_messages')->where('id', $messageId)->value('deleted_at') !== null, 'native chat deletion preserves soft-delete behavior');
$cacheRoute = $app['router']->getRoutes()->match(Request::create('/clear-views'));
check(in_array('auth', $cacheRoute->gatherMiddleware(), true) && in_array('permission:settings-edit', $cacheRoute->gatherMiddleware(), true), 'view-cache maintenance requires authentication and settings-edit');
foreach (['disabled' => ['is_active' => false], 'suspended' => ['suspended_until' => now()->addMinutes(10)]] as $state => $attributes) {
    $blockedUser = User::create(array_merge(['name' => 'Blocked '.$state, 'email' => $state.'@example.invalid', 'password' => bcrypt('Example123!')], $attributes));
    $blockedToken = $blockedUser->createToken('blocked-regression')->plainTextToken;
    $response = $http('GET', '/api/auth/me', [], $blockedToken);
    check($response->getStatusCode() === 403 && $blockedUser->tokens()->count() === 0, $state.' user cannot continue with an old bearer token');
}
$villageId = DB::table('villages')->insertGetId(['village_name' => 'Workflow Village', 'normalized_name' => 'workflow village', 'pincode' => '380001', 'state_name' => 'Gujarat']);
$addressId = DB::table('party_addresses')->insertGetId(['party_id' => $party, 'village_id' => $villageId, 'village_name' => 'Workflow Village', 'state' => 'Gujarat', 'pincode' => '380001']);
$orderBody = ['type' => 'sale', 'party_id' => $party, 'warehouse_id' => $wh, 'shipping_address_id' => $addressId, 'billing_address_id' => $addressId, 'order_date' => '2026-10-27', 'status' => 'pending', 'items' => [['product_id' => $prod, 'quantity' => 1, 'unit_price' => 1]], 'total_amount' => 1, 'tax_amount' => 0, 'discount_amount' => 0, 'net_amount' => 1];
$response = $http('POST', '/api/orders/preview', $orderBody, $token);
$json = json_decode($response->getContent(), true);
check($response->getStatusCode() === 200 && (float) ($json['quote']['grand_total'] ?? 0) === 100.0, 'native order preview ignores manipulated client pricing');
$response = $http('POST', '/api/orders', $orderBody, $token);
$json = json_decode($response->getContent(), true);
$nativeOrderId = $json['order']['id'] ?? null;
check($response->getStatusCode() === 200 && $nativeOrderId && (float) ($json['order']['net_amount'] ?? 0) === 100.0, 'native order creation persists authoritative pricing');
$response = $http('GET', '/api/orders/'.$nativeOrderId.'/edit-options', [], $token);
$json = json_decode($response->getContent(), true);
$stockRows = $json['initialOrder']['items'][0]['product']['warehouse_stocks'] ?? [];
check($response->getStatusCode() === 200 && count($stockRows) > 0 && ! array_key_exists(0, $stockRows[0]), 'populated native edit options expose clean warehouse stock fields');
$beforeCount = DB::table('orders')->count();
$response = $http('POST', '/api/orders', array_replace($orderBody, ['status' => 'delivered']), $token);
check($response->getStatusCode() === 422 && DB::table('orders')->count() === $beforeCount, 'native create cannot bypass delivery lifecycle or leave a partial order');

$response = $http('GET', '/api/invoices/'.$csvInvoice.'/pdf', [], $token);
check($response->getStatusCode() === 200 && str_contains($response->headers->get('Content-Type', ''), 'application/pdf') && str_starts_with($response->getContent(), '%PDF-'), 'native invoice download generates a real PDF document');
