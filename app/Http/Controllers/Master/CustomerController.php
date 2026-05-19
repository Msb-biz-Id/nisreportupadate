<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Master\Customer;
use App\Models\Master\CustomerType;
use App\Models\Master\SumberOrder;
use App\Support\BrandContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('master.manage');

        $brandId = BrandContext::current($request);
        $query = Customer::query()->with(['customerType:id,nama', 'sumberOrder:id,nama'])
            ->when($brandId, fn ($q) => $q->where(function ($w) use ($brandId) {
                $w->where('brand_id', $brandId)->orWhereNull('brand_id');
            }));

        if ($search = $request->string('q')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('kode', 'like', "%{$search}%")
                  ->orWhere('nomor_hp', 'like', "%{$search}%");
            });
        }

        if ($status = $request->string('status')->toString()) {
            $query->where('is_active', $status === 'active');
        }

        $items = $query->orderBy('nama')->paginate(20)->withQueryString();

        return Inertia::render('Master/Customer/Index', [
            'items' => $items,
            'filters' => [
                'q' => $request->string('q')->toString(),
                'status' => $request->string('status')->toString(),
            ],
            'customerTypes' => CustomerType::query()
                ->when($brandId, fn ($q) => $q->where(function ($w) use ($brandId) {
                    $w->where('brand_id', $brandId)->orWhereNull('brand_id');
                }))
                ->active()->orderBy('nama')->get(['id', 'nama', 'diskon_default']),
            'sumberOrders' => SumberOrder::query()
                ->when($brandId, fn ($q) => $q->where(function ($w) use ($brandId) {
                    $w->where('brand_id', $brandId)->orWhereNull('brand_id');
                }))
                ->active()->orderBy('nama')->get(['id', 'nama']),
            'can' => [
                'manage' => $request->user()->can('master.manage'),
            ],
        ]);
    }

    public function store(Request $request)
    {
        Gate::authorize('master.manage');

        $data = $this->validatePayload($request);

        if (empty($data['kode'])) {
            $data['kode'] = $this->generateKode($request);
        }

        $brandId = BrandContext::current($request);
        $data['brand_id'] = $brandId;

        Customer::create($data);

        return back()->with('success', 'Pelanggan berhasil dibuat.');
    }

    public function update(Request $request, Customer $customer)
    {
        Gate::authorize('master.manage');
        $this->guardOwnership($request, $customer);

        $data = $this->validatePayload($request, $customer->id);
        $customer->update($data);

        return back()->with('success', 'Pelanggan berhasil diperbarui.');
    }

    public function destroy(Request $request, Customer $customer)
    {
        Gate::authorize('master.manage');
        $this->guardOwnership($request, $customer);

        $customer->delete();
        return back()->with('success', 'Pelanggan berhasil dihapus.');
    }

    private function validatePayload(Request $request, ?string $ignoreId = null): array
    {
        $brandId = BrandContext::current($request);

        $rules = [
            'nama' => ['required', 'string', 'max:255'],
            'kode' => ['nullable', 'string', 'max:50',
                Rule::unique('customers', 'kode')->where(fn ($q) => $q->where('brand_id', $brandId))->ignore($ignoreId),
            ],
            'nomor_hp' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'type_pelanggan_id' => ['nullable', 'uuid', 'exists:customer_types,id'],
            'sumber_daftar_id' => ['nullable', 'uuid', 'exists:sumber_orders,id'],
            'provinsi_code' => ['nullable', 'string', 'max:10'],
            'provinsi_nama' => ['nullable', 'string', 'max:100'],
            'kabupaten_code' => ['nullable', 'string', 'max:10'],
            'kabupaten_nama' => ['nullable', 'string', 'max:100'],
            'kecamatan_code' => ['nullable', 'string', 'max:10'],
            'kecamatan_nama' => ['nullable', 'string', 'max:100'],
            'desa_code' => ['nullable', 'string', 'max:15'],
            'desa_nama' => ['nullable', 'string', 'max:100'],
            'detail_alamat' => ['nullable', 'string'],
            'kodepos' => ['nullable', 'string', 'max:10'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ];

        return $request->validate($rules);
    }

    private function guardOwnership(Request $request, Customer $customer): void
    {
        if ($customer->brand_id === null) return;
        $brandId = BrandContext::current($request);
        if ($customer->brand_id !== $brandId && ! $request->user()->isSuperadmin()) {
            abort(403);
        }
    }

    private function generateKode(Request $request): string
    {
        $brandId = BrandContext::current($request);
        $prefix = 'CUST';
        $next = Customer::where('brand_id', $brandId)->withTrashed()->count() + 1;
        return $prefix . '-' . Str::padLeft((string) $next, 5, '0');
    }
}
