<?php

namespace App\Http\Requests\Purchasing;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePurchasingOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $order = $this->route('order');
        if (!$order) {
            return false;
        }

        // Hanya boleh edit jika DRAFT atau PENDING_PIC_CHECK
        if (!in_array($order->status, ['DRAFT', 'PENDING_PIC_CHECK', 'REJECTED'])) {
            return false;
        }

        return $this->user()?->can('purchasing.manage-orders') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'transaction_date' => ['sometimes', 'required', 'date'],
            'order_type' => ['sometimes', 'required', 'in:OPEX,CAPEX'],
            'requester_employee_id' => ['nullable', 'exists:hcm_employees,id'],
            'requester_name' => ['nullable', 'string', 'max:150'],
            'department' => ['sometimes', 'required', 'string', 'max:100'],
            'division' => ['nullable', 'string', 'max:100'],
            'position' => ['sometimes', 'required', 'string', 'max:100'],
            'location_id' => ['nullable', 'exists:purchasing_master_options,id'],
            'vendor_id' => ['nullable', 'exists:purchasing_vendors,id'],
            'vendor_name_manual' => ['nullable', 'string', 'max:150'],
            'item_category_id' => ['nullable', 'exists:purchasing_master_options,id'],
            'item_name' => ['sometimes', 'required', 'string', 'max:150'],
            'specification' => ['nullable', 'string', 'max:2000'],
            'unit' => ['sometimes', 'required', 'string', 'max:50'],
            'quantity' => ['sometimes', 'required', 'numeric', 'min:0.01'],
            'unit_price' => ['sometimes', 'required', 'numeric', 'min:0'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'shipping_cost' => ['nullable', 'numeric', 'min:0'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_type' => ['sometimes', 'required', 'string', 'max:50'],
            'invoice_number' => ['nullable', 'string', 'max:100'],
            'invoice_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
