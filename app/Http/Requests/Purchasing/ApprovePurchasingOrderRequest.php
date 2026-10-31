<?php

namespace App\Http\Requests\Purchasing;

use Illuminate\Foundation\Http\FormRequest;

class ApprovePurchasingOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $action = $this->input('action');

        if ($action === 'approve_pic') {
            return $this->user()?->can('purchasing.approve-pic')
                || $this->user()?->hasRole(['superadmin', 'admin_purchasing']);
        }

        if ($action === 'approve_finance') {
            return $this->user()?->can('purchasing.approve-finance')
                || $this->user()?->hasRole(['superadmin', 'admin_keuangan']);
        }

        if ($action === 'reject') {
            return $this->user()?->can('purchasing.manage-orders')
                || $this->user()?->can('purchasing.approve-pic')
                || $this->user()?->can('purchasing.approve-finance')
                || $this->user()?->hasRole(['superadmin', 'admin_purchasing', 'admin_keuangan']);
        }

        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'action' => ['required', 'string', 'in:approve_pic,approve_finance,reject'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'rejection_reason' => ['required_if:action,reject', 'nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Custom messages
     */
    public function messages(): array
    {
        return [
            'rejection_reason.required_if' => 'Alasan penolakan wajib diisi jika order ditolak.',
        ];
    }
}
