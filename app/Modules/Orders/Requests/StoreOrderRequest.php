<?php

namespace App\Modules\Orders\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Permission checking is already handled in the controller's __construct / middleware
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => 'required|string|in:sale,purchase',
            'party_id' => 'required|exists:parties,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'shipping_address_id' => 'required|exists:party_addresses,id',
            'billing_address_id' => 'required|exists:party_addresses,id',
            'order_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.product_variant_id' => 'nullable|integer',
            'items.*.quantity' => 'required|numeric|gt:0',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.tax_rate' => 'nullable|numeric|min:0',
            'items.*.discount_amount' => 'nullable|numeric|min:0',
            'items.*.tax_amount' => 'nullable|numeric|min:0',
            'items.*.total_amount' => 'nullable|numeric|min:0',
            'items.*.is_gift' => 'nullable|boolean',
            'items.*.gift_source' => 'nullable|string',
            'status' => 'nullable|string|in:pending,future_order,pending_confirmation,confirmed,processing,ready_to_ship,dispatched,shipped,delivered,cancelled,return_requested,returned',
            'future_order_date' => 'nullable|date',
            'coupon_code' => 'nullable|string',
            'applied_offer_id' => 'nullable|integer|exists:offers,id',
            'applied_bogo_ids' => 'nullable|array',
            'applied_bogo_ids.*' => 'integer|exists:offers,id',
            'total_amount' => 'required|numeric',
            'tax_amount' => 'required|numeric',
            'discount_amount' => 'required|numeric',
            'net_amount' => 'required|numeric',
            'use_wallet_balance' => 'nullable|boolean',
        ];
    }
}
