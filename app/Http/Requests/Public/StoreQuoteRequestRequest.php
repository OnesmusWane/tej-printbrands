<?php

namespace App\Http\Requests\Public;

use Illuminate\Foundation\Http\FormRequest;

class StoreQuoteRequestRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:160'],
            'email' => ['required', 'email', 'max:160'],
            'phone' => ['nullable', 'string', 'max:40'],
            'product' => ['required', 'string', 'max:160'],
            'quantity' => ['nullable', 'string', 'max:80'],
            'size' => ['nullable', 'string', 'max:80'],
            'artwork' => ['nullable', 'file', 'max:10240'],
            'delivery_method' => ['required', 'in:pickup,delivery'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
