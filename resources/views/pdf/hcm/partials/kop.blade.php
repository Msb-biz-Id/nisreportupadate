{{--
    KOP SURAT RESMI STANDAR INDONESIA (DIVISI HCM)
    Mendukung Logo (kiri), Identitas Perusahaan & Divisi (tengah), Garis Ganda Baku (Double Border).
--}}
@php
    $p = $profile ?? \App\Services\HcmPdfHelper::getProfileData($employee ?? null);
@endphp

<div class="hcm-kop-container" style="width: 100%; margin-bottom: 14px;">
    <table style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
        <tr>
            {{-- LOGO RESMI (KOLOM KIRI SEIMBANG) --}}
            <td style="width: 100px; vertical-align: middle; text-align: left; padding-right: 6px;">
                @if(!empty($p['logo_base64']))
                    <img src="{{ $p['logo_base64'] }}" alt="Logo" style="max-height: 58px; max-width: 88px; height: auto; width: auto; display: block; object-fit: contain;">
                @else
                    <div style="width: 58px; height: 58px; background-color: #0f172a; border-radius: 6px; text-align: center; line-height: 58px;">
                        <span style="color: #ffffff; font-size: 19pt; font-weight: 700; font-family: 'Inter', Arial, sans-serif;">
                            {{ strtoupper(substr($p['company_name'] ?? 'N', 0, 1)) }}
                        </span>
                    </div>
                @endif
            </td>

            {{-- IDENTITAS RESMI LEMBAGA & DIVISI (TENGAH SIMETRIS 100%) --}}
            <td style="vertical-align: middle; text-align: center; padding: 0 6px;">
                {{-- NAMA PERUSAHAAN / HOLDING (12.5pt BOLD) --}}
                <div style="font-family: 'Inter', Arial, sans-serif; font-size: 12.5pt; font-weight: 700; color: #0f172a; text-transform: uppercase; letter-spacing: 0.8px; line-height: 1.2; margin-bottom: 2px;">
                    {{ $p['company_name'] }}
                </div>

                {{-- NAMA DIVISI HCM (9.8pt BOLD) --}}
                <div style="font-family: 'Inter', Arial, sans-serif; font-size: 9.8pt; font-weight: 700; color: #a8001c; text-transform: uppercase; letter-spacing: 0.5px; line-height: 1.25; margin-bottom: 2px;">
                    {{ $p['division_name'] }}
                </div>

                @if(!empty($p['company_tagline']))
                <div style="font-family: 'Inter', Arial, sans-serif; font-size: 7.5pt; font-style: italic; color: #64748b; margin-bottom: 2px;">
                    {{ $p['company_tagline'] }}
                </div>
                @endif

                {{-- ALAMAT DOMISILI --}}
                <div style="font-family: 'Inter', Arial, sans-serif; font-size: 8pt; color: #334155; line-height: 1.35;">
                    {{ $p['company_address'] }}
                </div>

                {{-- KONTAK & MEDIA RESMI --}}
                <div style="font-family: 'Inter', Arial, sans-serif; font-size: 7.5pt; color: #64748b; margin-top: 1.5px; line-height: 1.3;">
                    @php
                        $contactItems = [];
                        if (!empty($p['company_phone'])) $contactItems[] = 'Telp/WA: ' . $p['company_phone'];
                        if (!empty($p['company_email'])) $contactItems[] = 'Email: ' . $p['company_email'];
                        if (!empty($p['company_website'])) $contactItems[] = 'Web: ' . $p['company_website'];
                    @endphp
                    {{ implode('  |  ', $contactItems) }}
                </div>

                @if(!empty($p['kop_header_line2']))
                <div style="font-family: 'Inter', Arial, sans-serif; font-size: 7pt; color: #64748b; margin-top: 1px;">
                    {{ $p['kop_header_line2'] }}
                </div>
                @endif
            </td>

            {{-- KOLOM KANAN SEIMBANG (BADGE ATAU SPACER PENYEIMBANG SIMETRIS) --}}
            <td style="width: 100px; vertical-align: middle; text-align: right; padding-left: 6px;">
                @if(isset($badgeText))
                    <div style="display: inline-block; text-align: right;">
                        <div style="background-color: #a8001c; color: #ffffff; font-size: 7.2pt; font-weight: 700; padding: 4.5px 8.5px; border-radius: 4px; text-transform: uppercase; letter-spacing: 0.5px; display: inline-block; text-align: center; white-space: nowrap;">
                            {{ $badgeText }}
                        </div>
                        @if(isset($badgeSub))
                        <div style="font-size: 6.8pt; color: #64748b; margin-top: 3px; font-weight: 600; letter-spacing: 0.2px;">
                            {{ $badgeSub }}
                        </div>
                        @endif
                    </div>
                @else
                    <div style="width: 100px; height: 1px;">&nbsp;</div>
                @endif
            </td>
        </tr>
    </table>

    {{-- GARIS PEMBATAS MODERN MINIMALIS (SLEEK ACCENT LINE) --}}
    <div style="width: 100%; margin-top: 8px;">
        <div style="border-top: 2px solid #a8001c; width: 100%;"></div>
        <div style="border-top: 0.6px solid #cbd5e1; width: 100%; margin-top: 1.5px;"></div>
    </div>
</div>
