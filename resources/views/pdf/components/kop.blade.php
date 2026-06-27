<div>
    @if (!empty($logoData))
        <div style="margin-bottom: 8px;">
            <img src="{{ $logoData }}" alt="{{ $brand?->nama_brand ?? 'Brand Logo' }}" style="max-height: 58px; max-width: 180px; object-fit: contain;">
        </div>
        <div class="brand" style="color: #000000; font-weight: 900; font-size: 16pt; letter-spacing: -0.5px; margin-bottom: 4px;">{{ $brand?->nama_brand ?? 'Circle Sportwear' }}</div>
    @elseif ($brand?->logo)
        <div style="margin-bottom: 8px;">
            <img src="{{ public_path('storage/' . $brand->logo) }}" alt="{{ $brand?->nama_brand ?? 'Brand Logo' }}" style="max-height: 58px; max-width: 180px; object-fit: contain;">
        </div>
        <div class="brand" style="color: #000000; font-weight: 900; font-size: 16pt; letter-spacing: -0.5px; margin-bottom: 4px;">{{ $brand?->nama_brand ?? 'Circle Sportwear' }}</div>
    @else
        <div class="brand" style="color: #000000; font-weight: 900; font-size: 20pt; letter-spacing: -0.5px;">{{ $brand?->nama_brand ?? 'Circle Sportwear' }}</div>
    @endif

    @if ($brand?->tagline)
        <div class="brand-tagline" style="font-size: 9.5pt; color: #000000; font-weight: 700;">{{ $brand->tagline }}</div>
    @endif

    @if ($brand?->deskripsi)
        <div style="font-size: 8pt; color: #000000; font-weight: 600; margin-top: 3px;">{{ $brand->deskripsi }}</div>
    @endif

    <div style="font-size: 8pt; color: #4B5563; font-weight: 500; margin-top: 4px; line-height: 1.35;">
        @if ($brand?->alamat)
            <div><span style="font-weight: 700; color: #6B7280;">Alamat:</span> {{ $brand->alamat }}</div>
        @endif
        <div style="margin-top: 1.5px;">
            @if ($brand?->no_hp)
                <span style="font-weight: 700; color: #6B7280;">WA/Telp:</span> {{ $brand->no_hp }}
            @endif
            @if ($brand?->no_hp && $brand?->email)
                 · 
            @endif
            @if ($brand?->email)
                <span style="font-weight: 700; color: #6B7280;">Email:</span> {{ $brand->email }}
            @endif
        </div>
    </div>

    @php
        $svgGlobe = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#374151"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/></svg>';
        $svgInstagram = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#9D174D"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.051.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 1 0 0 12.324 6.162 6.162 0 0 0 0-12.324zM12 16a4 4 0 1 1 0-8 4 4 0 0 1 0 8zm6.406-11.845a1.44 1.44 0 1 0 0 2.881 1.44 1.44 0 0 0 0-2.881z"/></svg>';
        $svgFacebook = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#1D4ED8"><path d="M9 8h-3v4h3v12h5v-12h3.642l.358-4h-4v-1.667c0-.955.192-1.333 1.115-1.333h2.885v-5h-3.808c-3.596 0-5.192 1.583-5.192 4.615v3.385z"/></svg>';
        $svgTiktok = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#FFFFFF"><path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.02 1.59 4.23.02.02.04.04.06.06.01.83.02 1.66.02 2.5-1.04-.37-1.99-1.01-2.73-1.84-.04-.04-.08-.09-.13-.13-.01 2.92-.01 5.84-.02 8.76-.08 1.63-.73 3.23-1.86 4.39-1.42 1.48-3.55 2.19-5.59 1.84-2.22-.35-4.14-1.99-4.73-4.18-.72-2.59.18-5.51 2.27-7.06.94-.71 2.09-1.09 3.28-1.12.01 1.48.01 2.97.02 4.45-.63.07-1.25.35-1.68.83-.56.61-.75 1.48-.5 2.27.27.86.99 1.51 1.87 1.69.96.22 2.04-.15 2.55-.99.27-.45.37-.99.35-1.52-.01-4.72-.01-9.44-.02-14.16z"/></svg>';

        $compGlobeDataUri = 'data:image/svg+xml;base64,' . base64_encode($svgGlobe);
        $compInstagramDataUri = 'data:image/svg+xml;base64,' . base64_encode($svgInstagram);
        $compFacebookDataUri = 'data:image/svg+xml;base64,' . base64_encode($svgFacebook);
        $compTiktokDataUri = 'data:image/svg+xml;base64,' . base64_encode($svgTiktok);
    @endphp

    <div style="margin-top: 6px;">
        @if ($brand?->website)
            <a href="{{ str_starts_with($brand->website, 'http') ? $brand->website : 'https://' . $brand->website }}" target="_blank" style="display: inline-block; text-decoration: none; background: #F3F4F6; border: 1px solid #E5E7EB; border-radius: 4px; padding: 2px 6px; font-size: 7.5pt; font-weight: bold; color: #374151; margin-right: 5px; margin-bottom: 4px;">
                <img src="{{ $compGlobeDataUri }}" style="width: 9px; height: 9px; vertical-align: middle; margin-right: 2px; margin-top: 1.5px;"><span style="vertical-align: middle; font-weight: bold;">{{ $brand->website }}</span>
            </a>
        @endif
        @if ($brand?->instagram)
            <a href="https://instagram.com/{{ ltrim($brand->instagram, '@') }}" target="_blank" style="display: inline-block; text-decoration: none; background: #FDF2F8; border: 1px solid #FCE7F3; border-radius: 4px; padding: 2px 6px; font-size: 7.5pt; font-weight: bold; color: #9D174D; margin-right: 5px; margin-bottom: 4px;">
                <img src="{{ $compInstagramDataUri }}" style="width: 9px; height: 9px; vertical-align: middle; margin-right: 2px; margin-top: 1.5px;"><span style="vertical-align: middle; font-weight: bold;"><span>@</span>{{ ltrim($brand->instagram, '@') }}</span>
            </a>
        @endif
        @if ($brand?->facebook)
            <a href="https://facebook.com/{{ $brand->facebook }}" target="_blank" style="display: inline-block; text-decoration: none; background: #EFF6FF; border: 1px solid #DBEAFE; border-radius: 4px; padding: 2px 6px; font-size: 7.5pt; font-weight: bold; color: #1D4ED8; margin-right: 5px; margin-bottom: 4px;">
                <img src="{{ $compFacebookDataUri }}" style="width: 9px; height: 9px; vertical-align: middle; margin-right: 2px; margin-top: 1.5px;"><span style="vertical-align: middle; font-weight: bold;">{{ $brand->facebook }}</span>
            </a>
        @endif
        @if ($brand?->tiktok)
            <a href="https://tiktok.com/@{{ ltrim($brand->tiktok, '@') }}" target="_blank" style="display: inline-block; text-decoration: none; background: #111827; border: 1px solid #111827; border-radius: 4px; padding: 2px 6px; font-size: 7.5pt; font-weight: bold; color: #FFFFFF; margin-bottom: 4px;">
                <img src="{{ $compTiktokDataUri }}" style="width: 9px; height: 9px; vertical-align: middle; margin-right: 2px; margin-top: 1.5px;"><span style="vertical-align: middle; font-weight: bold;"><span>@</span>{{ ltrim($brand->tiktok, '@') }}</span>
            </a>
        @endif
    </div>
</div>
