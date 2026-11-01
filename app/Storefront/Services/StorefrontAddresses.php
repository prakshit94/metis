<?php

namespace App\Storefront\Services;

use App\Modules\Customers\Models\Customer;
use App\Modules\Customers\Models\PartyAddress as CustomerAddress;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Storefront\Models\CustomerAccount;

class StorefrontAddresses
{
    public const FIELDS = ['label' => 'Address label', 'address_line_1' => 'Address line 1', 'address_line_2' => 'Address line 2 (optional)', 'village_name' => 'Village', 'post_office' => 'Post office', 'taluka' => 'Taluka', 'district' => 'District', 'city' => 'City (optional)', 'state' => 'State', 'pincode' => 'PIN code'];

    public function data(CustomerAddress $address): array
    {
        $address->loadMissing('village');
        $data = $address->only(array_keys(self::FIELDS));
        foreach (['village_name' => 'village_name', 'post_office' => 'post_so_name', 'taluka' => 'taluka_name', 'district' => 'district_name', 'state' => 'state_name', 'pincode' => 'pincode'] as $key => $field) {
            $data[$key] = $data[$key] ?: $address->village?->$field;
        }

        return ['id' => $address->id, 'is_default' => $address->is_default, 'village_id' => $address->village_id, ...$data];
    }

    public function available(Customer $customer)
    {
        return $customer->addresses()->where('status', 'active')->with('village')->get()->map(fn ($address) => $this->data($address));
    }

    public function save(CustomerAccount $account, array $data, ?int $addressId = null): CustomerAddress
    {
        return DB::transaction(function () use ($account, $data, $addressId) {
            $customer = Customer::whereKey($account->customer_id)->lockForUpdate()->firstOrFail();
            $locked = CustomerAccount::storefrontEligible()->whereKey($account->id)->lockForUpdate()->first();
            abort_unless($locked && $locked->customer_id === $customer->id && $customer->is_active && ! $customer->is_blacklisted, 403);
            $fields = collect(array_merge(array_keys(self::FIELDS), ['village_id']))->mapWithKeys(fn ($field) => [$field => $data[$field] ?? null])->all();
            if (! empty($fields['village_id'])) {
                $village = \App\Modules\Core\Models\Village::find($fields['village_id']);
                if (! $village) {
                    throw ValidationException::withMessages(['village_id' => 'Choose an available village.']);
                }
                foreach (['village_name' => 'village_name', 'post_office' => 'post_so_name', 'taluka' => 'taluka_name', 'district' => 'district_name', 'state' => 'state_name', 'pincode' => 'pincode'] as $key => $column) {
                    $fields[$key] = $village->$column;
                }
            }
            if (empty($fields['state']) || ! preg_match('/^[0-9]{6}$/', $fields['pincode'] ?? '')) {
                throw ValidationException::withMessages(['village_id' => 'This address needs a state and a valid six-digit PIN code. Check the village or enter the details manually.']);
            }
            // Repeated submissions of the same address return the saved record.
            $match = $customer->addresses()->where('status', 'active');
            foreach ($fields as $field => $value) {
                $match->where($field, $value);
            }
            $address = $addressId !== null
                ? $customer->addresses()->where('status', 'active')->whereKey($addressId)->lockForUpdate()->firstOrFail()
                : $match->first();
            if (! $address && $customer->addresses()->count() >= 20) {
                throw ValidationException::withMessages(['address' => 'You can save up to 20 addresses. Contact your branch to manage existing addresses.']);
            }
            $default = ! empty($data['is_default']) || ! $customer->addresses()->where('status', 'active')->where('is_default', true)->exists();
            if ($default) {
                $customer->addresses()->where('is_default', true)->when($address, fn ($query) => $query->where('id', '!=', $address->id))->update(['is_default' => false]);
            }
            if (! $address) {
                $address = $customer->addresses()->make($fields);
            }
            $address->fill($fields);
            $address->fill(['status' => 'active', 'is_default' => $default || $address->is_default])->save();

            return $address;
        }, 3);
    }
}
