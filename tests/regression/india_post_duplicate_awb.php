<?php

require __DIR__.'/bootstrap.php';
use App\Models\IndiaPostAwbTracking;
use App\Modules\Orders\Models\Order;
use App\Services\InventoryService;
use App\Services\Shipping\Providers\IndiaPostProvider;
use App\Services\Shipping\ShippingManager;
use GuzzleHttp\Psr7\Response as PsrResponse;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

DB::statement('SET FOREIGN_KEY_CHECKS=0');
foreach (json_decode(file_get_contents(database_path('transitions/metis-20261029/audited-target-schema.json')), true) as $ddl) {
    DB::unprepared($ddl);
}DB::statement('SET FOREIGN_KEY_CHECKS=1');
class AwbTestProvider extends IndiaPostProvider
{
    public array $sent = [];

    public $respond;

    public function __construct()
    {
        $this->baseUrl = 'https://carrier.invalid';
    }

    protected function apiRequest(string $method, string $url, array $data = [], array $options = [])
    {
        $this->sent[] = $data;

        return ($this->respond)($data, count($this->sent));
    }
}
function awbResponse(array $data, int $status = 200): Response
{
    return new Response(new PsrResponse($status, [], json_encode($data)));
}
function duplicateResponse(string $barcode, array $errors = ['Duplicate article: Already booked today or yesterday']): Response
{
    return awbResponse(['success' => true, 'valid_articles' => [], 'error_articles' => [['barcode_no' => $barcode, 'errors' => $errors]]]);
}
function fixtureOrder(): Order
{
    $o = new Order;
    $o->forceFill(['id' => 9001, 'net_amount' => 150, 'total_paid_amount' => 25, 'shipping_address_line_1' => 'Test receiver address', 'shipping_address_line_2' => '', 'shipping_city' => 'Test city', 'shipping_state' => 'Gujarat', 'shipping_pincode' => '380001']);
    $o->setRelation('items', collect());
    $o->setRelation('warehouse', (object) ['name' => 'Test warehouse', 'company_name' => 'Test', 'phone' => '9000000001', 'address_line_1' => 'Test sender address', 'address_line_2' => '', 'city' => 'Test city', 'state' => 'Gujarat', 'pincode' => '380001']);
    $o->setRelation('party', (object) ['firstname' => 'Test', 'lastname' => 'Recipient', 'company_name' => '', 'phone' => '9000000002']);

    return $o;
}
config(['shipping.providers.india_post.bulk_customer_id' => 'TEST', 'shipping.providers.india_post.contracts.BUSINESS_PARCEL' => 'TEST', 'shipping.providers.india_post.pickup_dropoff_office_id' => '12345678', 'shipping.providers.india_post.drop_off_pincode' => '380001']);
$range = DB::table('india_post_barcode_ranges')->insertGetId(['office_id' => 'default', 'prefix' => 'CX', 'start_sequence' => 7963620, 'current_sequence' => 7964158, 'end_sequence' => 7968619, 'status' => 'active']);
$p = new AwbTestProvider;
$generate = new ReflectionMethod(IndiaPostProvider::class, 'generateBarcode');
$codes = [];
for ($i = 0; $i < 3; $i++) {
    DB::beginTransaction();
    $codes[] = $generate->invoke($p, 9001 + $i);
    DB::rollBack();
}
check(count(array_unique($codes)) === 3 && IndiaPostAwbTracking::count() === 3, 'order rollback cannot reuse committed barcode allocations');
check($codes[0] === 'CX079641581IN', 'existing barcode/check-digit format is preserved');
DB::table('india_post_barcode_ranges')->where('id', $range)->update(['current_sequence' => 7964158]);
$next = $generate->invoke($p, 9010);
check(! in_array($next, $codes, true), 'stale counter skips existing allocated barcodes');
// Reserve a historical shipment without an AWB allocation row.
DB::statement('SET FOREIGN_KEY_CHECKS=0');
DB::table('shipments')->insert(['order_id' => 9000, 'shipment_no' => 'TEST-HISTORICAL', 'tracking_no' => 'CX079641581IN', 'status' => 'pending']);
DB::statement('SET FOREIGN_KEY_CHECKS=1');
DB::table('india_post_awb_trackings')->where('barcode', $codes[0])->delete();
DB::table('india_post_barcode_ranges')->where('id', $range)->update(['current_sequence' => 7964158]);
$next = $generate->invoke($p, 9011);
check($next !== $codes[0], 'historical shipment barcodes are skipped even without allocation records');
$p = new AwbTestProvider;
$p->respond = fn ($data, $n) => $n === 1 ? duplicateResponse($data['articles'][0]['barcode_no']) : awbResponse(['success' => true, 'valid_articles' => [['barcode_no' => $data['articles'][0]['barcode_no'], 'calculated_tariff' => 45]]]);
DB::beginTransaction();
$result = $p->createShipment(fixtureOrder());
DB::rollBack();
check(count($p->sent) === 2 && $p->sent[0]['articles'][0]['barcode_no'] !== $p->sent[1]['articles'][0]['barcode_no'], 'explicit already-booked rejection retries with a new barcode');
$first = $p->sent[0]['articles'][0];
$second = $p->sent[1]['articles'][0];
unset($first['barcode_no'],$second['barcode_no']);
check($first === $second && $second['value_for_codr_cod'] === 125.0 && $result['shipping_cost'] === 45, 'retry preserves COD, address, parcel payload and tariff mapping');
check(IndiaPostAwbTracking::where('barcode', $p->sent[0]['articles'][0]['barcode_no'])->value('status') === 'rejected_duplicate', 'remote rejected barcode remains consumed after order rollback');
$cases = [
    'address validation' => fn ($data) => duplicateResponse($data['articles'][0]['barcode_no'], ['Receiver pincode invalid']),
    'mixed validation' => fn ($data) => duplicateResponse($data['articles'][0]['barcode_no'], ['Duplicate article: Already booked today or yesterday', 'Invalid weight']),
    'different article' => fn () => duplicateResponse('CX000000005IN'),
    'server failure' => fn ($data) => awbResponse(['success' => false, 'error_articles' => [['barcode_no' => $data['articles'][0]['barcode_no'], 'errors' => ['Duplicate article: Already booked today or yesterday']]]], 500),
    'network timeout' => fn () => throw new RuntimeException('simulated timeout'),
];
foreach ($cases as $label => $respond) {
    $p = new AwbTestProvider;
    $p->respond = $respond;
    $failed = false;
    try {
        $p->createShipment(fixtureOrder());
    } catch (Exception) {
        $failed = true;
    }check($failed && count($p->sent) === 1, $label.' is never automatically rebooked');
}
$p = new AwbTestProvider;
$p->respond = fn ($data) => duplicateResponse($data['articles'][0]['barcode_no']);
$failed = false;
try {
    $p->createShipment(fixtureOrder());
} catch (Exception) {
    $failed = true;
}
check($failed && count($p->sent) === 3 && count(array_unique(array_map(fn ($p) => $p['articles'][0]['barcode_no'], $p->sent))) === 3, 'duplicate recovery stops after three distinct attempts');
$last = DB::table('india_post_barcode_ranges')->where('id', $range)->value('end_sequence');
DB::table('india_post_barcode_ranges')->where('id', $range)->update(['current_sequence' => $last]);
$queued = DB::table('india_post_barcode_ranges')->insertGetId(['office_id' => 'default', 'prefix' => 'CX', 'start_sequence' => 8000000, 'current_sequence' => 8000000, 'end_sequence' => 8000001, 'status' => 'queued']);
$lastCode = $generate->invoke(new AwbTestProvider, 9013);
$newCode = $generate->invoke(new AwbTestProvider, 9014);
check(str_starts_with($lastCode, 'CX07968619') && str_starts_with($newCode, 'CX08000000') && DB::table('india_post_barcode_ranges')->where('id', $queued)->value('status') === 'active', 'range exhaustion activates the next queued range without changing barcode format');
DB::table('india_post_barcode_ranges')->update(['status' => 'exhausted']);
$failed = false;
try {
    $generate->invoke(new AwbTestProvider, 9015);
} catch (RuntimeException) {
    $failed = true;
}check($failed, 'exhausted configured ranges never fall back to unrelated legacy barcode ranges');

// Exercise the real ready-to-ship transaction without contacting a carrier.
DB::table('india_post_barcode_ranges')->where('id', $range)->update(['status' => 'active', 'current_sequence' => 7964200]);
$warehouse = DB::table('warehouses')->insertGetId(['name' => 'Test warehouse', 'phone' => '9000000001', 'address_line_1' => 'Test sender address', 'city' => 'Test city', 'state' => 'Gujarat', 'pincode' => '380001']);
$party = DB::table('parties')->insertGetId(['type' => 'customer', 'firstname' => 'Test', 'lastname' => 'Recipient', 'phone' => '9000000002']);
$attributes = fixtureOrder()->getAttributes();
unset($attributes['id'],$attributes['total_paid_amount']);
$id = DB::table('orders')->insertGetId(array_merge($attributes, ['order_no' => 'TEST-READY-SHIP', 'type' => 'sale', 'party_id' => $party, 'warehouse_id' => $warehouse, 'order_date' => now(), 'status' => 'processing']));
$provider = new AwbTestProvider;
$provider->respond = fn ($data) => duplicateResponse($data['articles'][0]['barcode_no']);
$manager = new class($provider) extends ShippingManager
{
    public function __construct(public AwbTestProvider $testProvider) {}

    public function driver($driver = null)
    {
        return $this->testProvider;
    }
};
app()->instance(ShippingManager::class, $manager);
$inventory = app(InventoryService::class);
$failed = false;
try {
    $inventory->readyToShipOrder(Order::findOrFail($id), 'India Post');
} catch (ValidationException) {
    $failed = true;
}
check($failed && DB::table('orders')->where('id', $id)->value('status') === 'processing' && ! DB::table('shipments')->where('order_id', $id)->exists(), 'failed duplicate recovery leaves order processing and creates no shipment');
$provider = new AwbTestProvider;
$provider->respond = fn ($data, $n) => $n === 1 ? duplicateResponse($data['articles'][0]['barcode_no']) : awbResponse(['success' => true, 'valid_articles' => [['barcode_no' => $data['articles'][0]['barcode_no'], 'calculated_tariff' => 45]]]);
$manager->testProvider = $provider;
$inventory->readyToShipOrder(Order::findOrFail($id), 'India Post');
check(DB::table('orders')->where('id', $id)->value('status') === 'ready_to_ship' && DB::table('shipments')->where('order_id', $id)->count() === 1 && DB::table('shipments')->where('order_id', $id)->value('tracking_no') === $provider->sent[1]['articles'][0]['barcode_no'], 'recovered booking transitions order once and saves the accepted tracking number');
check(DB::table('stock_movements')->count() === 0 && DB::table('stock_reservations')->count() === 0, 'ready-to-ship recovery does not introduce stock movements or reservation changes');
$workers = [];
$environment = array_merge(getenv(), ['METIS_EXISTING_TEST_DB' => $testDatabase]);
for ($i = 0; $i < 2; $i++) {
    $process = proc_open([PHP_BINARY, __DIR__.'/concurrency_worker.php', 'awb', '[]'], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, null, $environment);
    fclose($pipes[0]);
    $workers[] = [$process, $pipes];
}
$parallel = [];
foreach ($workers as [$process,$pipes]) {
    $output = stream_get_contents($pipes[1]);
    $error = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    if (proc_close($process) !== 0) {
        throw new RuntimeException('AWB worker failed: '.$error);
    }$parallel[] = json_decode($output,true,512,JSON_THROW_ON_ERROR)['result'];
}
check(count(array_unique($parallel)) === 2,'parallel booking workers allocate distinct barcodes');
echo "India Post duplicate AWB regressions complete.\n";
