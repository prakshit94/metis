<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Controllers;

use App\Modules\Core\Controllers\Controller;

class CatalogController extends Controller
{
    public function products()
    {
        return view('catalog.products.index');
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
