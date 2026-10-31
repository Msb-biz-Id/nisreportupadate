<?php

namespace App\Http\Controllers\Hcm;

use App\Http\Controllers\Controller;
use App\Models\Hcm\HcmOfficeExitPermit;
use App\Services\ActivityLogger;
use App\Services\GoogleDriveSyncService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class HcmOfficeExitPermitController extends Controller
{
    /**
     * Catat izin keluar kantor (Gate Pass) baru.
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('hcm.manage-attendance');

        $validated = $request->validate([
            'employee_id' => ['required', 'exists:hcm_employees,id'],
            'permit_date' => ['required', 'date'],
            'exit_time' => ['required', 'string', 'max:10'],
            'return_time' => ['nullable', 'string', 'max:10'],
            'purpose' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'attachment_status' => ['required', 'string', 'max:50'],
            'attachment_url' => ['nullable', 'string', 'max:500'],
            'file_attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'status' => ['nullable', 'string', 'max:50'],
        ]);

        $fileUrl = $validated['attachment_url'] ?? null;
        if ($request->hasFile('file_attachment')) {
            try {
                $uploaded = GoogleDriveSyncService::uploadFile(
                    $request->file('file_attachment'),
                    GoogleDriveSyncService::FOLDER_DOCUMENTS ?? 'hcm/gatepass'
                );
                $fileUrl = $uploaded['url'];
            } catch (\Throwable $e) {
                $path = $request->file('file_attachment')->store('hcm/gatepass', 'public');
                $fileUrl = Storage::url($path);
            }
            $validated['attachment_status'] = 'Terlampir';
        }

        $status = !empty($validated['return_time'])
            ? 'Kembali'
            : ($validated['status'] ?? 'Masih di Luar');

        $permit = HcmOfficeExitPermit::create([
            'employee_id' => $validated['employee_id'],
            'permit_date' => $validated['permit_date'],
            'exit_time' => $validated['exit_time'],
            'return_time' => $validated['return_time'] ?? null,
            'purpose' => $validated['purpose'],
            'notes' => $validated['notes'] ?? null,
            'attachment_status' => $validated['attachment_status'],
            'attachment_url' => $fileUrl,
            'status' => $status,
            'created_by' => Auth::id(),
        ]);

        ActivityLogger::log(
            'hcm.exit_permit.created',
            "Menerbitkan Izin Keluar Kantor (Gate Pass) untuk {$permit->employee?->name} ({$permit->purpose})"
        );

        return back()->with('success', 'Izin keluar kantor (Gate Pass) berhasil dicatat.');
    }

    /**
     * Perbarui data izin keluar kantor.
     */
    public function update(Request $request, HcmOfficeExitPermit $exitPermit): RedirectResponse
    {
        Gate::authorize('hcm.manage-attendance');

        $validated = $request->validate([
            'permit_date' => ['required', 'date'],
            'exit_time' => ['required', 'string', 'max:10'],
            'return_time' => ['nullable', 'string', 'max:10'],
            'purpose' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'attachment_status' => ['required', 'string', 'max:50'],
            'attachment_url' => ['nullable', 'string', 'max:500'],
            'file_attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'status' => ['required', 'string', 'max:50'],
        ]);

        $fileUrl = $exitPermit->attachment_url;
        if ($request->hasFile('file_attachment')) {
            try {
                $uploaded = GoogleDriveSyncService::uploadFile(
                    $request->file('file_attachment'),
                    GoogleDriveSyncService::FOLDER_DOCUMENTS ?? 'hcm/gatepass'
                );
                $fileUrl = $uploaded['url'];
            } catch (\Throwable $e) {
                $path = $request->file('file_attachment')->store('hcm/gatepass', 'public');
                $fileUrl = Storage::url($path);
            }
            $validated['attachment_status'] = 'Terlampir';
        }

        $status = $validated['status'];
        if (!empty($validated['return_time']) && $status === 'Masih di Luar') {
            $status = 'Kembali';
        }

        $exitPermit->update([
            'permit_date' => $validated['permit_date'],
            'exit_time' => $validated['exit_time'],
            'return_time' => $validated['return_time'] ?? null,
            'purpose' => $validated['purpose'],
            'notes' => $validated['notes'] ?? null,
            'attachment_status' => $validated['attachment_status'],
            'attachment_url' => $fileUrl,
            'status' => $status,
        ]);

        ActivityLogger::log(
            'hcm.exit_permit.updated',
            "Memperbarui Izin Keluar Kantor (Gate Pass) #{$exitPermit->id} ({$exitPermit->employee?->name})"
        );

        return back()->with('success', 'Data izin keluar kantor berhasil diperbarui.');
    }

    /**
     * Aksi cepat: Tandai karyawan telah kembali ke kantor / pabrik.
     */
    public function markReturned(Request $request, HcmOfficeExitPermit $exitPermit): RedirectResponse
    {
        Gate::authorize('hcm.manage-attendance');

        $returnTime = $request->input('return_time') ?: Carbon::now()->format('H:i');

        $exitPermit->update([
            'return_time' => $returnTime,
            'status' => 'Kembali',
        ]);

        ActivityLogger::log(
            'hcm.exit_permit.returned',
            "Karyawan {$exitPermit->employee?->name} tercatat telah kembali ke kantor pukul {$returnTime}"
        );

        return back()->with('success', "Karyawan {$exitPermit->employee?->name} berhasil ditandai telah kembali.");
    }

    /**
     * Hapus rekaman izin keluar kantor.
     */
    public function destroy(HcmOfficeExitPermit $exitPermit): RedirectResponse
    {
        Gate::authorize('hcm.manage-attendance');

        $empName = $exitPermit->employee?->name;
        $exitPermit->delete();

        ActivityLogger::log(
            'hcm.exit_permit.deleted',
            "Menghapus rekaman Izin Keluar Kantor untuk {$empName}"
        );

        return back()->with('success', 'Data izin keluar kantor berhasil dihapus.');
    }
}
