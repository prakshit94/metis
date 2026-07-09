<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customers;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\PartyAddress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerAddressController extends Controller
{
    public function store(Request $request, Customer $customer): JsonResponse
    {
        $validated = $request->validate([
            'label'          => ['required', 'string', 'max:255'],
            'status'         => ['nullable', 'string', 'in:active,inactive'],
            'address_line_1' => ['required', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'village_id'     => ['nullable', 'exists:villages,id'],
            'city'           => ['nullable', 'string', 'max:255'],
            'state'          => ['nullable', 'string', 'max:255'],
            'pincode'        => ['nullable', 'string', 'max:20'],
            'is_default'     => ['nullable', 'boolean'],
        ]);

        // Defaults
        $validated['status'] = $validated['status'] ?? 'active';

        $isDefault = (bool) ($validated['is_default'] ?? false);

        if ($isDefault) {
            $customer->addresses()->update(['is_default' => false]);
        }

        $address = new PartyAddress($validated);
        $address->is_default = $isDefault;
        $customer->addresses()->save($address);

        return response()->json([
            'message' => "Address [{$address->label}] created successfully.",
            'data'    => $address->load('village'),
        ], 201);
    }

    public function update(Request $request, Customer $customer, PartyAddress $address): JsonResponse
    {
        if ($address->party_id !== $customer->id) {
            return response()->json(['message' => 'Unauthorized address modification.'], 403);
        }

        $validated = $request->validate([
            'label'          => ['required', 'string', 'max:255'],
            'status'         => ['nullable', 'string', 'in:active,inactive'],
            'address_line_1' => ['required', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'village_id'     => ['nullable', 'exists:villages,id'],
            'city'           => ['nullable', 'string', 'max:255'],
            'state'          => ['nullable', 'string', 'max:255'],
            'pincode'        => ['nullable', 'string', 'max:20'],
            'is_default'     => ['nullable', 'boolean'],
        ]);

        // Defaults
        $validated['status'] = $validated['status'] ?? $address->status ?? 'active';

        $isDefault = (bool) ($validated['is_default'] ?? false);

        if ($isDefault) {
            $customer->addresses()->where('id', '!=', $address->id)->update(['is_default' => false]);
        }

        $validated['is_default'] = $isDefault;
        $address->update($validated);

        return response()->json([
            'message' => "Address [{$address->label}] updated successfully.",
            'data'    => $address->load('village'),
        ]);
    }

    public function destroy(Customer $customer, PartyAddress $address): JsonResponse
    {
        if ($address->party_id !== $customer->id) {
            return response()->json(['message' => 'Unauthorized address modification.'], 403);
        }

        $address->delete();

        return response()->json([
            'message' => "Address deleted successfully.",
        ]);
    }
}
