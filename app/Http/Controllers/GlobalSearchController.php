<?php

namespace App\Http\Controllers;

use App\Modules\Core\Controllers\Controller;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class GlobalSearchController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:search-view', only: ['search']),
        ];
    }

    public function search(Request $request)
    {
        // Let the dashboard run its existing permission-protected lookup and modal flow.
        // This keeps primary/alternate matching and customer creation in one place.
        if ($request->has('phone') && is_string($request->input('phone'))) {
            $phone = preg_replace('/\D/', '', $request->input('phone'));
            if (strlen($phone) > 10) {
                $phone = substr($phone, -10);
            }

            if (strlen($phone) === 10) {
                return new RedirectResponse(route('dashboard', ['phone' => $phone], false));
            }
        }

        return new RedirectResponse(route('dashboard', [], false));
    }
}
