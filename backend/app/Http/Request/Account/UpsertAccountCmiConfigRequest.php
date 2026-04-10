<?php

namespace HiEvents\Http\Request\Account;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpsertAccountCmiConfigRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'merchant_id' => ['required', 'string', 'max:255'],
            'store_key' => ['nullable', 'string', 'max:255'],
            'gateway_url' => ['required', 'url', 'max:500'],
            'currency_numeric_code' => ['required', 'string', 'size:3'],
            'language' => ['required', 'string', 'max:5'],
            'transaction_type' => ['required', 'string', 'max:32'],
            'store_type' => ['required', 'string', 'max:32'],
            'hash_algorithm' => ['required', 'string', 'max:16'],
            'auto_redirect' => ['required', 'boolean'],
            'is_enabled' => ['required', 'boolean'],
            'mode' => ['required', Rule::in(['test', 'live'])],
            'bill_to_company' => ['nullable', 'string', 'max:255'],
            'bill_to_street1' => ['nullable', 'string', 'max:255'],
            'bill_to_city' => ['nullable', 'string', 'max:255'],
            'bill_to_state_prov' => ['nullable', 'string', 'max:255'],
            'bill_to_postal_code' => ['nullable', 'string', 'max:32'],
            'bill_to_country' => ['required', 'string', 'size:3'],
        ];
    }
}
