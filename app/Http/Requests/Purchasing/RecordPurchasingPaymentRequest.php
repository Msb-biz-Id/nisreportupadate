<?php

namespace App\Http\Requests\Purchasing;

use Illuminate\Foundation\Http\FormRequest;

class RecordPurchasingPaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Segregation of Duties: Only Finance / Superadmin can disburse and record payments.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('purchasing.approve-finance')
            || $this->user()?->hasRole(['superadmin', 'admin_keuangan']);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'paid_at' => ['required', 'date'],
            'payment_method' => ['required', 'string', 'max:50'],
            'receipt_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'], // Max 5MB
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Custom attributes.
     */
    public function attributes(): array
    {
        return [
            'paid_at' => 'Tanggal pembayaran',
            'payment_method' => 'Metode pencairan pembayaran',
            'receipt_file' => 'Bukti transfer bank',
        ];
    }
}
