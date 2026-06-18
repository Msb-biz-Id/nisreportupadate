<?php

namespace App\Support;

use App\Models\Master\BahanKain;
use App\Models\Master\BankAccount;
use App\Models\Master\CustomerType;
use App\Models\Master\Logo;
use App\Models\Master\JenisSetelan;
use App\Models\Master\JenisProduk;
use App\Models\Master\PaketOrder;
use App\Models\Master\PolaJahitan;
use App\Models\Master\PolaProduksi;
use App\Models\Master\Printing;
use App\Models\Master\Product;
use App\Models\Master\Progress;
use App\Models\Master\Resleting;
use App\Models\Master\Size;
use App\Models\Master\Iklan;
use App\Models\Master\JenisOrder;
use App\Models\Master\KategoriOrder;
use App\Models\Master\SumberOrder;

/**
 * Konfigurasi terpusat untuk semua master data.
 *
 * Setiap entry:
 * - slug: URL slug (kebab-case)
 * - label: nama tampilan
 * - group: kategori sidebar (global | order | finance | production)
 * - icon: nama icon lucide-react
 * - model: class FQDN model
 * - scope: 'global' (no brand_id) | 'brand' (brand_id required) | 'brand_nullable' (brand_id nullable)
 * - fields: definisi field untuk form & validation
 * - list_columns: kolom yang ditampilkan di list view
 * - search_fields: field DB yang bisa di-search
 * - order_by: kolom default sort
 */
class MasterRegistry
{
    public static function all(): array
    {
        return [
            'bahan-kain' => [
                'slug' => 'bahan-kain',
                'label' => 'Bahan Kain',
                'group' => 'global',
                'icon' => 'Shirt',
                'model' => BahanKain::class,
                'scope' => 'global',
                'fields' => self::simpleNamaFields(),
                'list_columns' => [
                    ['key' => 'nama', 'label' => 'Nama'],
                    ['key' => 'deskripsi', 'label' => 'Deskripsi', 'class' => 'text-muted-foreground text-xs'],
                    ['key' => 'is_active', 'label' => 'Status', 'type' => 'badge_active'],
                ],
                'search_fields' => ['nama', 'deskripsi'],
                'order_by' => 'nama',
            ],
            'logo' => self::simpleConfig('logo', 'Logo', 'Sparkles', Logo::class),
            'resleting' => self::simpleConfig('resleting', 'Resleting', 'Move3D', Resleting::class),
            'printing' => self::simpleConfig('printing', 'Jenis Printing', 'Printer', Printing::class),
            'paket-order' => [
                'slug'  => 'paket-order',
                'label' => 'Paket Order',
                'group' => 'global',
                'icon'  => 'PackageOpen',
                'model' => PaketOrder::class,
                'scope' => 'global',
                'fields' => [
                    ['name' => 'nama',      'label' => 'Nama Paket',   'type' => 'text',   'required' => true, 'max' => 100],
                    ['name' => 'deskripsi', 'label' => 'Deskripsi',    'type' => 'textarea'],
                    ['name' => 'warna',     'label' => 'Warna Badge',  'type' => 'color',  'default' => '#6B7280',
                     'help' => 'Warna badge yang tampil di Kanban & Order. Contoh: Normal=#10B981, Ekspress=#F59E0B, Urgent=#EF4444'],
                    ['name' => 'prioritas', 'label' => 'Level Prioritas', 'type' => 'select', 'default' => 0,
                     'options' => [
                         ['value' => 0, 'label' => '0 — Normal'],
                         ['value' => 1, 'label' => '1 — Ekspress'],
                         ['value' => 2, 'label' => '2 — Urgent / Kritis'],
                     ]],
                    ['name' => 'is_active', 'label' => 'Aktif', 'type' => 'switch', 'default' => true],
                ],
                'list_columns' => [
                    ['key' => 'nama',      'label' => 'Nama Paket'],
                    ['key' => 'warna',     'label' => 'Warna',    'type' => 'color_badge'],
                    ['key' => 'prioritas', 'label' => 'Prioritas', 'type' => 'badge',
                     'map' => [0 => 'Normal', 1 => 'Ekspress', 2 => 'Urgent']],
                    ['key' => 'is_active', 'label' => 'Status',  'type' => 'badge_active'],
                ],
                'search_fields' => ['nama'],
                'order_by' => 'prioritas',
                'secondary_order' => 'nama',
            ],

            'size' => [
                'slug' => 'size',
                'label' => 'Size / Ukuran',
                'group' => 'global',
                'icon' => 'Ruler',
                'model' => Size::class,
                'scope' => 'global',
                'fields' => [
                    ['name' => 'kategori_size', 'label' => 'Kategori', 'type' => 'select', 'required' => true, 'options' => [
                        ['value' => 'ANAK', 'label' => 'Anak'],
                        ['value' => 'PEREMPUAN', 'label' => 'Perempuan'],
                        ['value' => 'LAKI-LAKI', 'label' => 'Laki-laki'],
                        ['value' => 'UNISEX', 'label' => 'Unisex'],
                        ['value' => 'CUSTOM', 'label' => 'Custom'],
                    ]],
                    ['name' => 'ukuran', 'label' => 'Ukuran', 'type' => 'text', 'required' => true, 'max' => 20, 'placeholder' => 'Contoh: XS, S, M, L, 6XL'],
                    ['name' => 'urutan', 'label' => 'Urutan Tampil', 'type' => 'number', 'default' => 0],
                    ['name' => 'is_active', 'label' => 'Aktif', 'type' => 'switch', 'default' => true],
                ],
                'list_columns' => [
                    ['key' => 'kategori_size', 'label' => 'Kategori', 'type' => 'badge'],
                    ['key' => 'ukuran', 'label' => 'Ukuran'],
                    ['key' => 'urutan', 'label' => 'Urutan'],
                    ['key' => 'is_active', 'label' => 'Status', 'type' => 'badge_active'],
                ],
                'search_fields' => ['kategori_size', 'ukuran'],
                'order_by' => 'kategori_size',
                'secondary_order' => 'urutan',
            ],

            'pola-jahitan' => [
                'slug' => 'pola-jahitan',
                'label' => 'Pola Jahitan',
                'group' => 'global',
                'icon' => 'Scissors',
                'model' => PolaJahitan::class,
                'scope' => 'global',
                'fields' => [
                    ['name' => 'jenis_pola', 'label' => 'Jenis Pola', 'type' => 'text', 'required' => true, 'max' => 100, 'placeholder' => 'Lengan / Kerah / Bawah / Pundak'],
                    ['name' => 'nama', 'label' => 'Nama Pola', 'type' => 'text', 'required' => true, 'max' => 100],
                    ['name' => 'deskripsi', 'label' => 'Deskripsi', 'type' => 'textarea'],
                    ['name' => 'is_active', 'label' => 'Aktif', 'type' => 'switch', 'default' => true],
                ],
                'list_columns' => [
                    ['key' => 'jenis_pola', 'label' => 'Jenis', 'type' => 'badge'],
                    ['key' => 'nama', 'label' => 'Nama Pola'],
                    ['key' => 'is_active', 'label' => 'Status', 'type' => 'badge_active'],
                ],
                'search_fields' => ['jenis_pola', 'nama'],
                'order_by' => 'jenis_pola',
                'secondary_order' => 'nama',
            ],

            'bank' => [
                'slug' => 'bank',
                'label' => 'Bank',
                'group' => 'finance',
                'icon' => 'Landmark',
                'model' => BankAccount::class,
                'scope' => 'brand',
                'fields' => [
                    ['name' => 'bank', 'label' => 'Nama Bank', 'type' => 'text', 'required' => true, 'max' => 100, 'placeholder' => 'BCA / Mandiri / BRI'],
                    ['name' => 'atas_nama', 'label' => 'Atas Nama', 'type' => 'text', 'required' => true, 'max' => 255],
                    ['name' => 'nomor_rekening', 'label' => 'Nomor Rekening', 'type' => 'text', 'required' => true, 'max' => 50],
                    ['name' => 'is_active', 'label' => 'Aktif', 'type' => 'switch', 'default' => true],
                ],
                'list_columns' => [
                    ['key' => 'bank', 'label' => 'Bank'],
                    ['key' => 'atas_nama', 'label' => 'Atas Nama'],
                    ['key' => 'nomor_rekening', 'label' => 'No. Rekening', 'class' => 'font-mono text-sm'],
                    ['key' => 'is_active', 'label' => 'Status', 'type' => 'badge_active'],
                ],
                'search_fields' => ['bank', 'atas_nama', 'nomor_rekening'],
                'order_by' => 'bank',
            ],

            'progress' => [
                'slug' => 'progress',
                'label' => 'Tahapan Progress',
                'group' => 'production',
                'icon' => 'ListChecks',
                'model' => Progress::class,
                'scope' => 'global',
                'fields' => [
                    ['name' => 'nama_progress', 'label' => 'Nama Tahapan', 'type' => 'text', 'required' => true, 'max' => 100],
                    ['name' => 'urutan', 'label' => 'Urutan', 'type' => 'number', 'required' => true, 'default' => 1],
                    ['name' => 'warna', 'label' => 'Warna', 'type' => 'color', 'default' => '#3B82F6'],
                    ['name' => 'is_skippable', 'label' => 'Bisa di-Skip', 'type' => 'switch', 'default' => true],
                    ['name' => 'is_active', 'label' => 'Aktif', 'type' => 'switch', 'default' => true],
                ],
                'list_columns' => [
                    ['key' => 'urutan', 'label' => '#', 'class' => 'w-12 text-center font-semibold'],
                    ['key' => 'nama_progress', 'label' => 'Nama Tahapan'],
                    ['key' => 'warna', 'label' => 'Warna', 'type' => 'color_swatch'],
                    ['key' => 'is_skippable', 'label' => 'Skippable', 'type' => 'badge_bool', 'true_label' => 'Ya', 'false_label' => 'Wajib'],
                    ['key' => 'is_active', 'label' => 'Status', 'type' => 'badge_active'],
                ],
                'search_fields' => ['nama_progress'],
                'order_by' => 'urutan',
            ],

            'sumber-order' => self::brandScopedSimple('sumber-order', 'Sumber Order', 'Compass', SumberOrder::class),
            'jenis-order' => self::brandScopedSimple('jenis-order', 'Jenis Order', 'LayoutList', JenisOrder::class),

            'iklan' => [
                'slug' => 'iklan',
                'label' => 'Promo',
                'group' => 'order',
                'icon' => 'Tag',
                'model' => Iklan::class,
                'scope' => 'brand_nullable',
                'fields' => [
                    ['name' => 'nama', 'label' => 'Nama Promo / Kampanye', 'type' => 'text', 'required' => true, 'max' => 150, 'placeholder' => 'Contoh: Promo Ramadan IG'],
                    ['name' => 'platform', 'label' => 'Platform', 'type' => 'text', 'max' => 100, 'placeholder' => 'Instagram / TikTok / Facebook / dll'],
                    ['name' => 'deskripsi', 'label' => 'Deskripsi', 'type' => 'textarea'],
                    ['name' => 'is_active', 'label' => 'Aktif', 'type' => 'switch', 'default' => true],
                ],
                'list_columns' => [
                    ['key' => 'nama', 'label' => 'Nama Promo'],
                    ['key' => 'platform', 'label' => 'Platform', 'class' => 'text-xs text-muted-foreground'],
                    ['key' => 'deskripsi', 'label' => 'Deskripsi', 'class' => 'text-xs text-muted-foreground'],
                    ['key' => 'is_active', 'label' => 'Status', 'type' => 'badge_active'],
                ],
                'search_fields' => ['nama', 'platform', 'deskripsi'],
                'order_by' => 'nama',
            ],

            'customer-type' => [
                'slug' => 'customer-type',
                'label' => 'Kategori Pelanggan',
                'group' => 'order',
                'icon' => 'UserCheck',
                'model' => CustomerType::class,
                'scope' => 'brand_nullable',
                'fields' => [
                    ['name' => 'nama', 'label' => 'Kategori Pelanggan', 'type' => 'text', 'required' => true, 'max' => 100, 'placeholder' => 'Sekolah / Perusahaan / Tim football / komunitas'],
                    ['name' => 'deskripsi', 'label' => 'Deskripsi', 'type' => 'textarea'],
                    ['name' => 'is_active', 'label' => 'Aktif', 'type' => 'switch', 'default' => true],
                ],
                'list_columns' => [
                    ['key' => 'nama', 'label' => 'Nama'],
                    ['key' => 'is_active', 'label' => 'Status', 'type' => 'badge_active'],
                ],
                'search_fields' => ['nama'],
                'order_by' => 'nama',
            ],

            'jenis-setelan' => [
                'slug'  => 'jenis-setelan',
                'label' => 'Jenis Setelan',
                'group' => 'production',
                'icon'  => 'Layers',
                'model' => JenisSetelan::class,
                'scope' => 'global',
                'fields' => [
                    ['name' => 'nama',      'label' => 'Nama Jenis Setelan', 'type' => 'text', 'required' => true, 'max' => 100,
                     'placeholder' => 'Contoh: Stell (Atasan + Bawahan)'],
                    ['name' => 'deskripsi', 'label' => 'Deskripsi', 'type' => 'textarea'],
                    ['name' => 'is_active', 'label' => 'Aktif', 'type' => 'switch', 'default' => true],
                ],
                'list_columns' => [
                    ['key' => 'nama',      'label' => 'Jenis Setelan'],
                    ['key' => 'deskripsi', 'label' => 'Deskripsi', 'class' => 'text-muted-foreground text-xs'],
                    ['key' => 'is_active', 'label' => 'Status', 'type' => 'badge_active'],
                ],
                'search_fields' => ['nama'],
                'order_by' => 'nama',
            ],

            'pola-produksi' => [
                'slug'  => 'pola-produksi',
                'label' => 'Pola Produksi',
                'group' => 'production',
                'icon'  => 'Scissors',
                'model' => PolaProduksi::class,
                'scope' => 'global',
                'fields' => [
                    ['name' => 'nama',      'label' => 'Nama Pola', 'type' => 'text', 'required' => true, 'max' => 100,
                     'placeholder' => 'Contoh: Standart, Perempuan, Slim Fit'],
                    ['name' => 'deskripsi', 'label' => 'Deskripsi', 'type' => 'textarea'],
                    ['name' => 'is_active', 'label' => 'Aktif', 'type' => 'switch', 'default' => true],
                ],
                'list_columns' => [
                    ['key' => 'nama',      'label' => 'Nama Pola'],
                    ['key' => 'deskripsi', 'label' => 'Deskripsi', 'class' => 'text-muted-foreground text-xs'],
                    ['key' => 'is_active', 'label' => 'Status', 'type' => 'badge_active'],
                ],
                'search_fields' => ['nama'],
                'order_by' => 'nama',
            ],

            'jenis-produk' => [
                'slug' => 'jenis-produk',
                'label' => 'Jenis Produk',
                'group' => 'production',
                'icon' => 'Layers',
                'model' => \App\Models\Master\JenisProduk::class,
                'scope' => 'global',
                'fields' => [
                    ['name' => 'nama', 'label' => 'Nama Jenis Produk', 'type' => 'text', 'required' => true, 'max' => 100],
                    ['name' => 'deskripsi', 'label' => 'Deskripsi', 'type' => 'textarea'],
                    ['name' => 'is_active', 'label' => 'Aktif', 'type' => 'switch', 'default' => true],
                ],
                'list_columns' => [
                    ['key' => 'nama', 'label' => 'Jenis Produk'],
                    ['key' => 'deskripsi', 'label' => 'Deskripsi', 'class' => 'text-muted-foreground text-xs'],
                    ['key' => 'is_active', 'label' => 'Status', 'type' => 'badge_active'],
                ],
                'search_fields' => ['nama'],
                'order_by' => 'nama',
            ],

            'produk' => [
                'slug' => 'produk',
                'label' => 'Produk',
                'group' => 'order',
                'icon' => 'Package',
                'model' => Product::class,
                'scope' => 'brand_nullable',
                'fields' => [
                    ['name' => 'nama', 'label' => 'Nama Produk', 'type' => 'text', 'required' => true, 'max' => 255],
                    ['name' => 'kode', 'label' => 'Kode (opsional)', 'type' => 'text', 'max' => 50],
                    ['name' => 'harga', 'label' => 'Harga (Rp)', 'type' => 'number', 'default' => 0, 'step' => '1'],
                    ['name' => 'deskripsi', 'label' => 'Deskripsi', 'type' => 'textarea'],
                    ['name' => 'gambar', 'label' => 'Gambar Produk', 'type' => 'image', 'purpose' => 'products', 'full_width' => true],
                    ['name' => 'is_featured', 'label' => 'Produk Unggulan', 'type' => 'switch', 'default' => false],
                    ['name' => 'is_active', 'label' => 'Aktif', 'type' => 'switch', 'default' => true],
                ],
                'list_columns' => [
                    ['key' => 'gambar', 'label' => '', 'type' => 'image', 'class' => 'w-12'],
                    ['key' => 'nama', 'label' => 'Produk'],
                    ['key' => 'kode', 'label' => 'Kode', 'class' => 'font-mono text-xs text-muted-foreground'],
                    ['key' => 'harga', 'label' => 'Harga', 'type' => 'currency'],
                    ['key' => 'is_featured', 'label' => 'Unggulan', 'type' => 'badge_bool', 'true_label' => 'Ya', 'false_label' => '-'],
                    ['key' => 'is_active', 'label' => 'Status', 'type' => 'badge_active'],
                ],
                'search_fields' => ['nama', 'kode'],
                'order_by' => 'nama',
            ],
        ];
    }

    public static function find(string $slug): ?array
    {
        return self::all()[$slug] ?? null;
    }

    public static function groups(): array
    {
        return [
            'global' => 'Global (Lintas Brand)',
            'order' => 'Order & Pelanggan',
            'finance' => 'Keuangan',
            'production' => 'Produksi',
        ];
    }

    private static function simpleConfig(string $slug, string $label, string $icon, string $model): array
    {
        return [
            'slug' => $slug,
            'label' => $label,
            'group' => 'global',
            'icon' => $icon,
            'model' => $model,
            'scope' => 'global',
            'fields' => self::simpleNamaFields(),
            'list_columns' => [
                ['key' => 'nama', 'label' => 'Nama'],
                ['key' => 'deskripsi', 'label' => 'Deskripsi', 'class' => 'text-muted-foreground text-xs'],
                ['key' => 'is_active', 'label' => 'Status', 'type' => 'badge_active'],
            ],
            'search_fields' => ['nama', 'deskripsi'],
            'order_by' => 'nama',
        ];
    }

    private static function brandScopedSimple(string $slug, string $label, string $icon, string $model): array
    {
        $cfg = self::simpleConfig($slug, $label, $icon, $model);
        $cfg['group'] = 'order';
        $cfg['scope'] = 'brand_nullable';
        return $cfg;
    }

    private static function simpleNamaFields(): array
    {
        return [
            ['name' => 'nama', 'label' => 'Nama', 'type' => 'text', 'required' => true, 'max' => 100],
            ['name' => 'deskripsi', 'label' => 'Deskripsi', 'type' => 'textarea'],
            ['name' => 'is_active', 'label' => 'Aktif', 'type' => 'switch', 'default' => true],
        ];
    }
}
