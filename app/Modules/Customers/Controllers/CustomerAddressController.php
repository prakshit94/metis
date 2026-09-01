<?php

declare(strict_types=1);

namespace App\Modules\Customers\Controllers;

use App\Modules\Core\Controllers\Controller;
use App\Modules\Core\Models\Village;
use App\Modules\Customers\Models\Party;
use App\Modules\Customers\Models\PartyAddress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class CustomerAddressController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:customeraddress-create', only: ['store']),
            new Middleware('permission:customeraddress-edit', only: ['update']),
            new Middleware('permission:customeraddress-delete', only: ['destroy']),
        ];
    }

    public function store(Request $request, Party $customer): JsonResponse
    {
        $validated = $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'in:active,inactive'],
            'address_line_1' => ['nullable', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'village_id' => ['nullable', 'exists:villages,id'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'pincode' => ['nullable', 'string', 'max:20'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        // Defaults
        $validated['status'] = $validated['status'] ?? 'active';

        $isDefault = (bool) ($validated['is_default'] ?? false);

        // If this is the first address for the customer, force it to be default
        if ($customer->addresses()->count() === 0) {
            $isDefault = true;
        }

        if ($isDefault) {
            $customer->addresses()->update(['is_default' => false]);
        }

        $address = new PartyAddress($validated);
        $address->is_default = $isDefault;

        if (! empty($validated['village_id'])) {
            $village = Village::find($validated['village_id']);
            if ($village) {
                $address->village_name = $village->village_name;
                $address->post_office = $village->post_so_name;
                $address->taluka = $village->taluka_name;
                $address->district = $village->district_name;
                $address->state = $address->state ?? $village->state_name;
                $address->pincode = $address->pincode ?? $village->pincode;
            }
        }

        $customer->addresses()->save($address);

        return response()->json([
            'message' => "Address [{$address->label}] created successfully.",
            'data' => $address->load('village'),
        ], 201);
    }

    public function update(Request $request, Party $customer, PartyAddress $address): JsonResponse
    {
        if ($address->party_id !== $customer->id) {
            return response()->json(['message' => 'Unauthorized address modification.'], 403);
        }

        $validated = $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'in:active,inactive'],
            'address_line_1' => ['nullable', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'village_id' => ['nullable', 'exists:villages,id'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'pincode' => ['nullable', 'string', 'max:20'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        // Defaults
        $validated['status'] = $validated['status'] ?? $address->status ?? 'active';

        $isDefault = (bool) ($validated['is_default'] ?? false);

        if ($isDefault) {
            $customer->addresses()->where('id', '!=', $address->id)->update(['is_default' => false]);
        }

        $validated['is_default'] = $isDefault;

        if (! empty($validated['village_id'])) {
            $village = Village::find($validated['village_id']);
            if ($village) {
                $validated['village_name'] = $village->village_name;
                $validated['post_office'] = $village->post_so_name;
                $validated['taluka'] = $village->taluka_name;
                $validated['district'] = $village->district_name;
                $validated['state'] = $validated['state'] ?? $village->state_name;
                $validated['pincode'] = $validated['pincode'] ?? $village->pincode;
            }
        }

        $address->update($validated);

        return response()->json([
            'message' => "Address [{$address->label}] updated successfully.",
            'data' => $address->load('village'),
        ]);
    }

    public function destroy(Party $customer, PartyAddress $address): JsonResponse
    {
        if ($address->party_id !== $customer->id) {
            return response()->json(['message' => 'Unauthorized address modification.'], 403);
        }

        $address->delete();

        return response()->json([
            'message' => 'Address deleted successfully.',
        ]);
    }
}
