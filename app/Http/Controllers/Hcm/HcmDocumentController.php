<?php

namespace App\Http\Controllers\Hcm;

use App\Http\Controllers\Controller;
use App\Models\Hcm\HcmInternalDocument;
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
        $search = $request->query('search', '');

        $escapedSearch = str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $search);

        $documents = HcmInternalDocument::with('creator:id,name')
            ->when($escapedSearch, fn ($q, $t) => $q->where(fn ($sub) =>
                $sub->where('title', 'like', "%{$t}%")
                    ->orWhere('document_code', 'like', "%{$t}%")
                    ->orWhere('description', 'like', "%{$t}%")
            ))
            ->when($categoryFilter !== 'all', fn ($q) => $q->where('category', $categoryFilter))
            ->when($stageFilter !== 'all', fn ($q) => $q->where('document_stage', $stageFilter))
            ->when($statusFilter !== 'all', fn ($q) => $q->where('status', $statusFilter))
            ->orderBy('category')
            ->orderBy('document_code')
            ->paginate(12)
            ->withQueryString();

        $metrics = [
            'total_documents' => HcmInternalDocument::count(),
            'active_sop' => HcmInternalDocument::where('category', 'SOP')->where('status', 'Aktif')->count(),
            'company_regulations' => HcmInternalDocument::where('category', 'Peraturan Perusahaan')->count(),
            'standard_forms' => HcmInternalDocument::where('category', 'Formulir Standar')->count(),
            'pengajuan_count' => HcmInternalDocument::where('document_stage', 'Pengajuan')->count(),
            'realisasi_count' => HcmInternalDocument::where('document_stage', 'Realisasi')->count(),
            'total_proposed_budget' => (float) HcmInternalDocument::sum('proposed_budget'),
            'total_actual_budget' => (float) HcmInternalDocument::sum('actual_budget'),
        ];

        $categories = [
            'Pengajuan RAB (Rencana Anggaran Biaya)',
            'Proposal Kegiatan / Acara',
            'Pengajuan Pembelian Aset / Inventaris',
            'LPJ (Laporan Pertanggungjawaban) Kegiatan',
            'Realisasi Pembelian Aset & Nota/Faktur',
            'Laporan & Bukti Pengeluaran Perjalanan Dinas',
            'SOP (Standar Operasional Prosedur)',
            'Peraturan Perusahaan',
            'Formulir Standar',
            'Pedoman Keselamatan Kerja (K3)',
            'Kebijakan Direksi',
            'Lainnya',
        ];

        return Inertia::render('Hcm/Documents/Index', [
            'documents' => $documents,
            'filters' => [
                'category' => $categoryFilter,
                'status' => $statusFilter,
                'stage' => $stageFilter,
                'search' => $search,
            ],
            'metrics' => $metrics,
            'categories' => $categories,
            'stages' => ['Pengajuan', 'Realisasi'],
        ]);
    }

    /**
     * Simpan Dokumen Internal / SOP baru.
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('hcm.manage-documents');

        $validated = $request->validate([
            'document_code' => ['required', 'string', 'max:50', 'unique:hcm_internal_documents,document_code'],
            'title' => ['required', 'string', 'max:200'],
            'category' => ['required', 'string', 'max:100'],
            'document_stage' => ['nullable', 'string', 'in:Pengajuan,Realisasi'],
            'department' => ['nullable', 'string', 'max:100'],
            'revision_number' => ['required', 'string', 'max:20'],
            'proposed_budget' => ['nullable', 'numeric', 'min:0'],
            'actual_budget' => ['nullable', 'numeric', 'min:0'],
            'effective_date' => ['required', 'date'],
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
            'document_code' => ['required', 'string', 'max:50', 'unique:hcm_internal_documents,document_code,' . $document->id],
            'title' => ['required', 'string', 'max:200'],
            'category' => ['required', 'string', 'max:100'],
            'document_stage' => ['nullable', 'string', 'in:Pengajuan,Realisasi'],
            'department' => ['nullable', 'string', 'max:100'],
            'revision_number' => ['required', 'string', 'max:20'],
            'proposed_budget' => ['nullable', 'numeric', 'min:0'],
            'actual_budget' => ['nullable', 'numeric', 'min:0'],
            'effective_date' => ['required', 'date'],
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
