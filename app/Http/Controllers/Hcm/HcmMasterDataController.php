<?php

namespace App\Http\Controllers\Hcm;

use App\Http\Controllers\Controller;
use App\Models\Hcm\HcmMasterCategory;
use App\Models\Hcm\HcmMasterOption;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class HcmMasterDataController extends Controller
{
    /**
     * Tampilkan halaman Master Data Kepegawaian dengan tata letak Tab Vertikal.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('hcm.manage-master');

        $categories = HcmMasterCategory::withCount([
            'options as total_options_count',
            'options as active_options_count' => function ($query) {
                $query->where('is_active', true);
            },
        ])
        ->orderBy('order_index')
        ->orderBy('id')
        ->get();

        $activeCategoryCode = $request->query('category', $categories->first()?->code ?? 'job_level');
        $selectedCategory = $categories->firstWhere('code', $activeCategoryCode) ?? $categories->first();

        $search = $request->query('search', '');
        $statusFilter = $request->query('status', 'all');

        $options = [];
        if ($selectedCategory) {
            $escapedSearch = str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $search);

            $optionsQuery = HcmMasterOption::where('category_id', $selectedCategory->id)
                ->when($escapedSearch, function ($query, $term) {
                    $query->where(function ($q) use ($term) {
                        $q->where('name', 'like', "%{$term}%")
                          ->orWhere('code', 'like', "%{$term}%")
                          ->orWhere('description', 'like', "%{$term}%");
                    });
                })
                ->when($statusFilter === 'active', fn ($q) => $q->where('is_active', true))
                ->when($statusFilter === 'inactive', fn ($q) => $q->where('is_active', false))
                ->orderBy('order_index')
                ->orderBy('id');

            $options = $optionsQuery->get();
        }

        return Inertia::render('Hcm/MasterData/Index', [
            'categories' => $categories,
            'selectedCategory' => $selectedCategory,
            'options' => $options,
            'filters' => [
                'category' => $activeCategoryCode,
                'search' => $search,
                'status' => $statusFilter,
            ],
        ]);
    }

    /**
     * Tambah opsi data baru pada kategori terpilih.
     */
    public function storeOption(Request $request, HcmMasterCategory $category): RedirectResponse
    {
        Gate::authorize('hcm.manage-master');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'code' => ['nullable', 'string', 'max:100'],
            'order_index' => ['nullable', 'integer'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $maxOrder = $category->options()->max('order_index') ?? 0;

        HcmMasterOption::create([
            'category_id' => $category->id,
            'name' => trim($validated['name']),
            'code' => !empty($validated['code']) ? Str::slug($validated['code'], '_') : Str::slug($validated['name'], '_'),
            'order_index' => $validated['order_index'] ?? ($maxOrder + 1),
            'description' => $validated['description'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return redirect()->back()->with('success', "Opsi '{$validated['name']}' berhasil ditambahkan ke {$category->name}.");
    }

    /**
     * Perbarui data opsi.
     */
    public function updateOption(Request $request, HcmMasterOption $option): RedirectResponse
    {
        Gate::authorize('hcm.manage-master');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'code' => ['nullable', 'string', 'max:100'],
            'order_index' => ['nullable', 'integer'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $option->update([
            'name' => trim($validated['name']),
            'code' => !empty($validated['code']) ? Str::slug($validated['code'], '_') : $option->code,
            'order_index' => $validated['order_index'] ?? $option->order_index,
            'description' => $validated['description'] ?? null,
            'is_active' => $validated['is_active'] ?? $option->is_active,
        ]);

        return redirect()->back()->with('success', "Opsi '{$option->name}' berhasil diperbarui.");
    }

    /**
     * Toggle status aktif/non-aktif opsi.
     */
    public function toggleOption(HcmMasterOption $option): RedirectResponse
    {
        Gate::authorize('hcm.manage-master');

        $option->update([
            'is_active' => !$option->is_active,
        ]);

        $statusText = $option->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return redirect()->back()->with('success', "Opsi '{$option->name}' berhasil {$statusText}.");
    }

    /**
     * Hapus opsi dari kategori.
     */
    public function destroyOption(HcmMasterOption $option): RedirectResponse
    {
        Gate::authorize('hcm.manage-master');

        $name = $option->name;
        $option->delete();

        return redirect()->back()->with('success', "Opsi '{$name}' berhasil dihapus.");
    }
}
