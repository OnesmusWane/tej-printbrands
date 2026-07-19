<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->is_admin;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'image', 'max:8192', 'mimes:'.implode(',', config('images.allowed_mimes'))],
        ];
    }

    public function messages(): array
    {
        return [
            'file.mimes' => 'Animated GIFs aren\'t supported — please upload a JPG, PNG, or WebP image instead.',
        ];
    }
}
