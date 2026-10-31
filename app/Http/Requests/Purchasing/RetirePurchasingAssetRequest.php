<?php

namespace App\Http\Requests\Purchasing;

use Illuminate\Foundation\Http\FormRequest;

class RetirePurchasingAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        return $user?->can('purchasing.retire-assets') 
            || $user?->can('purchasing.approve-finance') 
            || $user?->hasRole(['admin_purchasing', 'admin_keuangan', 'superadmin']) 
            ?? false;
    }

    public function rules(): array
    {
        return [
            'retired_at' => ['required', 'date'],
            'retirement_reason' => ['required', 'string', 'max:150'],
            'final_condition' => ['required', 'string', 'max:150'],
            'disposal_method' => ['required', 'string', 'max:150'],
            'book_value' => ['nullable', 'numeric', 'min:0'],
            'disposal_price' => ['nullable', 'numeric', 'min:0'],
            'handover_document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'retired_at.required' => 'Tanggal pelepasan / pensiun aset wajib diisi.',
            'retirement_reason.required' => 'Alasan pelepasan aset wajib dipilih.',
            'final_condition.required' => 'Kondisi akhir aset wajib dipilih.',
            'disposal_method.required' => 'Metode pelepasan aset wajib dipilih.',
        ];
    }
}
