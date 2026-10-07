{{--
    CATATAN KAKI DOKUMEN RESMI HCM
--}}
@php
    $p = $profile ?? \App\Services\HcmPdfHelper::getProfileData($employee ?? null);
@endphp

<div class="hcm-document-footer" style="width: 100%; margin-top: 28px; padding-top: 10px; border-top: 1px solid #cbd5e1; font-family: 'Inter', Arial, sans-serif; font-size: 8pt; color: #64748b; line-height: 1.4; text-align: center;">
    <div style="font-weight: 500;">
        {{ $p['document_footer_text'] }}
    </div>
    @if(!empty($p['document_footer_disclaimer']))
    <div style="font-size: 7.5pt; color: #94a3b8; margin-top: 2px;">
        {{ $p['document_footer_disclaimer'] }}
    </div>
    @endif
</div>
