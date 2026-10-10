<?php

namespace App\Http\Requests\Purchasing;

use Illuminate\Foundation\Http\FormRequest;

class StorePurchasingOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
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
            'transaction_date' => ['required', 'date'],
            'order_type' => ['required', 'in:OPEX,CAPEX'],
            'requester_employee_id' => ['nullable', 'exists:hcm_employees,id'],
            'requester_name' => ['nullable', 'string', 'max:150'],
            'department' => ['required', 'string', 'max:100'],
            'division' => ['nullable', 'string', 'max:100'],
            'position' => ['required', 'string', 'max:100'],
            'location_id' => ['nullable', 'exists:purchasing_master_options,id'],
            'vendor_id' => ['nullable', 'exists:purchasing_vendors,id'],
            'vendor_name_manual' => ['nullable', 'string', 'max:150'],
            'item_category_id' => ['nullable', 'exists:purchasing_master_options,id'],
            'item_name' => ['required', 'string', 'max:150'],
            'specification' => ['nullable', 'string', 'max:2000'],
            'unit' => ['required', 'string', 'max:50'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'shipping_cost' => ['nullable', 'numeric', 'min:0'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_type' => ['required', 'string', 'max:50'],
            'invoice_number' => ['nullable', 'string', 'max:100'],
            'invoice_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'], // Max 5MB
            'status' => ['nullable', 'string', 'in:DRAFT,PENDING_PIC_CHECK'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Custom attribute names.
     */
    public function attributes(): array
    {
        return [
            'transaction_date' => 'Tanggal transaksi',
            'order_type' => 'Tipe pengadaan',
            'requester_employee_id' => 'Karyawan pemohon',
            'department' => 'Departemen',
            'position' => 'Jabatan',
            'vendor_id' => 'Suplier / Vendor',
            'item_name' => 'Nama barang / jasa',
            'quantity' => 'Jumlah kuantitas',
            'unit_price' => 'Harga satuan',
            'payment_type' => 'Jenis transaksi pembayaran',
            'invoice_file' => 'Berkas nota / invoice',
        ];
    }
}
