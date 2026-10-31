<?php

namespace App\Http\Requests\Purchasing;

use Illuminate\Foundation\Http\FormRequest;

class StorePurchasingVendorContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('purchasing.manage-vendors');
    }

    public function rules(): array
    {
        return [
            'pic_name' => ['required', 'string', 'max:100'],
            'role_title' => ['nullable', 'string', 'max:100'],
            'phone' => ['required', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:100'],
            'is_primary' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'pic_name' => trim(strip_tags((string) $this->pic_name)),
            'role_title' => $this->role_title ? trim(strip_tags((string) $this->role_title)) : null,
            'phone' => trim(strip_tags((string) $this->phone)),
            'notes' => $this->notes ? trim(strip_tags((string) $this->notes)) : null,
        ]);
    }
}
