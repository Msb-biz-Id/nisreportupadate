<?php

namespace App\Http\Controllers\Hcm;

use App\Http\Controllers\Controller;
use App\Models\Hcm\HcmAttendance;
use App\Models\Hcm\HcmEmployee;
use App\Models\Hcm\HcmLeaveRequest;
use App\Models\Hcm\HcmMasterOption;
use App\Services\ActivityLogger;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class HcmLeaveRequestController extends Controller
{
    /**
     * Tampilkan daftar tiket permohonan Cuti / Izin / Sakit.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('hcm.manage-attendance');

        $statusFilter = $request->query('status', 'all');
        $typeFilter = $request->query('leave_type', 'all');
        $search = $request->query('search', '');

        $escapedSearch = str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $search);

        $query = HcmLeaveRequest::with([
            'employee:id,employee_code,name,nickname,department,division',
            'reviewer:id,name',
            'creator:id,name',
        ])
            ->when($escapedSearch, function ($q, $term) {
                $q->whereHas('employee', function ($eq) use ($term) {
                    $eq->where('name', 'like', "%{$term}%")
                       ->orWhere('employee_code', 'like', "%{$term}%");
                })->orWhere('reason', 'like', "%{$term}%");
            })
            ->when($statusFilter !== 'all', fn ($q) => $q->where('status', $statusFilter))
            ->when($typeFilter !== 'all', fn ($q) => $q->where('leave_type', $typeFilter))
            ->orderBy('id', 'desc');

        $leaveRequests = $query->paginate(15)->withQueryString();

        // Metrics statistik tiket
        $currentMonth = date('m');
        $currentYear = date('Y');

        $metrics = [
            'total_pending' => HcmLeaveRequest::where('status', 'PENDING_REVIEW')->count(),
            'approved_this_month' => HcmLeaveRequest::where('status', 'APPROVED')
                ->whereMonth('start_date', $currentMonth)
                ->whereYear('start_date', $currentYear)
                ->count(),
            'rejected_this_month' => HcmLeaveRequest::where('status', 'REJECTED')
                ->whereMonth('start_date', $currentMonth)
                ->whereYear('start_date', $currentYear)
                ->count(),
        ];

        // Daftar karyawan aktif untuk dropdown form pengajuan
        $employees = HcmEmployee::where('is_active', true)
            ->select('id', 'employee_code', 'name', 'department', 'division')
            ->orderBy('name')
            ->get();

        return Inertia::render('Hcm/Attendance/Leaves', [
            'leaveRequests' => $leaveRequests,
            'filters' => [
                'status' => $statusFilter,
                'leave_type' => $typeFilter,
                'search' => $search,
            ],
            'metrics' => $metrics,
            'employees' => $employees,
            'leaveTypes' => HcmMasterOption::getOptions('kategori_kehadiran') ? array_values(array_filter(
                HcmMasterOption::getOptions('kategori_kehadiran'),
                fn ($t) => !in_array($t, ['Hadir', 'Terlambat', 'Pulang Cepat', 'Alpha/Mangkir'])
            )) : [
                'Cuti Tahunan',
                'Izin',
                'Sakit',
                'Dinas Luar',
                'Cuti Bersama',
            ],
        ]);
    }

    /**
     * Ajukan tiket permohonan Cuti / Izin / Sakit baru.
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('hcm.manage-attendance');

        $validated = $request->validate([
            'employee_id' => ['required', 'exists:hcm_employees,id'],
            'leave_type' => ['required', 'string', 'in:Cuti Tahunan,Izin,Sakit,Dinas Luar,Cuti Bersama'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['required', 'string', 'max:500'],
            'attachment_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'], // Max 5MB
            'attachment_url' => ['nullable', 'string', 'max:255'],
        ]);

        $attachmentUrl = $validated['attachment_url'] ?? null;
        if ($request->hasFile('attachment_file')) {
            $uploaded = \App\Services\GoogleDriveSyncService::uploadFile(
                $request->file('attachment_file'),
                \App\Services\GoogleDriveSyncService::FOLDER_ATTENDANCE
            );
            $attachmentUrl = $uploaded['url'];
        }

        $startDate = Carbon::parse($validated['start_date']);
        $endDate = Carbon::parse($validated['end_date']);
        $totalDays = $startDate->diffInDays($endDate) + 1;

        $leaveRequest = HcmLeaveRequest::create([
            'employee_id' => $validated['employee_id'],
            'leave_type' => $validated['leave_type'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'total_days' => $totalDays,
            'reason' => $validated['reason'],
            'attachment_url' => $attachmentUrl,
            'status' => 'PENDING_REVIEW',
            'created_by' => Auth::id(),
        ]);

        $employee = HcmEmployee::find($validated['employee_id']);
        ActivityLogger::log('create', 'hcm', $leaveRequest, "Pengajuan {$leaveRequest->leave_type} untuk {$employee?->name} ({$totalDays} hari)");

        return redirect()->back()->with('success', 'Permohonan Cuti/Izin berhasil diajukan dan menunggu persetujuan HCM.');
    }

    /**
     * Setujui tiket Cuti / Izin dan sinkronkan otomatis ke tabel Presensi Harian.
     */
    public function approve(HcmLeaveRequest $leaveRequest): RedirectResponse
    {
        Gate::authorize('hcm.manage-attendance');

        if ($leaveRequest->status === 'APPROVED') {
            return redirect()->back()->with('error', 'Tiket ini sudah disetujui sebelumnya.');
        }

        DB::transaction(function () use ($leaveRequest) {
            $leaveRequest->update([
                'status' => 'APPROVED',
                'reviewed_by' => Auth::id(),
                'reviewed_at' => now(),
                'rejection_reason' => null,
            ]);

            // Sinkronisasi otomatis ke tabel hcm_attendances untuk rentang tanggal izin
            $period = CarbonPeriod::create($leaveRequest->start_date, $leaveRequest->end_date);
            $employee = $leaveRequest->employee;

            // Map jenis leave_type ke attendance_category
            $categoryMap = [
                'Cuti Tahunan' => 'Cuti',
                'Cuti Bersama' => 'Libur/Cuti Bersama',
                'Izin' => 'Izin',
                'Sakit' => 'Sakit',
                'Dinas Luar' => 'Dinas Luar',
            ];

            $attendanceCategory = $categoryMap[$leaveRequest->leave_type] ?? 'Izin';

            foreach ($period as $date) {
                $dateStr = $date->format('Y-m-d');

                HcmAttendance::updateOrCreate(
                    [
                        'attendance_date' => $dateStr,
                        'employee_id' => $leaveRequest->employee_id,
                    ],
                    [
                        'position' => $employee?->division ?: $employee?->position,
                        'attendance_category' => $attendanceCategory,
                        'clock_in' => null,
                        'clock_out' => null,
                        'notes' => "Disetujui: {$leaveRequest->reason}",
                        'attachment_status' => $leaveRequest->attachment_url ? 'Terlampir' : 'Tidak Terlampir',
                        'attachment_url' => $leaveRequest->attachment_url,
                        'created_by' => Auth::id(),
                    ]
                );
            }
        });

        ActivityLogger::log('approve', 'hcm', $leaveRequest, "Menyetujui {$leaveRequest->leave_type} {$leaveRequest->employee?->name} ({$leaveRequest->start_date} s/d {$leaveRequest->end_date})");

        return redirect()->back()->with('success', "Permohonan {$leaveRequest->leave_type} berhasil disetujui dan disinkronkan ke presensi harian.");
    }

    /**
     * Tolak tiket Cuti / Izin (Wajib mengisi alasan penolakan).
     */
    public function reject(Request $request, HcmLeaveRequest $leaveRequest): RedirectResponse
    {
        Gate::authorize('hcm.manage-attendance');

        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:500'],
        ]);

        $leaveRequest->update([
            'status' => 'REJECTED',
            'rejection_reason' => $validated['rejection_reason'],
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ]);

        ActivityLogger::log('reject', 'hcm', $leaveRequest, "Menolak {$leaveRequest->leave_type} {$leaveRequest->employee?->name}. Alasan: {$validated['rejection_reason']}");

        return redirect()->back()->with('success', 'Permohonan Cuti/Izin telah ditolak.');
    }

    /**
     * Hapus tiket pengajuan cuti (Hanya jika masih PENDING_REVIEW).
     */
    public function destroy(HcmLeaveRequest $leaveRequest): RedirectResponse
    {
        Gate::authorize('hcm.manage-attendance');

        if ($leaveRequest->status === 'APPROVED') {
            return redirect()->back()->with('error', 'Tiket yang telah disetujui tidak dapat dihapus.');
        }

        $leaveRequest->delete();

        return redirect()->back()->with('success', 'Tiket permohonan berhasil dihapus.');
    }
}
