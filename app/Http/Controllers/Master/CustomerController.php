<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Master\Customer;
use App\Models\Master\CustomerType;
use App\Models\Master\SumberOrder;
use App\Support\BrandContext;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\Settings\SystemSetting;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeBrandMaster($request->user());

        $brandId = BrandContext::masterDataId($request);
        $query = Customer::query()->with(['customerType:id,nama'])
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
            'can' => [
                'manage' => $request->user()->can('master.manage') || $request->user()->can('master.brand'),
                'import' => (bool) SystemSetting::get('system', 'customer_import_enabled', false),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeBrandMasterWrite($request->user());

        $data = $this->validatePayload($request);

        if (empty($data['kode'])) {
            $data['kode'] = $this->generateKode($request);
        }

        $brandId = BrandContext::masterDataId($request);
        $data['brand_id'] = $brandId;

        Customer::create($data);

        return back()->with('success', 'Pelanggan berhasil dibuat.');
    }

    public function update(Request $request, Customer $customer)
    {
        $this->authorizeBrandMasterWrite($request->user());
        $this->guardOwnership($request, $customer);

        $data = $this->validatePayload($request, $customer->id);
        $customer->update($data);

        return back()->with('success', 'Pelanggan berhasil diperbarui.');
    }

    public function destroy(Request $request, Customer $customer)
    {
        $this->authorizeBrandMasterWrite($request->user());
        $this->guardOwnership($request, $customer);

        $customer->delete();
        return back()->with('success', 'Pelanggan berhasil dihapus.');
    }

    private function authorizeBrandMaster($user): void
    {
        if ($user->can('master.manage') || $user->can('master.brand') || $user->can('master.view')) return;
        abort(403);
    }

    private function authorizeBrandMasterWrite($user): void
    {
        if ($user->can('master.manage') || $user->can('master.brand')) return;
        abort(403, 'Aksi ini tidak diizinkan untuk role Anda.');
    }

    private function validatePayload(Request $request, ?string $ignoreId = null): array
    {
        $brandId = BrandContext::masterDataId($request);

        $rules = [
            'nama' => ['required', 'string', 'max:255'],
            'kode' => ['nullable', 'string', 'max:50',
                Rule::unique('customers', 'kode')->where(fn ($q) => $q->where('brand_id', $brandId))->ignore($ignoreId),
            ],
            'nomor_hp' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'type_pelanggan_id' => ['nullable', 'uuid', 'exists:customer_types,id'],
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

    public function downloadTemplate(Request $request)
    {
        $this->authorizeBrandMasterWrite($request->user());
        if (!SystemSetting::get('system', 'customer_import_enabled', false)) {
            abort(403, 'Fitur impor dinonaktifkan oleh administrator.');
        }
        $headers = [
            'customer_nama',
            'customer_kode',
            'customer_nomor_hp',
            'customer_email',
            'customer_type',
            'customer_detail_alamat',
            'customer_kodepos',
            'customer_notes',
            'provinsi_nama',
            'kabupaten_nama',
            'kecamatan_nama',
            'desa_nama',
            'brand_code',
        ];

        $callback = function() use ($headers) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $headers);
            
            // Add sample row
            fputcsv($file, [
                'Budi Santoso',
                'CUST-00001',
                '081234567890',
                'budi@example.com',
                'Reguler',
                'Jl. Mawar No. 10',
                '12345',
                'Pelanggan Loyal',
                'JAWA BARAT',
                'KOTA BANDUNG',
                'COBLONG',
                'DAGO',
                'ALG',
            ]);
            fclose($file);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="format_baku_pelanggan.csv"',
        ]);
    }

    public function import(Request $request)
    {
        $this->authorizeBrandMasterWrite($request->user());
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

        $required = ['customer_nama', 'customer_nomor_hp', 'brand_code'];
        foreach ($required as $req) {
            if (!isset($headerMap[$req])) {
                fclose($handle);
                return back()->with('error', "Format CSV tidak valid. Kolom '{$req}' wajib ada.");
            }
        }

        $imported = 0;
        $errors = [];
        $rowNum = 1;

        DB::beginTransaction();
        try {
            while (($row = fgetcsv($handle, 1000, ',')) !== false) {
                $rowNum++;
                if (empty(array_filter($row))) continue;

                $data = [];
                foreach ($headerMap as $col => $index) {
                    $data[$col] = isset($row[$index]) ? trim($row[$index]) : null;
                }

                $custNama = $data['customer_nama'];
                $custHp = $data['customer_nomor_hp'];
                $brandCode = strtoupper($data['brand_code']);

                if (!$custNama || !$custHp || !$brandCode) {
                    $errors[] = "Baris {$rowNum}: Nama, No HP, dan Kode Brand wajib diisi.";
                    continue;
                }

                // 1. Process Brand (Must exist)
                $brand = \App\Models\Brand::where('kode', $brandCode)->first();
                if (!$brand) {
                    $errors[] = "Baris {$rowNum}: Brand dengan kode '{$brandCode}' tidak ditemukan. Silakan buat atau impor brand terlebih dahulu.";
                    continue;
                }

                // 2. Customer Type
                $typeId = null;
                $custTypeName = $data['customer_type'] ?? 'Reguler';
                if ($custTypeName) {
                    $cType = \App\Models\Master\CustomerType::where('brand_id', $brand->id)
                        ->where('nama', $custTypeName)
                        ->first();
                    if (!$cType) {
                        $cType = \App\Models\Master\CustomerType::create([
                            'brand_id' => $brand->id,
                            'nama' => $custTypeName,
                            'diskon_default' => 0,
                            'is_active' => true,
                        ]);
                    }
                    $typeId = $cType->id;
                }

                // 3. Regional names to codes using local database
                $provCode = null; $provNama = $data['provinsi_nama'];
                $kabCode = null; $kabNama = $data['kabupaten_nama'];
                $kecCode = null; $kecNama = $data['kecamatan_nama'];
                $desaCode = null; $desaNama = $data['desa_nama'];

                $prefix = config('laravolt.indonesia.table_prefix', 'indonesia_');

                if ($provNama && Schema::hasTable($prefix . 'provinces')) {
                    $dbProv = DB::table($prefix . 'provinces')->where('name', 'like', "%{$provNama}%")->first();
                    if ($dbProv) {
                        $provCode = $dbProv->code;
                        $provNama = $dbProv->name;
                    }
                }
                if ($kabNama && $provCode && Schema::hasTable($prefix . 'cities')) {
                    $dbKab = DB::table($prefix . 'cities')
                        ->where('province_code', $provCode)
                        ->where('name', 'like', "%{$kabNama}%")
                        ->first();
                    if ($dbKab) {
                        $kabCode = $dbKab->code;
                        $kabNama = $dbKab->name;
                    }
                }
                if ($kecNama && $kabCode && Schema::hasTable($prefix . 'districts')) {
                    $dbKec = DB::table($prefix . 'districts')
                        ->where('city_code', $kabCode)
                        ->where('name', 'like', "%{$kecNama}%")
                        ->first();
                    if ($dbKec) {
                        $kecCode = $dbKec->code;
                        $kecNama = $dbKec->name;
                    }
                }
                if ($desaNama && $kecCode && Schema::hasTable($prefix . 'villages')) {
                    $dbDesa = DB::table($prefix . 'villages')
                        ->where('district_code', $kecCode)
                        ->where('name', 'like', "%{$desaNama}%")
                        ->first();
                    if ($dbDesa) {
                        $desaCode = $dbDesa->code;
                        $desaNama = $dbDesa->name;
                    }
                }

                // Generate customer code if not provided
                $custCode = $data['customer_code'] ?? $data['customer_kode'];
                if (!$custCode) {
                    $prefixCode = 'CUST';
                    $next = Customer::where('brand_id', $brand->id)->withTrashed()->count() + 1;
                    $custCode = $prefixCode . '-' . Str::padLeft((string) $next, 5, '0');
                }

                // 4. Create or Update Customer
                Customer::updateOrCreate(
                    [
                        'brand_id' => $brand->id,
                        'nomor_hp' => $custHp,
                    ],
                    [
                        'nama' => $custNama,
                        'kode' => $custCode,
                        'email' => $data['customer_email'],
                        'type_pelanggan_id' => $typeId,
                        'provinsi_code' => $provCode,
                        'provinsi_nama' => $provNama ?: $data['provinsi_nama'],
                        'kabupaten_code' => $kabCode,
                        'kabupaten_nama' => $kabNama ?: $data['kabupaten_nama'],
                        'kecamatan_code' => $kecCode,
                        'kecamatan_nama' => $kecNama ?: $data['kecamatan_nama'],
                        'desa_code' => $desaCode,
                        'desa_nama' => $desaNama ?: $data['desa_nama'],
                        'detail_alamat' => $data['customer_detail_alamat'],
                        'kodepos' => $data['customer_kodepos'],
                        'notes' => $data['customer_notes'],
                        'is_active' => true,
                    ]
                );

                $imported++;
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            fclose($handle);
            return back()->with('error', 'Terjadi kesalahan saat mengimpor data: ' . $e->getMessage());
        }

        fclose($handle);

        if (count($errors) > 0) {
            return back()->with('success', "Berhasil mengimpor {$imported} pelanggan.")
                ->with('warning', implode('<br>', $errors));
        }

        return back()->with('success', "Berhasil mengimpor {$imported} pelanggan.");
    }

    private function guardOwnership(Request $request, Customer $customer): void
    {
        if ($customer->brand_id === null) return;
        if (! $request->user()->isSuperadmin() && ! $request->user()->hasAccessToBrand($customer->brand_id)) {
            abort(403);
        }
    }

    private function generateKode(Request $request): string
    {
        $brandId = BrandContext::masterDataId($request);
        $prefix = 'CUST';
        $next = Customer::where('brand_id', $brandId)->withTrashed()->count() + 1;
        return $prefix . '-' . Str::padLeft((string) $next, 5, '0');
    }
}
