<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use App\Models\Settings\SystemSetting;

class BrandController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('brand.view');

        $user = $request->user();
        $query = Brand::query()->with('parentBrand')->withCount('users');

        if ($user->isSuperadmin()) {
            // Superadmin lihat semua
        } elseif ($user->hasRole('admin_reseller')) {
            // admin_reseller: lihat SEMUA reseller_hub di sistem (dashboard manajemen reseller)
            // + branches yang mereka punya akses
            $query->where(function ($q) use ($user) {
                $q->where('brand_type', Brand::TYPE_RESELLER_HUB)
                  ->orWhere(function ($q2) use ($user) {
                      $accessibleIds = $user->brands()->pluck('brands.id');
                      $q2->where('brand_type', Brand::TYPE_RESELLER_BRANCH)
                          ->whereIn('id', $accessibleIds);
                  });
            });
        } else {
            $brandIds = $user->brands()->pluck('brands.id');
            $query->whereIn('id', $brandIds)
                ->where('brand_type', Brand::TYPE_REGULAR);
        }

        if ($search = $request->string('q')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('nama_brand', 'like', "%{$search}%")
                  ->orWhere('kode', 'like', "%{$search}%");
            });
        }

        if ($status = $request->string('status')->toString()) {
            $query->where('is_active', $status === 'active');
        }

        $brands = $query->orderBy('nama_brand')->paginate(15)->withQueryString();

        $parentBrands = Brand::where('brand_type', Brand::TYPE_REGULAR)->orderBy('nama_brand')->get(['id', 'nama_brand']);

        return Inertia::render('Brand/Index', [
            'brands' => $brands,
            'reseller_hubs' => $parentBrands,
            'parent_brands' => $parentBrands,
            'filters' => [
                'q' => $request->string('q')->toString(),
                'status' => $request->string('status')->toString(),
            ],
            'can' => [
                'create' => $user->can('brand.create'),
                'update' => $user->can('brand.update'),
                'delete' => $user->can('brand.delete'),
                'import' => (bool) SystemSetting::get('system', 'customer_import_enabled', false),
            ],
            'is_admin_reseller' => $user->hasRole('admin_reseller'),
            // IDs brand yang admin_reseller punya akses penuh (untuk tampilkan tombol aksi)
            'accessible_brand_ids' => $user->hasRole('admin_reseller')
                ? array_merge(
                    $user->brands()->pluck('brands.id')->toArray(),
                    Brand::where('brand_type', Brand::TYPE_RESELLER_HUB)->pluck('id')->toArray()
                )
                : [],
        ]);
    }

    public function store(Request $request)
    {
        Gate::authorize('brand.create');

        $user = $request->user();

        $data = $request->validate([
            'nama_brand' => ['required', 'string', 'max:100'],
            'kode' => ['required', 'string', 'max:20', 'alpha_dash', Rule::unique('brands', 'kode')],
            'tagline' => ['nullable', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'email' => ['nullable', 'email', 'max:255'],
            'no_hp' => ['nullable', 'string', 'max:20'],
            'alamat' => ['nullable', 'string'],
            'instagram' => ['nullable', 'string', 'max:100'],
            'facebook' => ['nullable', 'string', 'max:100'],
            'tiktok' => ['nullable', 'string', 'max:100'],
            'whatsapp' => ['nullable', 'string', 'max:20'],
            'website' => ['nullable', 'string', 'max:255'],
            'warna_primary' => ['nullable', 'string', 'max:20'],
            'timezone' => ['nullable', 'string', 'max:50'],
            'currency' => ['nullable', 'string', 'max:10'],
            'is_active' => ['boolean'],
            'brand_type' => ['nullable', 'string', Rule::in([Brand::TYPE_REGULAR, Brand::TYPE_RESELLER_HUB, Brand::TYPE_RESELLER_BRANCH])],
            'parent_brand_id' => ['nullable', 'string', 'exists:brands,id'],
        ]);

        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('brand_logos', 'public');
        }

        $data['kode'] = Str::upper($data['kode']);
        $data['created_by'] = $user->id;
        $data['is_active'] = $data['is_active'] ?? true;

        if ($user->hasRole('admin_reseller') && ! $user->isSuperadmin()) {
            if (!in_array($data['brand_type'] ?? '', [Brand::TYPE_RESELLER_HUB, Brand::TYPE_RESELLER_BRANCH])) {
                $data['brand_type'] = Brand::TYPE_RESELLER_HUB;
            }
        } else {
            $data['brand_type'] = $data['brand_type'] ?? Brand::TYPE_REGULAR;
        }

        if ($data['brand_type'] === Brand::TYPE_RESELLER_BRANCH && empty($data['parent_brand_id'])) {
            return back()->withErrors(['parent_brand_id' => 'Brand Utama wajib dipilih jika tipe brand adalah Reseller Branch.']);
        }

        if (!in_array($data['brand_type'], [Brand::TYPE_RESELLER_BRANCH, Brand::TYPE_RESELLER_HUB])) {
            $data['parent_brand_id'] = null;
        }

        $brand = Brand::create($data);

        // Auto-create CASH bank account untuk brand baru
        \App\Models\Master\BankAccount::create([
            'brand_id' => $brand->id,
            'bank' => 'CASH',
            'atas_nama' => 'Cash',
            'nomor_rekening' => 'CASH',
            'is_active' => true,
        ]);

        // Otomatis assign admin_reseller ke reseller baru yang mereka buat
        if ($user->hasRole('admin_reseller') && ! $user->isSuperadmin()) {
            $user->brands()->attach($brand->id, [
                'is_default' => false,
                'assigned_at' => now(),
                'assigned_by' => $user->id,
            ]);
        }

        \App\Services\ActivityLogger::log('create', 'brand', $brand, "Tambah brand / reseller: {$brand->nama_brand} ({$brand->kode})");

        return redirect()->route('brands.index')->with('success', "Reseller {$brand->nama_brand} berhasil ditambahkan.");
    }

    public function update(Request $request, Brand $brand)
    {
        Gate::authorize('brand.update');

        $user = $request->user();
        if (! $user->isSuperadmin()) {
            if ($user->hasRole('admin_reseller')) {
                // admin_reseller hanya bisa update branch di bawah hub/induk-nya
                $parentIds = $user->brands()->whereIn('brand_type', [Brand::TYPE_RESELLER_HUB, Brand::TYPE_REGULAR])->pluck('brands.id');
                abort_unless(
                    $brand->brand_type === Brand::TYPE_RESELLER_BRANCH && $parentIds->contains($brand->parent_brand_id),
                    403
                );
            } elseif (! $user->hasAccessToBrand($brand->id)) {
                abort(403);
            }
        }

        $logoRule = $request->hasFile('logo') ? ['image', 'max:2048'] : ['nullable', 'string'];

        $data = $request->validate([
            'nama_brand' => ['required', 'string', 'max:100'],
            'kode' => ['required', 'string', 'max:20', 'alpha_dash', Rule::unique('brands', 'kode')->ignore($brand->id)],
            'tagline' => ['nullable', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string'],
            'logo' => ['nullable', 'sometimes', $logoRule],
            'email' => ['nullable', 'email', 'max:255'],
            'no_hp' => ['nullable', 'string', 'max:20'],
            'alamat' => ['nullable', 'string'],
            'instagram' => ['nullable', 'string', 'max:100'],
            'facebook' => ['nullable', 'string', 'max:100'],
            'tiktok' => ['nullable', 'string', 'max:100'],
            'whatsapp' => ['nullable', 'string', 'max:20'],
            'website' => ['nullable', 'string', 'max:255'],
            'warna_primary' => ['nullable', 'string', 'max:20'],
            'timezone' => ['nullable', 'string', 'max:50'],
            'currency' => ['nullable', 'string', 'max:10'],
            'is_active' => ['boolean'],
            'brand_type' => ['nullable', 'string', Rule::in([Brand::TYPE_REGULAR, Brand::TYPE_RESELLER_HUB, Brand::TYPE_RESELLER_BRANCH])],
            'parent_brand_id' => ['nullable', 'string', 'exists:brands,id'],
        ]);

        if ($request->hasFile('logo')) {
            if ($brand->logo) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($brand->logo);
            }
            $data['logo'] = $request->file('logo')->store('brand_logos', 'public');
        }

        $data['kode'] = Str::upper($data['kode']);

        if ($user->hasRole('admin_reseller') && ! $user->isSuperadmin()) {
            if (!in_array($data['brand_type'] ?? '', [Brand::TYPE_RESELLER_HUB, Brand::TYPE_RESELLER_BRANCH])) {
                $data['brand_type'] = Brand::TYPE_RESELLER_HUB;
            }
        } else {
            $data['brand_type'] = $data['brand_type'] ?? Brand::TYPE_REGULAR;
        }

        if ($data['brand_type'] === Brand::TYPE_RESELLER_BRANCH && empty($data['parent_brand_id'])) {
            return back()->withErrors(['parent_brand_id' => 'Brand Utama wajib dipilih jika tipe brand adalah Reseller Branch.']);
        }

        if (!in_array($data['brand_type'], [Brand::TYPE_RESELLER_BRANCH, Brand::TYPE_RESELLER_HUB])) {
            $data['parent_brand_id'] = null;
        }

        $brand->update($data);

        \App\Services\ActivityLogger::log('update', 'brand', $brand, "Perbarui brand: {$brand->nama_brand} ({$brand->kode})");

        return redirect()->route('brands.index')->with('success', 'Brand berhasil diperbarui.');
    }

    public function destroy(Request $request, Brand $brand)
    {
        Gate::authorize('brand.delete');

        $user = $request->user();
        if (! $user->isSuperadmin() && $user->hasRole('admin_reseller')) {
            $parentIds = $user->brands()->whereIn('brand_type', [Brand::TYPE_RESELLER_HUB, Brand::TYPE_REGULAR])->pluck('brands.id');
            abort_unless(
                $brand->brand_type === Brand::TYPE_RESELLER_BRANCH && $parentIds->contains($brand->parent_brand_id),
                403
            );
        }

        if ($brand->users()->exists()) {
            return redirect()->route('brands.index')
                ->with('error', 'Brand tidak bisa dihapus karena masih memiliki user yang terhubung.');
        }

        $brandName = $brand->nama_brand;
        $brandCode = $brand->kode;
        $brand->delete();

        \App\Services\ActivityLogger::log('delete', 'brand', null, "Hapus brand: {$brandName} ({$brandCode})");

        return redirect()->route('brands.index')->with('success', 'Brand Reseller berhasil dihapus.');
    }

    public function toggle(Request $request, Brand $brand)
    {
        Gate::authorize('brand.update');

        $brand->update(['is_active' => ! $brand->is_active]);

        $statusStr = $brand->is_active ? 'aktifkan' : 'nonaktifkan';
        \App\Services\ActivityLogger::log('toggle', 'brand', $brand, "Ubah status brand {$brand->nama_brand}: {$statusStr}");

        return back()->with('success', 'Status brand diperbarui.');
    }

    /**
     * Admin Reseller mengambil alih pengelolaan brand reseller yang belum dikelola.
     * Brand langsung di-assign ke user tanpa perlu persetujuan superadmin.
     */
    public function takeOwnership(Request $request, Brand $brand)
    {
        $user = $request->user();
        abort_unless($user->hasRole('admin_reseller') || $user->isSuperadmin(), 403);

        // Hanya untuk reseller hub/branch
        abort_unless(
            in_array($brand->brand_type, [Brand::TYPE_RESELLER_HUB, Brand::TYPE_RESELLER_BRANCH]),
            422, 'Hanya bisa mengambil alih brand reseller.'
        );

        // Assign ke user jika belum
        if (! $user->hasAccessToBrand($brand->id)) {
            $user->brands()->attach($brand->id, [
                'is_default'  => false,
                'assigned_at' => now(),
                'assigned_by' => $user->id,
            ]);
        }

        return back()->with('success', "Brand {$brand->nama_brand} berhasil diambil alih. Anda sekarang bisa mengelolanya.");
    }

    public function downloadTemplate(Request $request)
    {
        Gate::authorize('brand.create');
        if (!SystemSetting::get('system', 'customer_import_enabled', false)) {
            abort(403, 'Fitur impor dinonaktifkan oleh administrator.');
        }

        $headers = [
            'kode',
            'nama_brand',
            'brand_type',
            'tagline',
            'deskripsi',
            'email',
            'no_hp',
            'alamat',
            'warna_primary'
        ];

        $callback = function() use ($headers) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $headers);
            
            // Add sample row
            fputcsv($file, [
                'ALG',
                'Apparel Allegiant',
                'regular',
                'Premium Sportwear',
                'Produsen jersey berkualitas tinggi',
                'info@allegiant.id',
                '081223344556',
                'Bandung, Jawa Barat',
                '#000000'
            ]);
            fclose($file);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="format_baku_brand.csv"',
        ]);
    }

    public function import(Request $request)
    {
        Gate::authorize('brand.create');
        if (!SystemSetting::get('system', 'customer_import_enabled', false)) {
            abort(403, 'Fitur impor dinonaktifkan oleh administrator.');
        }

        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt'],
        ]);

        $file = $request->file('file');
        $path = $file->getRealPath();

        $handle = fopen($path, 'r');
        if (!$handle) {
            return back()->with('error', 'Gagal membuka file CSV.');
        }

        $headers = fgetcsv($handle, 1000, ',');
        if ($headers) {
            $headers[0] = preg_replace('/[\x{FEFF}\x{FFFE}\x{EFBB}\x{BFB0}]/u', '', $headers[0]);
            $headers = array_map('trim', $headers);
        }

        $headerMap = array_flip($headers);

        $required = ['kode', 'nama_brand'];
        foreach ($required as $req) {
            if (!isset($headerMap[$req])) {
                fclose($handle);
                return back()->with('error', "Format CSV salah. Kolom '{$req}' wajib ada.");
            }
        }

        $imported = 0;
        $errors = [];
        $lineNum = 1;

        DB::beginTransaction();
        try {
            while (($row = fgetcsv($handle, 1000, ',')) !== false) {
                $lineNum++;
                if (empty(array_filter($row))) continue;

                $data = [];
                foreach ($headerMap as $col => $index) {
                    $data[$col] = isset($row[$index]) ? trim($row[$index]) : null;
                }

                if (empty($data['kode']) || empty($data['nama_brand'])) {
                    $errors[] = "Baris {$lineNum}: Kolom 'kode' dan 'nama_brand' tidak boleh kosong.";
                    continue;
                }

                $brandCode = Str::upper($data['kode']);
                $brandType = strtolower($data['brand_type'] ?? 'regular');
                if (!in_array($brandType, ['regular', 'reseller_hub', 'reseller_branch'])) {
                    $brandType = 'regular';
                }

                // Create or Update Brand
                $brand = Brand::updateOrCreate(
                    ['kode' => $brandCode],
                    [
                        'nama_brand' => $data['nama_brand'],
                        'brand_type' => $brandType,
                        'tagline' => $data['tagline'],
                        'deskripsi' => $data['deskripsi'],
                        'email' => $data['email'],
                        'no_hp' => $data['no_hp'],
                        'alamat' => $data['alamat'],
                        'warna_primary' => $data['warna_primary'] ?? '#000000',
                        'is_active' => true,
                        'created_by' => $request->user()->id,
                    ]
                );

                // Auto-create CASH bank account for brand if not exists
                if (!$brand->bankAccounts()->where('bank', 'CASH')->exists()) {
                    \App\Models\Master\BankAccount::create([
                        'brand_id' => $brand->id,
                        'bank' => 'CASH',
                        'atas_nama' => 'Cash',
                        'nomor_rekening' => 'CASH',
                        'is_active' => true,
                    ]);
                }

                $imported++;
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            fclose($handle);
            return back()->with('error', 'Terjadi kesalahan saat mengimpor brand: ' . $e->getMessage());
        }

        fclose($handle);

        if (count($errors) > 0) {
            return back()->with('success', "Berhasil mengimpor {$imported} brand.")
                ->with('warning', implode('<br>', $errors));
        }

        return back()->with('success', "Berhasil mengimpor {$imported} brand.");
    }
}
