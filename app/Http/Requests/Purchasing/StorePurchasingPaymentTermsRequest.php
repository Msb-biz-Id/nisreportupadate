<?php

namespace App\Http\Requests\Purchasing;

use Illuminate\Foundation\Http\FormRequest;

class StorePurchasingPaymentTermsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('purchasing.manage-orders')
            || $this->user()?->can('purchasing.approve-finance')
            || $this->user()?->hasRole(['superadmin', 'admin_purchasing', 'admin_keuangan']);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'terms' => ['required', 'array', 'min:1'],
            'terms.*.term_step' => ['nullable', 'integer', 'min:1'],
            'terms.*.term_name' => ['required', 'string', 'max:100'],
            'terms.*.term_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'terms.*.amount' => ['required', 'numeric', 'min:1'],
            'terms.*.due_date' => ['required', 'date'],
            'terms.*.notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Custom attributes.
     */
    public function attributes(): array
    {
        return [
            'terms' => 'Jadwal termin pembayaran',
            'terms.*.term_name' => 'Nama termin',
            'terms.*.amount' => 'Nominal termin',
            'terms.*.due_date' => 'Tanggal jatuh tempo',
        ];
    }
}
