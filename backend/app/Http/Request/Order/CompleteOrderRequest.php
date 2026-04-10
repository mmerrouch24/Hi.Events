<?php

declare(strict_types=1);

namespace HiEvents\Http\Request\Order;

use HiEvents\Http\Request\BaseRequest;
use HiEvents\Validators\CompleteOrderValidator;
use Illuminate\Validation\Rule;

class CompleteOrderRequest extends BaseRequest
{
    public function rules(): array
    {
        if (!$this->route('event_id')) {
            return [
                'order.first_name' => ['required', 'string', 'max:40'],
                'order.last_name' => ['required', 'string', 'max:40'],
                'order.email' => ['required', 'email'],
                'order.email_confirmation' => ['required', 'email', 'same:order.email'],
                'order.donor_type' => ['nullable', 'string', Rule::in(['ALUMNI', 'COMPANY'])],
                'order.questions' => ['nullable', 'array'],
                'order.address' => ['nullable', 'array'],
                'products' => ['required', 'array'],
            ];
        }

        return app(CompleteOrderValidator::class)->rules();
    }

    public function messages(): array
    {
        return app(CompleteOrderValidator::class)->messages();
    }
}
