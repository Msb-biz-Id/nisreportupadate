<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class BrandController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('brand.view');

        $user = $request->user();
        $query = Brand::query()->withCount('users');

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

        return Inertia::render('Brand/Index', [
            'brands' => $brands,
            'filters' => [
                'q' => $request->string('q')->toString(),
                'status' => $request->string('status')->toString(),
            ],
            'can' => [
                'create' => $user->can('brand.create'),
                'update' => $user->can('brand.update'),
                'delete' => $user->can('brand.delete'),
            ],
            'is_admin_reseller' => $user->hasRole('admin_reseller'),
            // IDs brand yang admin_reseller punya akses penuh (untuk tampilkan tombol aksi)
            'accessible_brand_ids' => $user->hasRole('admin_reseller')
                ? $user->brands()->pluck('brands.id')->toArray()
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
        ]);

        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('brand_logos', 'public');
        }

        $data['kode'] = Str::upper($data['kode']);
        $data['created_by'] = $user->id;
        $data['is_active'] = $data['is_active'] ?? true;

        // admin_reseller: membuat reseller baru sebagai hub mandiri
        // (bukan branch, melainkan entitas reseller independen yang mereka kelola)
        if ($user->hasRole('admin_reseller') && ! $user->isSuperadmin()) {
            $data['brand_type'] = Brand::TYPE_RESELLER_HUB;
            $data['parent_brand_id'] = null;
        } else {
            $data['brand_type'] = $data['brand_type'] ?? Brand::TYPE_REGULAR;
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

        return redirect()->route('brands.index')->with('success', "Reseller {$brand->nama_brand} berhasil ditambahkan.");
    }

    public function update(Request $request, Brand $brand)
    {
        Gate::authorize('brand.update');

        $user = $request->user();
        if (! $user->isSuperadmin()) {
            if ($user->hasRole('admin_reseller')) {
                // admin_reseller hanya bisa update branch di bawah hub-nya
                $hubIds = $user->brands()->where('brand_type', Brand::TYPE_RESELLER_HUB)->pluck('brands.id');
                abort_unless(
                    $brand->brand_type === Brand::TYPE_RESELLER_BRANCH && $hubIds->contains($brand->parent_brand_id),
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
        ]);

        if ($request->hasFile('logo')) {
            if ($brand->logo) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($brand->logo);
            }
            $data['logo'] = $request->file('logo')->store('brand_logos', 'public');
        }

        $data['kode'] = Str::upper($data['kode']);
        $brand->update($data);

        return redirect()->route('brands.index')->with('success', 'Brand berhasil diperbarui.');
    }

    public function destroy(Request $request, Brand $brand)
    {
        Gate::authorize('brand.delete');

        $user = $request->user();
        if (! $user->isSuperadmin() && $user->hasRole('admin_reseller')) {
            $hubIds = $user->brands()->where('brand_type', Brand::TYPE_RESELLER_HUB)->pluck('brands.id');
            abort_unless(
                $brand->brand_type === Brand::TYPE_RESELLER_BRANCH && $hubIds->contains($brand->parent_brand_id),
                403
            );
        }

        if ($brand->users()->exists()) {
            return redirect()->route('brands.index')
                ->with('error', 'Brand tidak bisa dihapus karena masih memiliki user yang terhubung.');
        }

        $brand->delete();

        return redirect()->route('brands.index')->with('success', 'Brand Reseller berhasil dihapus.');
    }

    public function toggle(Request $request, Brand $brand)
    {
        Gate::authorize('brand.update');

        $brand->update(['is_active' => ! $brand->is_active]);

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
}
