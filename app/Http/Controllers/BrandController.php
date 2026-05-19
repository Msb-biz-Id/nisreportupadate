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

        $query = Brand::query()->withCount('users');

        if (! $request->user()->isSuperadmin()) {
            $brandIds = $request->user()->brands()->pluck('brands.id');
            $query->whereIn('id', $brandIds);
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
                'create' => $request->user()->can('brand.create'),
                'update' => $request->user()->can('brand.update'),
                'delete' => $request->user()->can('brand.delete'),
            ],
        ]);
    }

    public function store(Request $request)
    {
        Gate::authorize('brand.create');

        $data = $request->validate([
            'nama_brand' => ['required', 'string', 'max:100'],
            'kode' => ['required', 'string', 'max:20', 'alpha_dash', Rule::unique('brands', 'kode')],
            'tagline' => ['nullable', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string'],
            'email' => ['nullable', 'email', 'max:255'],
            'no_hp' => ['nullable', 'string', 'max:20'],
            'alamat' => ['nullable', 'string'],
            'warna_primary' => ['nullable', 'string', 'max:20'],
            'timezone' => ['nullable', 'string', 'max:50'],
            'currency' => ['nullable', 'string', 'max:10'],
            'is_active' => ['boolean'],
        ]);

        $data['kode'] = Str::upper($data['kode']);
        $data['created_by'] = $request->user()->id;
        $data['is_active'] = $data['is_active'] ?? true;

        Brand::create($data);

        return redirect()->route('brands.index')->with('success', 'Brand berhasil dibuat.');
    }

    public function update(Request $request, Brand $brand)
    {
        Gate::authorize('brand.update');

        if (! $request->user()->isSuperadmin() && ! $request->user()->hasAccessToBrand($brand->id)) {
            abort(403);
        }

        $data = $request->validate([
            'nama_brand' => ['required', 'string', 'max:100'],
            'kode' => ['required', 'string', 'max:20', 'alpha_dash', Rule::unique('brands', 'kode')->ignore($brand->id)],
            'tagline' => ['nullable', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string'],
            'email' => ['nullable', 'email', 'max:255'],
            'no_hp' => ['nullable', 'string', 'max:20'],
            'alamat' => ['nullable', 'string'],
            'warna_primary' => ['nullable', 'string', 'max:20'],
            'timezone' => ['nullable', 'string', 'max:50'],
            'currency' => ['nullable', 'string', 'max:10'],
            'is_active' => ['boolean'],
        ]);

        $data['kode'] = Str::upper($data['kode']);
        $brand->update($data);

        return redirect()->route('brands.index')->with('success', 'Brand berhasil diperbarui.');
    }

    public function destroy(Request $request, Brand $brand)
    {
        Gate::authorize('brand.delete');

        if ($brand->users()->exists()) {
            return redirect()->route('brands.index')
                ->with('error', 'Brand tidak bisa dihapus karena masih memiliki user yang terhubung.');
        }

        $brand->delete();

        return redirect()->route('brands.index')->with('success', 'Brand berhasil dihapus.');
    }

    public function toggle(Request $request, Brand $brand)
    {
        Gate::authorize('brand.update');

        $brand->update(['is_active' => ! $brand->is_active]);

        return back()->with('success', 'Status brand diperbarui.');
    }
}
