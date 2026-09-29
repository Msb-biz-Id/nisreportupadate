<?php

namespace App\Http\Controllers\Hcm;

use App\Http\Controllers\Controller;
use App\Models\Hcm\HcmAgendaLetter;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class HcmLetterController extends Controller
{
    /**
     * Tampilkan Buku Agenda Persuratan (Surat Masuk & Surat Keluar).
     */
    public function index(Request $request): Response
    {
        Gate::authorize('hcm.manage-agenda');

        $typeFilter = $request->query('type', 'all');
        $search = $request->query('search', '');
        $yearFilter = $request->query('year', date('Y'));

        $escapedSearch = str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $search);

        $letters = HcmAgendaLetter::with('creator:id,name')
            ->when($escapedSearch, fn ($q, $t) => $q->where(fn ($sub) =>
                $sub->where('subject', 'like', "%{$t}%")
                    ->orWhere('letter_number', 'like', "%{$t}%")
                    ->orWhere('agenda_number', 'like', "%{$t}%")
                    ->orWhere('sender', 'like', "%{$t}%")
                    ->orWhere('recipient', 'like', "%{$t}%")
            ))
            ->when($typeFilter !== 'all', fn ($q) => $q->where('letter_type', $typeFilter))
            ->when($yearFilter !== 'all', fn ($q) => $q->whereYear('letter_date', (int) $yearFilter))
            ->orderBy('letter_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(12)
            ->withQueryString();

        $metrics = [
            'total_letters' => HcmAgendaLetter::count(),
            'incoming' => HcmAgendaLetter::where('letter_type', 'SURAT_MASUK')->count(),
            'outgoing' => HcmAgendaLetter::where('letter_type', 'SURAT_KELUAR')->count(),
            'internal_memos' => HcmAgendaLetter::whereIn('letter_type', ['INTERNAL_MEMO', 'SK_DIREKSI'])->count(),
        ];

        $letterTypes = [
            ['label' => 'Surat Masuk (Eksternal)', 'value' => 'SURAT_MASUK'],
            ['label' => 'Surat Keluar Resmi', 'value' => 'SURAT_KELUAR'],
            ['label' => 'Memo Internal / Nota Dinas', 'value' => 'INTERNAL_MEMO'],
            ['label' => 'Surat Keputusan (SK) Direksi', 'value' => 'SK_DIREKSI'],
        ];

        return Inertia::render('Hcm/Letters/Index', [
            'letters' => $letters,
            'filters' => [
                'type' => $typeFilter,
                'search' => $search,
                'year' => $yearFilter,
            ],
            'metrics' => $metrics,
            'letterTypes' => $letterTypes,
        ]);
    }

    /**
     * Catat surat masuk / keluar baru ke dalam buku agenda.
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('hcm.manage-agenda');

        $validated = $request->validate([
            'letter_type' => ['required', 'string', 'in:SURAT_MASUK,SURAT_KELUAR,INTERNAL_MEMO,SK_DIREKSI'],
            'letter_number' => ['required', 'string', 'max:100'],
            'letter_date' => ['required', 'date'],
            'received_or_sent_date' => ['nullable', 'date'],
            'sender' => ['required', 'string', 'max:150'],
            'recipient' => ['required', 'string', 'max:150'],
            'subject' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
            'file_upload' => ['nullable', 'file', 'mimes:pdf,doc,docx,xls,xlsx,png,jpg', 'max:10240'],
            'file_url' => ['nullable', 'string', 'max:255'],
            'physical_location' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ]);

        if ($request->hasFile('file_upload')) {
            $uploaded = \App\Services\GoogleDriveSyncService::uploadFile(
                $request->file('file_upload'),
                \App\Services\GoogleDriveSyncService::FOLDER_LETTERS
            );
            $validated['file_url'] = $uploaded['url'];
        }
        unset($validated['file_upload']);

        $letter = HcmAgendaLetter::create([
            ...$validated,
            'created_by' => Auth::id(),
        ]);

        ActivityLogger::log('create', 'hcm', $letter, "Mencatat surat agenda {$letter->agenda_number}: {$letter->subject} ({$letter->letter_number})");

        return redirect()->back()->with('success', "Surat nomor {$letter->letter_number} berhasil dicatat dengan No. Agenda {$letter->agenda_number}.");
    }

    /**
     * Perbarui data persuratan.
     */
    public function update(Request $request, HcmAgendaLetter $letter): RedirectResponse
    {
        Gate::authorize('hcm.manage-agenda');

        $validated = $request->validate([
            'letter_type' => ['required', 'string', 'in:SURAT_MASUK,SURAT_KELUAR,INTERNAL_MEMO,SK_DIREKSI'],
            'letter_number' => ['required', 'string', 'max:100'],
            'letter_date' => ['required', 'date'],
            'received_or_sent_date' => ['nullable', 'date'],
            'sender' => ['required', 'string', 'max:150'],
            'recipient' => ['required', 'string', 'max:150'],
            'subject' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
            'file_upload' => ['nullable', 'file', 'mimes:pdf,doc,docx,xls,xlsx,png,jpg', 'max:10240'],
            'file_url' => ['nullable', 'string', 'max:255'],
            'physical_location' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ]);

        if ($request->hasFile('file_upload')) {
            $uploaded = \App\Services\GoogleDriveSyncService::uploadFile(
                $request->file('file_upload'),
                \App\Services\GoogleDriveSyncService::FOLDER_LETTERS
            );
            $validated['file_url'] = $uploaded['url'];
        }
        unset($validated['file_upload']);

        $letter->update($validated);

        ActivityLogger::log('update', 'hcm', $letter, "Memperbarui agenda surat {$letter->agenda_number}: {$letter->subject}");

        return redirect()->back()->with('success', "Data agenda surat {$letter->agenda_number} berhasil diperbarui.");
    }

    /**
     * Hapus arsip persuratan.
     */
    public function destroy(HcmAgendaLetter $letter): RedirectResponse
    {
        Gate::authorize('hcm.manage-agenda');

        $agendaNo = $letter->agenda_number;
        $subject = $letter->subject;
        $letter->delete();

        ActivityLogger::log('delete', 'hcm', null, "Menghapus agenda surat {$agendaNo}: {$subject}");

        return redirect()->back()->with('success', "Agenda surat {$agendaNo} berhasil dihapus.");
    }
}
