<?php

namespace App\Http\Requests\Purchasing;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePurchasingAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('purchasing.manage-assets') ?? false;
    }

    public function rules(): array
    {
        return [
            'specification' => ['nullable', 'string', 'max:3000'],
            'serial_number' => ['nullable', 'string', 'max:100'],
            'barcode_qr_code' => ['nullable', 'string', 'max:100'],
            'warranty_duration' => ['nullable', 'string', 'max:50'],
            'warranty_expires_at' => ['nullable', 'date'],
            'location_id' => ['nullable', 'integer'],
            'pic_employee_id' => ['nullable', 'exists:hcm_employees,id'],
            'unit' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
