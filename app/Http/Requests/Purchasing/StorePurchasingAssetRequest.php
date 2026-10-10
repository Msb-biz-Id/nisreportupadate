<?php

namespace App\Http\Requests\Purchasing;

use Illuminate\Foundation\Http\FormRequest;

class StorePurchasingAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('purchasing.manage-assets') ?? false;
    }

    public function rules(): array
    {
        return [
            'asset_name' => ['required', 'string', 'max:150'],
            'category_code' => ['required', 'string', 'max:10'],
            'department' => ['required', 'string', 'max:100'],
            'department_code' => ['nullable', 'string', 'max:10'],
            'division' => ['nullable', 'string', 'max:100'],
            'position' => ['nullable', 'string', 'max:100'],
            'order_id' => ['nullable', 'exists:purchasing_orders,id'],
            'pic_employee_id' => ['nullable', 'exists:hcm_employees,id'],
            'location_id' => ['nullable', 'integer'],
            'vendor_id' => ['nullable', 'exists:purchasing_vendors,id'],
            'supplier_name' => ['nullable', 'string', 'max:150'],
            'purchase_date' => ['nullable', 'date'],
            'received_date' => ['nullable', 'date'],
            'acquisition_cost' => ['nullable', 'numeric', 'min:0'],
            'quantity' => ['nullable', 'integer', 'min:1'],
            'unit' => ['nullable', 'string', 'max:50'],
            'specification' => ['nullable', 'string', 'max:2000'],
            'serial_number' => ['nullable', 'string', 'max:100'],
            'warranty_duration' => ['nullable', 'string', 'max:50'],
            'warranty_expires_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'asset_name.required' => 'Nama aset wajib diisi.',
            'category_code.required' => 'Kategori aset resmi wajib dipilih.',
            'department.required' => 'Departemen penanggung jawab wajib dipilih.',
            'acquisition_cost.numeric' => 'Biaya perolehan harus berupa nominal angka valid.',
        ];
    }
}
