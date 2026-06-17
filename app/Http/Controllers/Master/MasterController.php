<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Support\BrandContext;
use App\Support\MasterRegistry;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class MasterController extends Controller
{
    public function index(Request $request, string $slug)
    {
        $config = $this->resolveConfig($slug);
        $this->authorizeForConfig($config);
        $modelClass = $config['model'];

        $query = $modelClass::query();
        $brandId = BrandContext::masterDataId($request);

        if ($config['scope'] === 'brand_nullable' && $brandId) {
            $query->where(function ($q) use ($brandId) {
                $q->where('brand_id', $brandId)->orWhereNull('brand_id');
            });
        } elseif ($config['scope'] === 'brand' && $brandId) {
            $query->where('brand_id', $brandId);
        }

        if ($search = $request->string('q')->toString()) {
            $query->where(function ($q) use ($config, $search) {
                foreach ($config['search_fields'] as $f) {
                    $q->orWhere($f, 'like', "%{$search}%");
                }
            });
        }

        if (($status = $request->string('status')->toString()) !== '') {
            if ($status === 'active') $query->where('is_active', true);
            elseif ($status === 'inactive') $query->where('is_active', false);
        }

        $query->orderBy($config['order_by']);
        if (!empty($config['secondary_order'])) {
            $query->orderBy($config['secondary_order']);
        }

        $items = $query->paginate(20)->withQueryString();

        return Inertia::render('Master/Index', [
            'config' => $this->publicConfig($config),
            'items' => $items,
            'filters' => [
                'q' => $request->string('q')->toString(),
                'status' => $request->string('status')->toString(),
            ],
            'can' => [
                'manage' => $this->canManageConfig($request->user(), $config),
            ],
        ]);
    }

    public function store(Request $request, string $slug)
    {
        $config = $this->resolveConfig($slug);
        abort_unless($this->canManageConfig($request->user(), $config), 403, 'Aksi ini tidak diizinkan untuk role Anda.');

        $data = $this->validatePayload($request, $config);

        if (in_array($config['scope'], ['brand', 'brand_nullable'], true)) {
            $brandId = BrandContext::masterDataId($request);
            if ($config['scope'] === 'brand' && ! $brandId) {
                return back()->with('error', 'Brand aktif belum dipilih.');
            }
            $data['brand_id'] = $brandId;
        }

        $modelClass = $config['model'];
        $modelClass::create($data);

        return back()->with('success', $config['label'] . ' berhasil dibuat.');
    }

    public function update(Request $request, string $slug, string $id)
    {
        $config = $this->resolveConfig($slug);
        abort_unless($this->canManageConfig($request->user(), $config), 403, 'Aksi ini tidak diizinkan untuk role Anda.');

        $modelClass = $config['model'];
        $record = $modelClass::findOrFail($id);

        $this->guardBrandOwnership($request, $config, $record);

        $data = $this->validatePayload($request, $config, $id);
        $record->update($data);

        return back()->with('success', $config['label'] . ' berhasil diperbarui.');
    }

    public function destroy(Request $request, string $slug, string $id)
    {
        $config = $this->resolveConfig($slug);
        abort_unless($this->canManageConfig($request->user(), $config), 403, 'Aksi ini tidak diizinkan untuk role Anda.');

        $modelClass = $config['model'];
        $record = $modelClass::findOrFail($id);
        $this->guardBrandOwnership($request, $config, $record);

        $record->delete();

        return back()->with('success', $config['label'] . ' berhasil dihapus.');
    }

    private function resolveConfig(string $slug): array
    {
        $config = MasterRegistry::find($slug);
        abort_if(! $config, Response::HTTP_NOT_FOUND, "Master '{$slug}' tidak ditemukan.");
        return $config;
    }

    private function authorizeForConfig(array $config): void
    {
        $user = request()->user();
        if ($user->can('master.manage') || $user->can('master.view')) return;
        if ($user->can('master.brand') && ($config['group'] === 'order' || $config['slug'] === 'bank')) return;
        if ($user->can('master.produk') && $config['slug'] === 'produk') return;
        // admin_produksi: akses semua master global (bahan, size, logo, pola, dll) + production (progress)
        if ($user->can('master.production') && in_array($config['group'], ['production', 'global'])) return;
        abort(403);
    }

    private function canManageConfig($user, array $config): bool
    {
        if ($user->can('master.manage')) return true;
        if ($user->can('master.brand') && ($config['group'] === 'order' || $config['slug'] === 'bank')) return true;
        if ($user->can('master.produk') && $config['slug'] === 'produk') return true;
        if ($user->can('master.production') && in_array($config['group'], ['production', 'global'])) return true;
        return false;
    }

    private function guardBrandOwnership(Request $request, array $config, $record): void
    {
        if (! in_array($config['scope'], ['brand', 'brand_nullable'], true)) return;
        if (! isset($record->brand_id) || $record->brand_id === null) return;

        $brandId = BrandContext::masterDataId($request);
        if ($record->brand_id !== $brandId && ! $request->user()->isSuperadmin()) {
            abort(403, 'Record ini milik brand lain.');
        }
    }

    private function validatePayload(Request $request, array $config, ?string $ignoreId = null): array
    {
        $rules = [];
        $data = [];

        foreach ($config['fields'] as $field) {
            $name = $field['name'];
            $r = [];

            if (! empty($field['required'])) {
                $r[] = $field['type'] === 'switch' ? 'sometimes' : 'required';
            } else {
                $r[] = 'nullable';
            }

            switch ($field['type']) {
                case 'number':
                    $r[] = 'numeric';
                    break;
                case 'switch':
                    $r[] = 'boolean';
                    break;
                case 'color':
                    $r[] = 'string';
                    $r[] = 'max:20';
                    break;
                case 'textarea':
                    $r[] = 'string';
                    break;
                case 'image':
                    $r[] = 'string';
                    $r[] = 'max:255';
                    break;
                case 'select':
                    if (! empty($field['options'])) {
                        $r[] = Rule::in(array_column($field['options'], 'value'));
                    }
                    break;
                default:
                    $r[] = 'string';
                    if (! empty($field['max'])) $r[] = 'max:' . $field['max'];
            }

            $rules[$name] = $r;
        }

        $validated = $request->validate($rules);

        foreach ($config['fields'] as $field) {
            $name = $field['name'];
            $value = $validated[$name] ?? ($field['default'] ?? null);
            if ($field['type'] === 'switch') {
                $value = (bool) ($validated[$name] ?? $field['default'] ?? false);
            }
            $data[$name] = $value;
        }

        return $data;
    }

    private function publicConfig(array $config): array
    {
        return [
            'slug' => $config['slug'],
            'label' => $config['label'],
            'group' => $config['group'],
            'icon' => $config['icon'],
            'scope' => $config['scope'],
            'fields' => $config['fields'],
            'list_columns' => $config['list_columns'],
        ];
    }
}
