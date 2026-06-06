<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->is_admin;
    }

    public function rules(): array
    {
        return [
            'invoice_id' => ['nullable', 'exists:invoices,id'],
            'client' => ['required', 'string', 'max:160'],
            'amount' => ['required', 'integer', 'min:1'],
            'method' => ['required', 'in:cash,mpesa,bank_transfer'],
            'reference' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'string', 'max:40'],
            'paid_at' => ['nullable', 'date'],
        ];
    }
}
