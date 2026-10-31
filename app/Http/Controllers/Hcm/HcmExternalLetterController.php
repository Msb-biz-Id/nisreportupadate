<?php

namespace App\Http\Controllers\Hcm;

use App\Http\Controllers\Controller;
use App\Models\Hcm\HcmExternalLetter;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class HcmExternalLetterController extends Controller
{
    /**
     * Modul 9: Arsip Korespondensi Eksternal (BPJS/Disnaker/Bank/Mitra).
     */
    public function index(Request $request): Response
    {
        Gate::authorize('hcm.manage-documents');

        $directionFilter = $request->query('direction', 'all');
        $yearFilter = $request->query('year', date('Y'));
        $search = $request->query('search', '');

        $escapedSearch = str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $search);

        $letters = HcmExternalLetter::with('creator:id,name')
            ->when($escapedSearch, fn ($q, $t) => $q->where(fn ($sub) =>
                $sub->where('subject', 'like', "%{$t}%")
                    ->orWhere('external_letter_no', 'like', "%{$t}%")
                    ->orWhere('registration_no', 'like', "%{$t}%")
                    ->orWhere('sender', 'like', "%{$t}%")
                    ->orWhere('recipient', 'like', "%{$t}%")
            ))
            ->when($directionFilter !== 'all', fn ($q) => $q->where('direction', $directionFilter))
            ->when($yearFilter !== 'all', fn ($q) => $q->whereYear('letter_date', (int) $yearFilter))
            ->orderBy('letter_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(12)
            ->withQueryString();

        $metrics = [
            'total_letters' => HcmExternalLetter::count(),
            'incoming' => HcmExternalLetter::where('direction', 'Surat Masuk')->count(),
            'outgoing' => HcmExternalLetter::where('direction', 'Surat Keluar')->count(),
        ];

        return Inertia::render('Hcm/ExternalLetters/Index', [
            'letters' => $letters,
            'filters' => [
                'direction' => $directionFilter,
                'search' => $search,
                'year' => $yearFilter,
            ],
            'metrics' => $metrics,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('hcm.manage-documents');

        $validated = $request->validate([
            'letter_date' => ['required', 'date'],
            'direction' => ['required', 'string', 'in:Surat Masuk,Surat Keluar'],
            'external_letter_no' => ['required', 'string', 'max:100'],
            'sender' => ['required', 'string', 'max:150'],
            'recipient' => ['required', 'string', 'max:150'],
            'subject' => ['required', 'string'],
            'file_upload' => ['nullable', 'file', 'mimes:pdf,doc,docx,xls,xlsx,png,jpg', 'max:10240'],
            'file_scan_url' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        if ($request->hasFile('file_upload')) {
            $uploaded = \App\Services\GoogleDriveSyncService::uploadFile(
                $request->file('file_upload'),
                \App\Services\GoogleDriveSyncService::FOLDER_LETTERS
            );
            $validated['file_scan_url'] = $uploaded['url'];
        }
        unset($validated['file_upload']);
        $validated['created_by'] = Auth::id();

        $letter = HcmExternalLetter::create($validated);

        ActivityLogger::log('create', 'hcm', $letter, "Mencatat surat eksternal {$letter->registration_no}: {$letter->subject}");

        return redirect()->back()->with('success', "Surat eksternal {$letter->registration_no} berhasil dicatat.");
    }

    public function update(Request $request, HcmExternalLetter $externalLetter): RedirectResponse
    {
        Gate::authorize('hcm.manage-documents');

        $validated = $request->validate([
            'letter_date' => ['required', 'date'],
            'direction' => ['required', 'string', 'in:Surat Masuk,Surat Keluar'],
            'external_letter_no' => ['required', 'string', 'max:100'],
            'sender' => ['required', 'string', 'max:150'],
            'recipient' => ['required', 'string', 'max:150'],
            'subject' => ['required', 'string'],
            'file_upload' => ['nullable', 'file', 'mimes:pdf,doc,docx,xls,xlsx,png,jpg', 'max:10240'],
            'file_scan_url' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        if ($request->hasFile('file_upload')) {
            $uploaded = \App\Services\GoogleDriveSyncService::uploadFile(
                $request->file('file_upload'),
                \App\Services\GoogleDriveSyncService::FOLDER_LETTERS
            );
            $validated['file_scan_url'] = $uploaded['url'];
        }
        unset($validated['file_upload']);

        $externalLetter->update($validated);

        return redirect()->back()->with('success', "Surat eksternal {$externalLetter->registration_no} berhasil diperbarui.");
    }

    public function destroy(HcmExternalLetter $externalLetter): RedirectResponse
    {
        Gate::authorize('hcm.manage-documents');

        $reg = $externalLetter->registration_no;
        $externalLetter->delete();

        return redirect()->back()->with('success', "Surat eksternal {$reg} berhasil dihapus.");
    }
}
