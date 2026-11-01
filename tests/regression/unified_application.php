<?php

require __DIR__.'/bootstrap.php';

use App\Modules\Orders\Models\Order;
use App\Modules\Users\Models\User;
use App\Modules\Users\Models\Role;
use App\Storefront\Models\CustomerAccount;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

Artisan::call('migrate', ['--force'=>true]);
$compiled='/tmp/metis-unified-views-'.getmypid();
$locks='/tmp/metis-unified-locks-'.getmypid();
mkdir($compiled,0700,true);mkdir($locks,0700,true);
config(['view.compiled'=>$compiled, 'cache.stores.file.path'=>$locks, 'cache.stores.file.lock_path'=>$locks]);
register_shutdown_function(function() use ($compiled,$locks) { \Illuminate\Support\Facades\File::deleteDirectory($compiled); \Illuminate\Support\Facades\File::deleteDirectory($locks); });
$staff=User::forceCreate(['name'=>'Unified staff', 'email'=>'unified-staff@example.invalid','password'=>'Synthetic123!', 'is_active'=>true]);
$staff->assignRole(Role::findOrCreate('Super Admin','web'));
$customer=\App\Modules\Customers\Models\Customer::create(['firstname'=>'Unified buyer','email'=>'unified-buyer@example.invalid','phone'=>'9999999999']);
$account=(new CustomerAccount)->forceFill(['customer_id'=>$customer->id,'email'=>$customer->email,'password'=>'Synthetic123!','email_verified_at'=>now(),'login_enabled'=>true,'ordering_enabled'=>true]);$account->save();
$address=$customer->addresses()->create(['label'=>'Home','address_line_1'=>'123 Sample Road','state'=>'Gujarat','pincode'=>'380001','status'=>'active','is_default'=>true]);
$warehouse=DB::table('warehouses')->insertGetId(['name'=>'Unified warehouse','status'=>'active','is_active'=>true,'is_default'=>true]);
$product=\App\Modules\Catalog\Models\Product::create(['name'=>'Unified item','slug'=>'unified-item','sku'=>'UNIFIED-1','selling_price'=>100,'status'=>'published','is_active'=>true,'storefront_visible'=>true,'is_sku_enabled'=>true]);
DB::table('stocks')->insert(['product_id'=>$product->id,'warehouse_id'=>$warehouse,'quantity'=>50]);
$jar=[];
function unifiedHttp(string $uri,string $method='GET',array $data=[],?array $cookies=null,array $headers=[])
{
    global $app,$jar;
    auth()->forgetGuards();auth()->shouldUse('web');
    app('session')->driver()->flush();
    $request=Request::create($uri,$method,$data,$cookies ?? $jar,[],array_merge(['HTTP_ACCEPT'=>'text/html'],$headers));
    $kernel=$app->make(Kernel::class);
    $response=$kernel->handle($request);
    foreach($response->headers->getCookies() as $cookie) {
        if($cookie->getExpiresTime()!==0 && $cookie->getExpiresTime()<time()) unset($jar[$cookie->getName()]);
        else $jar[$cookie->getName()]=$cookie->getValue();
    }
    $kernel->terminate($request,$response);
    if($response->getStatusCode()>=500) throw new RuntimeException('HTTP '.$response->getStatusCode().' at '.$uri.': '.mb_substr(strip_tags($response->getContent()),0,300));
    return $response;
}
$adminCookie=config('session.cookie');
$r=unifiedHttp('/');
check($r->getStatusCode()===200 && str_contains($r->getContent(),'Unified item'), 'domain root renders the real storefront catalog');
check(isset($jar['metis_storefront_session']) && !isset($jar[$adminCookie]), 'shop creates only its separate customer session cookie');
$r=unifiedHttp('/admin');
check($r->getStatusCode()===302 && str_ends_with($r->headers->get('Location'),'/admin/login'), 'unauthenticated admin redirects to staff login');
check(unifiedHttp('/admin/login')->getStatusCode()===200 && unifiedHttp('/login')->getStatusCode()===200, 'staff and customer login pages coexist');
$r=unifiedHttp('/admin/login','POST',['email'=>$staff->email,'password'=>'Synthetic123!','remember'=>'1']);
check($r->getStatusCode()===302 && str_ends_with($r->headers->get('Location'),'/admin'), 'staff login retains its existing authentication workflow at /admin');
$staffHistory=DB::table('login_histories')->count();
check($staffHistory>0 && isset($jar[$adminCookie]), 'staff login history and admin session remain intact');
check(unifiedHttp('/orders')->getStatusCode()===302, 'staff login alone cannot open customer order history');
$r=unifiedHttp('/login','POST',['email'=>$account->email,'password'=>'Synthetic123!']);
check($r->getStatusCode()===302 && DB::table('login_histories')->count()===$staffHistory, 'customer login does not create staff authentication records');
check(unifiedHttp('/orders')->getStatusCode()===200 && unifiedHttp('/admin/orders')->getStatusCode()===200, 'customer and staff order pages both work with separate sessions');
$r=unifiedHttp('/admin/orders?limit=25&page=1');
check(str_contains($r->getContent(),'/admin/orders/import-deliver') && str_contains($r->getContent(),'Tracking ID'), 'admin imports and tracking options use the prefixed routes');
check(unifiedHttp('/products/unified-item')->getStatusCode()===200 && unifiedHttp('/brand-logo')->getStatusCode()===200, 'public product and branding routes use the merged public assets');
unifiedHttp('/cart','POST',['product_id'=>$product->id,'quantity'=>'2']);
$r=unifiedHttp('/cart');
check(preg_match('/name="checkout_key"\s+value="([^"]+)"/',$r->getContent(),$matches)===1, 'customer cart prepares a checkout key through real HTTP');
$r=unifiedHttp('/checkout','POST',['checkout_key'=>$matches[1],'delivery_address_choice'=>$address->id,'billing_same_as_shipping'=>'1','name'=>'Unified buyer','phone'=>'9999999999']);
check($r->getStatusCode()===302 && Order::count()===1, 'merged customer checkout creates exactly one real business order');
$order=Order::firstOrFail();
check($order->created_by===null && $order->updated_by===null && $order->party_id===$customer->id && (float)$order->net_amount===200.0, 'staff session and remember cookie never attribute a shopping order to staff or alter pricing');
check(unifiedHttp('/admin/orders')->getStatusCode()===200, 'shopping checkout does not log staff out');
$token=$staff->createToken('Unified regression')->plainTextToken;
$r=unifiedHttp('/api/orders','GET',[],[],['HTTP_ACCEPT'=>'application/json','HTTP_AUTHORIZATION'=>'Bearer '.$token]);
check($r->getStatusCode()===200 && is_array(json_decode($r->getContent(),true)), 'native order API URL and bearer authentication remain unchanged');
unifiedHttp('/logout','POST');
check(unifiedHttp('/orders')->getStatusCode()===302 && unifiedHttp('/admin/orders')->getStatusCode()===200, 'customer logout leaves the staff session active');
unifiedHttp('/login','POST',['email'=>$account->email,'password'=>'Synthetic123!']);
unifiedHttp('/admin/logout','POST');
check(unifiedHttp('/orders')->getStatusCode()===200 && unifiedHttp('/admin')->getStatusCode()===302, 'staff logout leaves the customer session active');
check(route('shop.index')===url('/') && route('dashboard')===url('/admin') && route('login')===url('/admin/login'), 'named routes generate the canonical public and admin locations');
echo "Unified application HTTP checks passed.\n";
