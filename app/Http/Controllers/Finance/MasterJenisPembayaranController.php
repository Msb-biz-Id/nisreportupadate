<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\MasterJenisPembayaran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Illuminate\Validation\Rule;

class MasterJenisPembayaranController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('finance.view');

        $query = MasterJenisPembayaran::query();

        if ($search = $request->string('q')->toString()) {
            $query->where('nama', 'like', "%{$search}%");
        }

        $items = $query->orderBy('nama')->paginate(20)->withQueryString();

        return Inertia::render('Finance/MasterJenisPembayaran', [
            'items' => $items,
            'filters' => ['q' => $request->string('q')->toString()],
        ]);
    }

    public function store(Request $request)
    {
        Gate::authorize('finance.view');

        $data = $request->validate([
            'nama' => ['required', 'string', 'max:255', 'unique:master_jenis_pembayarans,nama'],
            'tipe_keuangan' => ['required', Rule::in(['pemasukan', 'pengeluaran'])],
            'efek_tagihan' => ['required', Rule::in(['penambahan', 'pengurangan', 'netral'])],
            'is_active' => ['boolean'],
        ]);

        MasterJenisPembayaran::create($data);

        return back()->with('success', 'Master Jenis Pembayaran berhasil ditambahkan.');
    }

    public function update(Request $request, MasterJenisPembayaran $master)
    {
        Gate::authorize('finance.view');

        $data = $request->validate([
            'nama' => ['required', 'string', 'max:255', Rule::unique('master_jenis_pembayarans')->ignore($master->id)],
            'tipe_keuangan' => ['required', Rule::in(['pemasukan', 'pengeluaran'])],
            'efek_tagihan' => ['required', Rule::in(['penambahan', 'pengurangan', 'netral'])],
            'is_active' => ['boolean'],
        ]);

        $master->update($data);

        return back()->with('success', 'Master Jenis Pembayaran berhasil diperbarui.');
    }

    public function destroy(MasterJenisPembayaran $master)
    {
        Gate::authorize('finance.view');
        
        $master->delete();

        return back()->with('success', 'Master Jenis Pembayaran berhasil dihapus.');
    }
}
