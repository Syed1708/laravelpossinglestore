<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class SyncOrdersRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'orders'                        => ['required', 'array', 'min:1'],
            'orders.*.uuid'                 => ['required', 'uuid'],
            'orders.*.subtotal_excl_vat'    => ['required', 'numeric'],
            'orders.*.vat_amount'           => ['required', 'numeric'],
            'orders.*.total_incl_vat'       => ['required', 'numeric'],
            'orders.*.completed_at'         => ['required', 'date'],
            'orders.*.order_type'           => ['nullable', 'string', 'in:dine_in,takeaway,click_and_collect,delivery'],
            'orders.*.client_id'            => ['nullable', 'exists:clients,id'],
            'orders.*.customer_name'        => ['nullable', 'string', 'max:100'],
            'orders.*.customer_phone'       => ['nullable', 'string', 'max:50'],
            
            // 🚀 The missing rules that allow cancellations and refunds through $request->validated():
            'orders.*.status'               => ['nullable', 'string', 'max:50'],
            'orders.*.preparation_status'   => ['nullable', 'string', 'max:50'],

            'orders.*.items'                => ['required', 'array', 'min:1'],
            'orders.*.items.*.product_id'   => ['nullable', 'exists:products,id'],
            'orders.*.items.*.product_name' => ['required', 'string', 'max:255'],
            'orders.*.items.*.quantity'     => ['required', 'integer', 'min:1'],
            'orders.*.items.*.unit_price'   => ['required', 'numeric', 'min:0'],
            'orders.*.items.*.vat_rate'     => ['required', 'numeric'],
            'orders.*.items.*.subtotal'     => ['required', 'numeric', 'min:0'],
            'orders.*.items.*.notes'        => ['nullable', 'array'],
            'orders.*.payments'             => ['required', 'array', 'min:1'],
            'orders.*.payments.*.amount'    => ['required', 'numeric', 'min:0'],
            'orders.*.payments.*.method'    => ['required', 'string', 'max:50'],
        ];
    }

    /**
     * 🛡️ Maintain 100% backward compatibility with POS client error expectations
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'error'   => 'Validation failed',
            'details' => $validator->errors(),
        ], 422));
    }
}