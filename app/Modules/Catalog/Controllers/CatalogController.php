<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Controllers;

use App\Modules\Core\Controllers\Controller;
use App\Modules\Catalog\Models\Warehouse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Http\Request;

class CatalogController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:product-view', only: ['products']),
            new Middleware('permission:brand-view', only: ['brands']),
            new Middleware('permission:category-view', only: ['categories']),
            new Middleware('permission:unitofmeasure-view', only: ['uom']),
            new Middleware('permission:taxrate-view', only: ['taxRates']),
            new Middleware('permission:hsncode-view', only: ['hsnCodes']),
            new Middleware('permission:warehouse-view', only: ['warehouses']),
            new Middleware('permission:productattribute-view', only: ['attributes']),
        ];
    }

    public function products(Request $request)
    {
        $user = $request->user();
        $userAttributes = $user?->getAttributes() ?? [];
        $defaultWarehouseId = array_key_exists('warehouse_id', $userAttributes)
            ? $userAttributes['warehouse_id']
            : null;
        $accessibleWarehouse = fn ($query) => $query
            ->where('status', 'active')
            ->when($user?->lob_state_name, fn ($query, $state) => $query->where('state', $state));

        if (! $defaultWarehouseId || ! $accessibleWarehouse(Warehouse::query()->whereKey($defaultWarehouseId))->exists()) {
            $defaultWarehouseId = $accessibleWarehouse(Warehouse::query())
                ->orderBy('name')
                ->value('id');
        }

        return view('catalog.products.index', compact('defaultWarehouseId'));
    }

    public function brands()
    {
        return view('catalog.brands.index');
    }

    public function categories()
    {
        return view('catalog.categories.index');
    }

    public function uom()
    {
        return view('catalog.uom.index');
    }

    public function taxRates()
    {
        return view('catalog.tax-rates.index');
    }

    public function hsnCodes()
    {
        return view('catalog.hsn-codes.index');
    }

    public function warehouses()
    {
        return view('catalog.warehouses.index');
    }

    public function attributes()
    {
        return view('catalog.attributes.index');
    }
}
