<?php

namespace App\Http\Requests\Purchasing;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePurchasingVendorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('purchasing.manage-vendors');
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:150',
                Rule::unique('purchasing_vendors', 'name')->whereNull('deleted_at'),
            ],
            'category' => ['required', 'string', 'max:50'],
            'item_category' => ['nullable', 'string', 'max:100'],
            'item_name' => ['nullable', 'string', 'max:150'],
            'specification' => ['nullable', 'string', 'max:1000'],
            'address' => ['nullable', 'string', 'max:1000'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'bank_account_no' => ['nullable', 'string', 'max:100'],
            'bank_account_holder' => ['nullable', 'string', 'max:100'],
            'default_top_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],

            // Multi-PIC Contacts
            'contacts' => ['nullable', 'array'],
            'contacts.*.pic_name' => ['required_with:contacts', 'string', 'max:100'],
            'contacts.*.role_title' => ['nullable', 'string', 'max:100'],
            'contacts.*.phone' => ['required_with:contacts', 'string', 'max:50'],
            'contacts.*.email' => ['nullable', 'email', 'max:100'],
            'contacts.*.is_primary' => ['nullable', 'boolean'],
            'contacts.*.notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim(strip_tags((string) $this->name)),
            'item_name' => $this->item_name ? trim(strip_tags((string) $this->item_name)) : null,
            'specification' => $this->specification ? trim(strip_tags((string) $this->specification)) : null,
            'address' => $this->address ? trim(strip_tags((string) $this->address)) : null,
            'notes' => $this->notes ? trim(strip_tags((string) $this->notes)) : null,
        ]);
    }
}
