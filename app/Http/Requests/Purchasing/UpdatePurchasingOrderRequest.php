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
            'item_name' => ['nullable', 'string', 'max:150'],
            'specification' => ['nullable', 'string', 'max:2000'],
            'unit' => ['nullable', 'string', 'max:50'],
            'quantity' => ['nullable', 'numeric', 'min:0.01'],
            'unit_price' => ['nullable', 'numeric', 'min:0'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'shipping_cost' => ['nullable', 'numeric', 'min:0'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_type' => ['sometimes', 'required', 'string', 'max:50'],
            'payment_mode' => ['nullable', 'in:SINGLE,SPLIT'],
            'invoice_number' => ['nullable', 'string', 'max:100'],
            'invoice_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
            'notes' => ['nullable', 'string', 'max:1000'],

            // Validasi Multi-Item Repeater
            'items' => ['nullable', 'array', 'min:1'],
            'items.*.item_name' => ['required_with:items', 'string', 'max:150'],
            'items.*.item_category_id' => ['nullable', 'exists:purchasing_master_options,id'],
            'items.*.specification' => ['nullable', 'string', 'max:2000'],
            'items.*.unit' => ['required_with:items', 'string', 'max:50'],
            'items.*.quantity' => ['required_with:items', 'numeric', 'min:0.01'],
            'items.*.unit_price' => ['required_with:items', 'numeric', 'min:0'],
            'items.*.discount_amount' => ['nullable', 'numeric', 'min:0'],
            'items.*.subtotal' => ['nullable', 'numeric', 'min:0'],

            // Validasi Split / Termin Pembayaran
            'terms' => ['nullable', 'array'],
            'terms.*.term_step' => ['nullable', 'integer'],
            'terms.*.term_name' => ['required_with:terms', 'string', 'max:100'],
            'terms.*.term_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'terms.*.amount' => ['required_with:terms', 'numeric', 'min:0'],
            'terms.*.due_date' => ['required_with:terms', 'date'],
            'terms.*.notes' => ['nullable', 'string', 'max:255'],
        ];
    }
}
