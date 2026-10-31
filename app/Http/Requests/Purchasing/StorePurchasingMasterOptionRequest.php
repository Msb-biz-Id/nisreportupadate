<?php

namespace App\Http\Requests\Purchasing;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePurchasingMasterOptionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('purchasing.manage-master') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $optionId = $this->route('option')?->id;

        return [
            'category' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:150'],
            'code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('purchasing_master_options', 'code')
                    ->where('category', $this->input('category'))
                    ->ignore($optionId),
            ],
            'color' => ['nullable', 'string', 'max:50'],
            'badge_color' => ['nullable', 'string', 'max:50'],
            'order_index' => ['nullable', 'integer', 'min:0'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Custom attribute names.
     */
    public function attributes(): array
    {
        return [
            'category' => 'Kategori master data',
            'name' => 'Nama opsi',
            'code' => 'Kode sistem',
            'order_index' => 'Urutan tampil',
        ];
    }
}
