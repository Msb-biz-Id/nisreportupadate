<?php

namespace App\Http\Requests\Purchasing;

use Illuminate\Foundation\Http\FormRequest;

class MutatePurchasingAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('purchasing.mutate-assets') ?? false;
    }

    public function rules(): array
    {
        return [
            'to_department' => ['required', 'string', 'max:100'],
            'to_division' => ['nullable', 'string', 'max:100'],
            'to_position' => ['nullable', 'string', 'max:100'],
            'to_pic_employee_id' => ['nullable', 'exists:hcm_employees,id'],
            'to_location_id' => ['nullable', 'integer'],
            'mutation_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'to_department.required' => 'Departemen tujuan mutasi wajib dipilih.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $asset = $this->route('asset');
            if ($asset && $asset->status === 'RETIRED') {
                $validator->errors()->add('to_department', "Aset {$asset->asset_code} yang telah berstatus RETIRED tidak dapat dimutasi.");
            }
        });
    }
}
