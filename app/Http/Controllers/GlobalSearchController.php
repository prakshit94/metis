<?php

namespace App\Http\Controllers;

use App\Modules\Core\Controllers\Controller;
use Illuminate\Http\Request;
use App\Modules\Customers\Models\Customer;

class GlobalSearchController extends Controller
{
    public function search(Request $request)
    {
        if ($request->has('phone')) {
            $phone = $request->phone;
            $customer = Customer::where('phone', $phone)->first();
            
            if ($customer) {
                return redirect()->route('orders.create', ['customer_id' => $customer->id]);
            } else {
                return redirect()->route('orders.create', ['phone' => $phone]);
            }
        }
        
        return redirect()->route('dashboard');
    }
}
