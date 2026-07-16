<?php

namespace App\Modules\Orders\Controllers;

use App\Modules\Core\Controllers\Controller;
use Illuminate\Http\Request;

class RefundController extends Controller
{
    public function index()
    {
        return view('orders.refunds.index');
    }
}
