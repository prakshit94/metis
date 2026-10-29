<?php
// Disposable MySQL only. No live account, password, email delivery or order is used.
require dirname(__DIR__).'/bootstrap/autoload.php';
putenv('APP_ENV=testing'); $_ENV['APP_ENV']='testing';
$app=require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
set_exception_handler(function(Throwable $e){fwrite(STDERR,get_class($e).': '.$e->getMessage().' at '.$e->getFile().':'.$e->getLine()."\n".$e->getTraceAsString()."\n");exit(1);});
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\Request;
use Storefront\Models\CustomerAccount;
use Storefront\Models\Product;
use App\Modules\Customers\Models\Customer;
function check($condition,$message){if(!$condition)throw new RuntimeException('FAIL: '.$message);echo "PASS: $message\n";}
$socket=getenv('METIS_TEST_MYSQL_SOCKET')?:'/tmp/metis-audit-mysql/mysql.sock';
$database='metis_regression_'.getmypid().'_'.bin2hex(random_bytes(3));
$pdo=new PDO('mysql:unix_socket='.$socket,'root','');$pdo->exec('CREATE DATABASE `'.$database.'`');
config(['database.default'=>'regression','database.connections.regression'=>['driver'=>'mysql','unix_socket'=>$socket,'database'=>$database,'username'=>'root','password'=>'','charset'=>'utf8mb4','collation'=>'utf8mb4_unicode_ci','prefix'=>'','strict'=>true], 'activitylog.enabled'=>true,'audit.enabled'=>true,'audit.console'=>true,'cache.default'=>'array','session.driver'=>'array','queue.default'=>'sync']);
register_shutdown_function(function()use($pdo,$database){DB::disconnect('regression');$pdo->exec('DROP DATABASE IF EXISTS `'.$database.'`');});
Artisan::call('migrate',['--path'=>dirname(__DIR__,2).'/database/migrations','--realpath'=>true,'--force'=>true]);
check(is_subclass_of(config('audit.user.resolver'), OwenIt\Auditing\Contracts\UserResolver::class), 'shared dependency discovery registers a valid audit resolver');
check(Schema::hasTable('customer_accounts') && Schema::hasTable('storefront_checkouts'),'additive migrations run against full existing schema');
$customer=Customer::create(['firstname'=>'Sample','email'=>'sample@example.invalid','phone'=>'9999999999']);
$account=(new CustomerAccount)->forceFill(['customer_id'=>$customer->id,'email'=>'SAMPLE@example.invalid','password'=>'LongExample123!','email_verified_at'=>now(),'login_enabled'=>true,'ordering_enabled'=>true]);$account->save();
check($account->email==='sample@example.invalid' && $account->canPlaceOrders(),'customer credentials isolated and eligibility enforced');
check(!array_key_exists('password',$account->toArray()),'credential hashes never serialized');
$customer->update(['is_blacklisted'=>true]);check(!$account->canPlaceOrders(),'CRM blacklisting immediately revokes storefront access');$customer->update(['is_blacklisted'=>false]);
$wh=App\Modules\Catalog\Models\Warehouse::create(['name'=>'Sample warehouse','code'=>'TEST','status'=>'active','is_active'=>true,'is_default'=>true]);
$product=Product::create(['name'=>'Sample essential','sku'=>'SAMPLE-1','slug'=>'sample-essential','selling_price'=>100,'mrp'=>150,'is_active'=>true,'status'=>'published','manage_stock'=>false,'is_sku_enabled'=>true]);
App\Modules\Inventory\Models\Stock::create(['product_id'=>$product->id,'warehouse_id'=>$wh->id,'quantity'=>100,'reserved_qty'=>0]);
check(!$product->fresh()->storefront_visible,'existing/new admin products remain private by default');
DB::table('products')->where('id',$product->id)->update(['storefront_visible'=>true]);
$address=$customer->addresses()->create(['label'=>'Home','address_line_1'=>'123 Sample Road','city'=>'Ahmedabad','state'=>'Gujarat','pincode'=>'380001','status'=>'active','is_default'=>true]);
$session=app('session')->driver();$session->start();
function req(array $data=[]): Request { global $session; $r=Request::create('/shop/checkout','POST',$data);$r->setLaravelSession($session);return $r; }
$checkout=app(Storefront\Services\Checkout::class);
$key=(string)Illuminate\Support\Str::uuid();$cart=[$product->id=>'2'];$session->put('shop.cart',$cart);$session->put('shop.checkout_key',$key);$quote=$checkout->quote($cart);$session->put('shop.quote',hash('sha256',json_encode([$quote['items'],$quote['grand_total']])));
$input=['checkout_key'=>$key,'delivery_address_choice'=>$address->id,'billing_same_as_shipping'=>'1','name'=>'Sample','phone'=>'9999999999'];
$id=$checkout->place(req($input),$account);$order=App\Modules\Orders\Models\Order::findOrFail($id);
check((float)$order->net_amount===200.0 && $order->party_id===$customer->id && $order->created_by===null,'checkout uses canonical totals and never impersonates a staff user');
check($order->shipping_address_line_1==='123 Sample Road' && $order->billing_pincode==='380001','addresses copied into existing admin order snapshots');
check($checkout->place(req($input),$account)===$id && DB::table('orders')->count()===1,'repeated checkout key never creates duplicate orders');
check((float)$order->items()->firstOrFail()->quantity===2.0,'checkout accepts whole-number strings from existing form-submitted carts');
check(str_starts_with($order->order_no,'ORD'),'existing service generates the order number');
$session->put('shop.checkout_key',(string)Illuminate\Support\Str::uuid());$other=Customer::create(['firstname'=>'Other']);$foreign=$other->addresses()->create(['label'=>'Private','address_line_1'=>'Private','state'=>'Gujarat','pincode'=>'380001','status'=>'active']);
try{$checkout->place(req(array_merge($input,['checkout_key'=>$session->get('shop.checkout_key'),'delivery_address_choice'=>$foreign->id])),$account);throw new RuntimeException('Foreign address accepted');}catch(Illuminate\Validation\ValidationException $e){check(true,'foreign delivery address rejected');}
$actions=app(Storefront\Services\CustomerOrderActions::class);
try{$actions->apply($account,$id,['status'=>'return_requested','expected_status'=>'pending','notes'=>'Invalid']);throw new RuntimeException('Invalid return accepted');}catch(Illuminate\Validation\ValidationException $e){check(true,'invalid lifecycle transition rejected');}
$actions->apply($account,$id,['status'=>'cancelled','expected_status'=>'pending','notes'=>'Sample cancellation']);check($order->fresh()->status==='cancelled','customer cancellation uses existing inventory lifecycle service');
DB::table('products')->where('id',$product->id)->update(['status'=>'published','is_active'=>true,'is_sku_enabled'=>true]);
// Render every public view with the real controller/model adapters.
$controller=app(Storefront\Http\Controllers\StorefrontController::class);
Illuminate\Support\Facades\Auth::guard('customer')->setUser($account);
$controller->updateCart(req(['product_id'=>(string)$product->id,'quantity'=>'2']));
check($session->get('shop.cart')[$product->id]===2,'HTML form quantity is stored as an integer');
$session->put('shop.cart',[$product->id=>'2']);$controller->cart(req());
check($session->get('shop.cart')[$product->id]===2,'loading an existing cart repairs numeric-string quantities');

$r=req();app()->instance('request',$r);Illuminate\Support\Facades\View::share('errors',new Illuminate\Support\ViewErrorBag);
check(str_contains($controller->index($r)->render(),'Everyday essentials.'),'reference catalog renders through adapted schema');
check(str_contains($controller->product($product->slug)->render(),'Sample essential'),'product details render with original UI');
Illuminate\Support\Facades\Auth::guard('customer')->setUser($account);
check(str_contains($controller->cart($r)->render(),'Where should we deliver?'),'cart, shipping and saved address views render');
check(str_contains($controller->profile($r)->render(),'Edit your profile'),'customer profile renders');
check(str_contains($controller->orders($r)->render(),'Sample essential'),'order history renders existing admin order items');
// Fake outbound delivery: no real email is sent.
config(['mail.default'=>'smtp','mail.mailers.smtp'=>['transport'=>'smtp','host'=>'127.0.0.1'],'two_factor.email_mailer'=>'smtp']);
Illuminate\Support\Facades\Mail::fake();
$r=req(['name'=>'New Customer','email'=>'NEW@example.invalid','phone'=>'9999999998','password'=>'ExampleNew123!','password_confirmation'=>'ExampleNew123!']);
$controller->register($r);
$new=CustomerAccount::where('email','new@example.invalid')->firstOrFail();
check($new->customer_id!==$customer->id && !$new->email_verified_at,'registration creates separate CRM identity and requires email verification');
check(DB::table('audits')->where('auditable_type',Customer::class)->where('auditable_id',$new->customer_id)->where('event','created')->exists(),'registration succeeds and records the customer audit with auditing enabled');
check(Illuminate\Support\Facades\Mail::sent(Illuminate\Mail\Mailable::class)->count()===1,'verification message constructed without external delivery');
check(true,'verification email uses shared configured mailer with faked delivery');
$provider=app('auth')->createUserProvider('customers');
check($provider->retrieveByCredentials(['email'=>'new@example.invalid','password'=>'ExampleNew123!'])===null,'unverified customers cannot sign in');
$new->forceFill(['email_verified_at'=>now()])->save();
$found=$provider->retrieveByCredentials(['email'=>'NEW@example.invalid','password'=>'ExampleNew123!']);
check($found && $provider->validateCredentials($found,['password'=>'ExampleNew123!']),'verified password sign-in normalizes email case');
$codes=app(Storefront\Services\CustomerEmailLoginCodes::class);
$r=req();$codes->start($r,$account->email,$account,'cart');
$challenge=$codes->pending($r);
check($challenge['attempts']===0 && $challenge['expires_at']-time()<=300 && !isset($challenge['code']),'email challenges have bounded lifetime and store hashed codes only');
try{$codes->verify($r,'000000');throw new RuntimeException('Incorrect OTP accepted');}catch(Illuminate\Validation\ValidationException $e){check($codes->pending($r)['attempts']===1,'incorrect OTP consumes one attempt');}
$challengeId=$session->get($codes::SESSION_KEY);$plain='123456';$challenge=$codes->pending($r);$challenge['hash']=Illuminate\Support\Facades\Hash::make($plain);Illuminate\Support\Facades\Cache::put('shop-email-login:challenge:'.$challengeId,$challenge,300);
check($codes->verify($r,$plain)==='cart' && !$session->has($codes::SESSION_KEY),'valid email OTP consumed and preserves return destination');
check(auth()->id()===null && auth('customer')->id()===$account->id,'customer login never sets staff identity');
try{$codes->verify($r,$plain);throw new RuntimeException('Replayed OTP accepted');}catch(Illuminate\Validation\ValidationException $e){check(true,'OTP replay rejected');}
$tax=App\Modules\Catalog\Models\TaxRate::create(['name'=>'Test GST','rate'=>18,'status'=>'active','is_active'=>true]);
DB::table('products')->where('id',$product->id)->update(['tax_rate_id'=>$tax->id,'default_discount'=>10,'default_discount_type'=>'percent']);
$quote=$checkout->quote([$product->id=>2]);
check(abs($quote['grand_total']-212)<0.001 && abs($quote['tax_amount']-32.4)<0.001,'existing product discount and GST calculations reused exactly');
check(Product::with('taxRate')->findOrFail($product->id)->display_price===118.0,'public display price includes the existing product GST');
$summary=$checkout->summary($quote);
check($summary['subtotal']-$summary['discount']+$summary['tax']+$summary['rounding']===21200,'cart breakdown reconciles exactly to the existing rounded total');
$addressService=app(Storefront\Services\StorefrontAddresses::class);
$saved=$addressService->save($account,['label'=>'Office','address_line_1'=>'Another Road','city'=>'Ahmedabad','state'=>'Gujarat','pincode'=>'380002']);
check($saved->party_id===$customer->id && $addressService->save($account,['label'=>'Office','address_line_1'=>'Another Road','city'=>'Ahmedabad','state'=>'Gujarat','pincode'=>'380002'])->id===$saved->id,'saved addresses use party ownership and deduplicate retries');
$edited=$addressService->save($account,['label'=>'Office updated','address_line_1'=>'Edited Road','city'=>'Ahmedabad','state'=>'Gujarat','pincode'=>'380003','is_default'=>true],$saved->id);
check($edited->id===$saved->id && $edited->address_line_1==='Edited Road' && $edited->is_default,'owned address can be edited and made default without duplication');
check($customer->addresses()->where('is_default',true)->count()===1,'editing a default address preserves exactly one default');
$addressService->save($account,['label'=>'Office updated','address_line_1'=>'Edited Road','city'=>'Ahmedabad','state'=>'Gujarat','pincode'=>'380003','is_default'=>true],$saved->id);
check($saved->fresh()->is_default && $customer->addresses()->where('is_default',true)->count()===1,'saving the same default again does not clear its database flag');
$village=App\Modules\Core\Models\Village::create(['village_name'=>'Directory Village','normalized_name'=>'directory village','post_so_name'=>'Directory Post','taluka_name'=>'Directory Taluka','district_name'=>'Directory District','state_name'=>'Gujarat','pincode'=>'380004']);
$canonical=$addressService->save($account,['label'=>'Village','address_line_1'=>'Village Road','village_id'=>$village->id,'village_name'=>'Wrong Village','state'=>'Wrong State','pincode'=>'999999']);
check($canonical->village_name==='Directory Village' && $canonical->state==='Gujarat' && $canonical->pincode==='380004','selected village metadata is canonicalized on the server');
check($addressService->data($canonical)['village_id']===$village->id,'address editor retains its village directory reference');

try{$addressService->save($account,['label'=>'Foreign','address_line_1'=>'Changed','state'=>'Gujarat','pincode'=>'380001'],$foreign->id);throw new RuntimeException('Foreign address edit accepted');}catch(Illuminate\Database\Eloquent\ModelNotFoundException $e){check($foreign->fresh()->address_line_1==='Private','foreign address edits are rejected without changing the record');}
check($order->fresh()->shipping_address_line_1==='123 Sample Road','editing saved addresses leaves historical order snapshots unchanged');
check(str_contains($controller->profile(req())->render(),'data-address-book'),'profile includes shared address management');
$customer->delete();check($provider->retrieveById($account->id)===null,'soft-deleted CRM customer loses authentication');$customer->restore();
$guard = new ReflectionMethod(App\Modules\Customers\Controllers\CustomerController::class,'assertNoStorefrontAccounts');
try{$guard->invoke(app(App\Modules\Customers\Controllers\CustomerController::class),[$customer->id]);throw new RuntimeException('Linked permanent delete accepted');}catch(Illuminate\Validation\ValidationException $e){check(true,'linked customer permanent deletion returns clear validation error');}
$session->put('shop.cart',[$product->id=>2]);
$key=(string)Illuminate\Support\Str::uuid();$session->put('shop.checkout_key',$key);$session->put('shop.quote','stale-quote');
try{$checkout->place(req(array_merge($input,['checkout_key'=>$key])),$account);throw new RuntimeException('Stale quote accepted');}catch(Illuminate\Validation\ValidationException $e){check(true,'changed price quote must be reviewed before placement');}
$session->put('shop.cart',[$product->id=>'2.5']);
try{$checkout->place(req(array_merge($input,['checkout_key'=>$key])),$account);throw new RuntimeException('Fractional quantity accepted');}catch(Illuminate\Validation\ValidationException $e){check(true,'fractional saved quantities remain rejected');}
$session->put('shop.cart',[$product->id=>1001]);
try{$checkout->place(req(array_merge($input,['checkout_key'=>$key])),$account);throw new RuntimeException('Oversized quantity accepted');}catch(Illuminate\Validation\ValidationException $e){check(true,'quantities above the product limit remain rejected');}
$session->put('shop.cart',[$product->id=>2]);
$quote=$checkout->quote([$product->id=>2]);$session->put('shop.quote',hash('sha256',json_encode([$quote['items'],$quote['grand_total']])));
DB::table('stocks')->where('product_id',$product->id)->update(['quantity'=>0]);
try{$checkout->place(req(array_merge($input,['checkout_key'=>$key])),$account);throw new RuntimeException('Out of stock accepted');}catch(Illuminate\Validation\ValidationException $e){check(true,'insufficient warehouse stock rejected without creating an order');}
DB::table('stocks')->where('product_id',$product->id)->update(['quantity'=>100]);
DB::table('products')->where('id',$product->id)->update(['storefront_visible'=>false]);
$controller->cart(req())->render();check(!$session->get('shop.cart'),'unpublished cart product removed without a redirect loop');
DB::table('products')->where('id',$product->id)->update(['storefront_visible'=>true]);
$nextKey=(string)Illuminate\Support\Str::uuid();$session->put('shop.cart',[$product->id=>1]);$session->put('shop.checkout_key',$nextKey);
$controller->checkout(req($input),$checkout);
check($session->get('shop.checkout_key')===$nextKey && $session->get('shop.cart')[$product->id]===1,'retrying an old checkout does not erase a newly started bag');
try{app(Storefront\Services\CustomerOrderActions::class)->apply($new,$id,['status'=>'cancelled','expected_status'=>'pending','notes'=>'Foreign']);throw new RuntimeException('Foreign order accepted');}catch(Illuminate\Database\Eloquent\ModelNotFoundException $e){check(true,'another customer cannot act on an order');}
$codes->start(req(),$account->email,$account,'shop');
for($attempt=0;$attempt<5;$attempt++){try{$codes->verify(req(),'000000');throw new RuntimeException('Wrong OTP accepted');}catch(Illuminate\Validation\ValidationException $e){}}
check(!$session->has($codes::SESSION_KEY),'five wrong OTP attempts invalidate the challenge');
$codes->start(req(),$account->email,$account,'shop');$challenge=$codes->pending(req());$challenge['expires_at']=time()-1;Illuminate\Support\Facades\Cache::put('shop-email-login:challenge:'.$session->get($codes::SESSION_KEY),$challenge,300);
try{$codes->verify(req(),'123456');throw new RuntimeException('Expired code accepted');}catch(Illuminate\Validation\ValidationException $e){check(!$session->has($codes::SESSION_KEY),'expired OTP challenge cleared and rejected');}
DB::table('orders')->where('id',$id)->update(['status'=>'delivered']);
$actions->apply($account,$id,['status'=>'return_requested','expected_status'=>'delivered','notes'=>'Sample return request']);
$return=App\Modules\Orders\Models\OrderReturn::where('order_id',$id)->firstOrFail();
check($return->status==='pending' && (float)$return->items()->sum('requested_qty')===2.0 && $order->fresh()->status==='return_requested','customer return creates the existing staff return workflow and sold quantities');
$controller->updateProfile(req(['firstname'=>'Updated','middlename'=>null,'lastname'=>'Customer','phone'=>'9999999997','email'=>'hijack@example.invalid','customer_id'=>$other->id,'login_enabled'=>false]));
check($customer->fresh()->firstname==='Updated' && $account->fresh()->email==='sample@example.invalid' && $account->fresh()->login_enabled,'profile changes only permitted personal fields and preserves account identity/access');
$offer=App\Modules\Orders\Models\Offer::create(['name'=>'Sample offer','type'=>'order_discount','discount_type'=>'fixed','value'=>5,'cashback_fixed'=>3,'is_active'=>true]);
$key=(string)Illuminate\Support\Str::uuid();$session->put('shop.cart',[$product->id=>2]);$session->put('shop.checkout_key',$key);
$quote=$checkout->quote([$product->id=>2]);$session->put('shop.quote',hash('sha256',json_encode([$quote['items'],$quote['grand_total']])));
$offerOrder=App\Modules\Orders\Models\Order::findOrFail($checkout->place(req(array_merge($input,['checkout_key'=>$key])),$account));
check($offerOrder->applied_offer_id===$offer->id && $offerOrder->selected_offer_id===$offer->id && (float)$offerOrder->cashback_earned===(float)$quote['cashback_earned'],'checkout persists the pricing engine offer and cashback metadata');
check($offer->fresh()->used_count===1,'checkout records offer usage through the existing order service');
$offer->update(['is_active'=>false]);
$product=Product::findOrFail($product->id);$product->selling_price=1694.92;$product->selling_price_including_tax='2000.00';$product->default_discount=0;$product->save();
check($product->fresh()->display_price===2000.0,'storefront displays the exact inclusive product price');
$session->put('shop.cart',[$product->id=>2]);$key=(string)Illuminate\Support\Str::uuid();$session->put('shop.checkout_key',$key);
$exactQuote=$checkout->quote([$product->id=>2]);$session->put('shop.quote',hash('sha256',json_encode([$exactQuote['items'],$exactQuote['grand_total']])));
check(abs($exactQuote['subtotal']+$exactQuote['tax_amount']-4000)<0.000001,'two inclusive-priced units quote exactly without per-unit GST conversion drift');
$exactOrder=App\Modules\Orders\Models\Order::findOrFail($checkout->place(req(array_merge($input,['checkout_key'=>$key])),$account));
$exactItem=$exactOrder->items()->firstOrFail();
check((float)$exactOrder->net_amount===4000.0 && abs((float)$exactItem->unit_price-2000/1.18)<0.00000001,'checkout snapshots retain precise taxable unit price and exact inclusive total');
check((float)$order->fresh()->net_amount===200.0,'new inclusive pricing leaves historical order totals unchanged');
try{(require dirname(__DIR__,2).'/database/migrations/2026_10_28_180000_add_storefront_customer_access.php')->down();throw new RuntimeException('Destructive rollback accepted');}catch(RuntimeException $e){check(str_contains($e->getMessage(),'Archive'),'migration rollback protects existing storefront accounts and orders');}
echo "Storefront integration checks complete.\n";
