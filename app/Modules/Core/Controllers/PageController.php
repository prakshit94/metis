<?php

namespace App\Modules\Core\Controllers;

use Illuminate\Http\Request;

class PageController extends Controller
{
    public function dashboard()
    {
        return view('dashboard');
    }

    public function login()
    {
        return view('login');
    }

    public function analytics()
    {
        return view('analytics');
    }

    public function users()
    {
        return view('users.index');
    }

    public function customers()
    {
        return view('customers.index');
    }

    public function villages()
    {
        return view('villages.index');
    }

    public function rolesPermissions()
    {
        return view('users.roles-permissions');
    }

    public function products()
    {
        return view('products');
    }

    public function orders()
    {
        return view('orders.index');
    }

    public function stockManagement()
    {
        return view('inventory.stock-management');
    }

    public function stockTransfers()
    {
        return view('inventory.stock-transfers');
    }

    public function inventoryAdjustments()
    {
        return view('inventory.adjustments');
    }

    public function reports()
    {
        return view('reports');
    }

    public function messages()
    {
        return view('messages');
    }

    public function calendar()
    {
        return view('calendar');
    }

    public function files()
    {
        return view('files');
    }

    public function forms()
    {
        return view('forms');
    }

    public function settings()
    {
        return view('settings');
    }

    public function security()
    {
        return view('security');
    }

    public function help()
    {
        return view('help');
    }

    public function elementsOverview()
    {
        return view('elements.overview');
    }

    public function elementsAlerts()
    {
        return view('elements.alerts');
    }

    public function elementsBadges()
    {
        return view('elements.badges');
    }

    public function elementsButtons()
    {
        return view('elements.buttons');
    }

    public function elementsCards()
    {
        return view('elements.cards');
    }

    public function elementsForms()
    {
        return view('elements.forms');
    }

    public function elementsModals()
    {
        return view('elements.modals');
    }

    public function elementsTables()
    {
        return view('elements.tables');
    }

    public function shipments()
    {
        return view('shipping.shipments');
    }

    public function shippingServices()
    {
        return view('shipping.services');
    }
}
