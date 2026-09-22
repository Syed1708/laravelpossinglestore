<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreKioskOrderRequest extends FormRequest
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
            'cart'              => ['required', 'array', 'min:1'],
            'cart.*.id'         => ['required', 'exists:products,id'],
            'cart.*.quantity'   => ['required', 'integer', 'min:1'],
            'cart.*.notes'      => ['nullable', 'array'],
            'cart.*.extraPrice' => ['nullable', 'numeric', 'min:0'],
            'order_type'        => ['required', 'in:kiosk_eat_in,kiosk_takeaway,dine_in,takeaway'],
            'payment_choice'    => ['required', 'in:pay_at_counter,card_terminal'],
            'customer_name'     => ['nullable', 'string', 'max:100'],
            'customer_phone'    => ['nullable', 'string', 'max:50'],
        ];
    }
}