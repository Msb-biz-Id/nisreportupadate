{{--
    BLOK TANDA TANGAN RESMI (OFFICIAL SIGN-OFF BLOCK) HCM
    Mendukung Nama Otomatis Pejabat, Jabatan, NIK, TTD Digital Transparan, dan Stempel Cap Resmi.
--}}
@php
    $p = $profile ?? \App\Services\HcmPdfHelper::getProfileData($employee ?? null);
    $signerName = $customSignerName ?? $p['signer_name'];
    $signerRole = $customSignerRole ?? $p['signer_role'];
    $signerNik  = $customSignerNik  ?? $p['signer_nik'];
    $cityName   = $customCity       ?? $p['company_city'];
    $dateText   = $customDate       ?? $p['current_date_formatted'];
    $align      = $align            ?? 'right'; // 'right', 'left', 'center'
    $showSig    = $p['show_signature_on_pdf'] && !empty($p['signature_base64']);
    $showStamp  = $p['show_stamp_on_pdf'] && !empty($p['stamp_base64']);
@endphp

<div class="hcm-ttd-block" style="display: inline-block; min-width: 230px; max-width: 280px; text-align: center; vertical-align: top;">
    {{-- TEMPAT & TANGGAL SURAT --}}
    <div style="font-family: 'Inter', Arial, sans-serif; font-size: 10pt; color: #1e293b; margin-bottom: 3px;">
        {{ $cityName }}, {{ $dateText }}
    </div>

    {{-- JABATAN PENANDATANGAN --}}
    <div style="font-family: 'Inter', Arial, sans-serif; font-size: 9.5pt; font-weight: 600; color: #334155; margin-bottom: 4px;">
        {{ $signerRole }}
    </div>

    {{-- WADAH TANDA TANGAN DIGITAL & STEMPEL --}}
    <div style="position: relative; width: 220px; height: 75px; margin: 4px auto; text-align: center;">
        {{-- STEMPEL RESMI --}}
        @if($showStamp)
        <div style="position: absolute; left: 10px; top: 2px; width: 72px; height: 72px; z-index: 1;">
            <img src="{{ $p['stamp_base64'] }}" alt="Stempel Resmi" style="width: 72px; height: 72px; object-fit: contain; opacity: 0.88;">
        </div>
        @endif

        {{-- TANDA TANGAN DIGITAL --}}
        @if($showSig)
        <div style="position: absolute; left: 40px; top: 6px; width: 140px; height: 64px; z-index: 2;">
            <img src="{{ $p['signature_base64'] }}" alt="Tanda Tangan Digital" style="max-width: 140px; max-height: 64px; width: auto; height: auto; object-fit: contain;">
        </div>
        @else
        {{-- RUANG TANDA TANGAN BASAH --}}
        <div style="height: 70px; width: 100%;"></div>
        @endif
    </div>

    {{-- NAMA LENGKAP PEJABAT (TEBAL BERGARIS BAWAH) --}}
    <div style="font-family: 'Inter', Arial, sans-serif; font-size: 10.5pt; font-weight: 700; color: #0f172a; margin-top: 2px;">
        <span style="border-bottom: 1.5px solid #0f172a; display: inline-block; padding-bottom: 1px;">
            {{ $signerName ?: '( ___________________________ )' }}
        </span>
    </div>

    {{-- NIK / NIP PEJABAT --}}
    @if(!empty($signerNik))
    <div style="font-family: 'Inter', Arial, sans-serif; font-size: 8.5pt; color: #64748b; margin-top: 2px;">
        NIK. {{ $signerNik }}
    </div>
    @endif
</div>
