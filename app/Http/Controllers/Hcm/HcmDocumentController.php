<?php

namespace App\Http\Controllers\Hcm;

use App\Http\Controllers\Controller;
use App\Models\Hcm\HcmInternalDocument;
use App\Models\Hcm\HcmMasterOption;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class HcmDocumentController extends Controller
{
    /**
     * Tampilkan Modul Dokumen Internal & SOP Perusahaan.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('hcm.manage-documents');

        $categoryFilter = $request->query('category', 'all');
        $statusFilter = $request->query('status', 'all');
        $stageFilter = $request->query('stage', 'all');
        $departmentFilter = $request->query('department', 'all');
        $search = $request->query('search', '');

        $escapedSearch = str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $search);

        $documents = HcmInternalDocument::with('creator:id,name')
            ->when($escapedSearch, fn ($q, $t) => $q->where(fn ($sub) =>
                $sub->where('title', 'like', "%{$t}%")
                    ->orWhere('document_code', 'like', "%{$t}%")
                    ->orWhere('description', 'like', "%{$t}%")
                    ->orWhere('department', 'like', "%{$t}%")
            ))
            ->when($categoryFilter !== 'all', fn ($q) => $q->where('category', $categoryFilter))
            ->when($stageFilter !== 'all', fn ($q) => $q->where('document_stage', $stageFilter))
            ->when($departmentFilter !== 'all', fn ($q) => $q->where('department', $departmentFilter))
            ->when($statusFilter !== 'all', fn ($q) => $q->where('status', $statusFilter))
            ->orderByDesc('id')
            ->paginate(12)
            ->withQueryString();

        $metrics = [
            'total_documents' => HcmInternalDocument::count(),
            'active_sop' => HcmInternalDocument::where(fn ($q) =>
                $q->where('category', 'like', '%SOP%')
                    ->orWhere('document_stage', 'SOP & Kebijakan')
            )->whereIn('status', ['Aktif', 'Berlaku'])->count(),
            'pengajuan_count' => HcmInternalDocument::where('document_stage', 'Pengajuan')->count(),
            'realisasi_count' => HcmInternalDocument::where('document_stage', 'Realisasi')->count(),
            'total_proposed_budget' => (float) HcmInternalDocument::sum('proposed_budget'),
            'total_actual_budget' => (float) HcmInternalDocument::sum('actual_budget'),
        ];

        // Ambil Departemen / Divisi dinamis dari Master Data
        $departments = HcmMasterOption::getOptions('divisi') ?: [
            'Keuangan',
            'Human Capital Management',
            'Marketing',
            'Produksi',
            'Media Internal',
            'Media Eksternal',
        ];

        // Ambil Kategori Dokumen per Siklus dari Master Data (Blueprint Website HCM NIS.xlsx)
        $categoriesPengajuan = HcmMasterOption::getOptions('kategori_dokumen_pengajuan') ?: [
            'Pengajuan RAB (Rencana Anggaran Biaya)',
            'Proposal Kegiatan / Acara',
            'Pengajuan Pembelian Aset / Inventaris',
            'Pengajuan Perjalanan Dinas / Surat Tugas',
        ];

        $categoriesRealisasi = HcmMasterOption::getOptions('kategori_dokumen_realisasi') ?: [
            'LPJ (Laporan Pertanggungjawaban) Kegiatan',
            'Realisasi Pembelian Aset & Nota/Faktur Pembelanjaan',
            'Laporan & Bukti Pengeluaran Perjalanan Dinas (Reimburse / Settlement)',
        ];

        $categoriesKebijakan = HcmMasterOption::getOptions('dokumen_administratif_kebijakan') ?: [
            'Standard Operating Procedure (SOP)',
            'Surat Keputusan (SK) & Kebijakan Internal',
            'Kontrak / Perjanjian Kerjasama (Vendor / Partner)',
            'Surat Peringatan (SP 1 / SP 2 / SP 3)',
            'Surat Keputusan / Pemberitahuan PHK',
            'Surat Pengalaman Kerja (Paklaring)',
            'Surat Pengumuman Internal (Mutasi, Promosi, atau Kebijakan)',
            'Berita Acara / Surat Klarifikasi',
            'Surat Tugas & Perjalanan Dinas (SPPD)',
        ];

        $categoriesByStage = [
            'Pengajuan' => $categoriesPengajuan,
            'Realisasi' => $categoriesRealisasi,
            'SOP & Kebijakan' => $categoriesKebijakan,
        ];

        // Seluruh kategori gabungan untuk opsi filter & fallback
        $allCategories = array_values(array_unique(array_merge(
            $categoriesPengajuan,
            $categoriesRealisasi,
            $categoriesKebijakan,
            HcmInternalDocument::distinct()->whereNotNull('category')->pluck('category')->toArray()
        )));

        $stages = ['Pengajuan', 'Realisasi', 'SOP & Kebijakan'];
        $statuses = ['Berlaku', 'Selesai', 'Dalam Revisi', 'Dibatalkan', 'Arsip'];

        return Inertia::render('Hcm/Documents/Index', [
            'documents' => $documents,
            'filters' => [
                'category' => $categoryFilter,
                'status' => $statusFilter,
                'stage' => $stageFilter,
                'department' => $departmentFilter,
                'search' => $search,
            ],
            'metrics' => $metrics,
            'departments' => $departments,
            'categories' => $allCategories,
            'categoriesByStage' => $categoriesByStage,
            'stages' => $stages,
            'statuses' => $statuses,
        ]);
    }

    /**
     * Simpan Dokumen Internal / SOP baru.
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('hcm.manage-documents');

        $validated = $request->validate([
            'document_code' => ['required', 'string', 'max:100', 'unique:hcm_internal_documents,document_code'],
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:150'],
            'document_stage' => ['nullable', 'string', 'max:50'],
            'department' => ['nullable', 'string', 'max:100'],
            'revision_number' => ['nullable', 'string', 'max:30'],
            'proposed_budget' => ['nullable', 'numeric', 'min:0'],
            'actual_budget' => ['nullable', 'numeric', 'min:0'],
            'effective_date' => ['nullable', 'date'],
            'submission_date' => ['nullable', 'date'],
            'approval_date' => ['nullable', 'date'],
            'status' => ['required', 'string', 'max:50'],
            'file_upload' => ['nullable', 'file', 'mimes:pdf,doc,docx,xls,xlsx,png,jpg', 'max:10240'],
            'file_url' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        if ($request->hasFile('file_upload')) {
            $uploaded = \App\Services\GoogleDriveSyncService::uploadFile(
                $request->file('file_upload'),
                \App\Services\GoogleDriveSyncService::FOLDER_DOCUMENTS
            );
            $validated['file_url'] = $uploaded['url'];
        }
        unset($validated['file_upload']);

        HcmInternalDocument::create([
            ...$validated,
            'created_by' => Auth::id(),
        ]);

        return redirect()->back()->with('success', "Dokumen {$validated['document_code']} - {$validated['title']} berhasil ditambahkan.");
    }

    /**
     * Perbarui Dokumen Internal / SOP.
     */
    public function update(Request $request, HcmInternalDocument $document): RedirectResponse
    {
        Gate::authorize('hcm.manage-documents');

        $validated = $request->validate([
            'document_code' => ['required', 'string', 'max:100', 'unique:hcm_internal_documents,document_code,' . $document->id],
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:150'],
            'document_stage' => ['nullable', 'string', 'in:Pengajuan,Realisasi,SOP & Kebijakan'],
            'department' => ['nullable', 'string', 'max:100'],
            'revision_number' => ['nullable', 'string', 'max:30'],
            'proposed_budget' => ['nullable', 'numeric', 'min:0'],
            'actual_budget' => ['nullable', 'numeric', 'min:0'],
            'effective_date' => ['nullable', 'date'],
            'submission_date' => ['nullable', 'date'],
            'approval_date' => ['nullable', 'date'],
            'status' => ['required', 'string', 'max:50'],
            'file_upload' => ['nullable', 'file', 'mimes:pdf,doc,docx,xls,xlsx,png,jpg', 'max:10240'],
            'file_url' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        if ($request->hasFile('file_upload')) {
            $uploaded = \App\Services\GoogleDriveSyncService::uploadFile(
                $request->file('file_upload'),
                \App\Services\GoogleDriveSyncService::FOLDER_DOCUMENTS
            );
            $validated['file_url'] = $uploaded['url'];
        }
        unset($validated['file_upload']);

        $document->update($validated);

        return redirect()->back()->with('success', "Dokumen {$document->document_code} berhasil diperbarui.");
    }

    /**
     * Hapus Dokumen Internal.
     */
    public function destroy(HcmInternalDocument $document): RedirectResponse
    {
        Gate::authorize('hcm.manage-documents');

        $code = $document->document_code;
        $document->delete();

        return redirect()->back()->with('success', "Dokumen {$code} berhasil dihapus.");
    }
}
