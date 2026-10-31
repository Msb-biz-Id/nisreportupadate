<?php

namespace App\Http\Controllers\Hcm;

use App\Http\Controllers\Controller;
use App\Models\Hcm\HcmCompanyEvent;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class HcmEventController extends Controller
{
    /**
     * Tampilkan Modul Kalender & Agenda Kegiatan Sosial Perusahaan.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('hcm.manage-events');

        $typeFilter = $request->query('type', 'all');
        $monthFilter = $request->query('month', date('n'));
        $yearFilter = $request->query('year', date('Y'));
        $search = $request->query('search', '');

        $escapedSearch = str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $search);

        $eventsQuery = HcmCompanyEvent::with('creator:id,name')
            ->when($escapedSearch, fn ($q, $t) => $q->where(fn ($sub) =>
                $sub->where('title', 'like', "%{$t}%")
                    ->orWhere('location', 'like', "%{$t}%")
                    ->orWhere('description', 'like', "%{$t}%")
            ))
            ->when($typeFilter !== 'all', fn ($q) => $q->where('event_type', $typeFilter))
            ->when($yearFilter !== 'all', fn ($q) => $q->whereYear('start_date', (int) $yearFilter))
            ->when($monthFilter !== 'all', fn ($q) => $q->whereMonth('start_date', (int) $monthFilter))
            ->orderBy('start_date', 'asc');

        $events = $eventsQuery->get();

        // Metrik Ringkasan Agenda Acara
        $now = Carbon::now();
        $metrics = [
            'total_events' => HcmCompanyEvent::count(),
            'upcoming_events' => HcmCompanyEvent::whereDate('start_date', '>=', $now->toDateString())->count(),
            'completed_events' => HcmCompanyEvent::whereDate('end_date', '<', $now->toDateString())->count(),
            'national_holidays' => HcmCompanyEvent::where('event_type', 'Libur Nasional')->whereYear('start_date', $now->year)->count(),
        ];

        $eventTypes = [
            'Libur Nasional',
            'Acara Perusahaan',
            'Meeting Internal',
            'Pelatihan / Workshop',
            'Kegiatan Sosial',
            'Lainnya',
        ];

        return Inertia::render('Hcm/Events/Index', [
            'events' => $events,
            'filters' => [
                'type' => $typeFilter,
                'month' => (string) $monthFilter,
                'year' => (string) $yearFilter,
                'search' => $search,
            ],
            'metrics' => $metrics,
            'eventTypes' => $eventTypes,
            'today' => $now->toDateString(),
        ]);
    }

    /**
     * Tambah acara kegiatan baru.
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('hcm.manage-events');

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'event_type' => ['required', 'string', 'max:50'],
            'organizer_name' => ['nullable', 'string', 'max:100'],
            'target_audience' => ['nullable', 'string', 'max:100'],
            'reminder_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'is_annual_recurring' => ['boolean'],
            'invitation_file_url' => ['nullable', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'start_time' => ['nullable', 'string', 'max:10'],
            'end_time' => ['nullable', 'string', 'max:10'],
            'location' => ['nullable', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
            'color_code' => ['nullable', 'string', 'max:25'],
            'is_public' => ['boolean'],
        ]);

        $validated['created_by'] = Auth::id();
        $validated['color_code'] = $validated['color_code'] ?? '#3b82f6';
        $validated['is_public'] = $validated['is_public'] ?? true;

        HcmCompanyEvent::create($validated);

        return redirect()->back()->with('success', "Kegiatan '{$validated['title']}' berhasil ditambahkan ke kalender agenda perusahaan.");
    }

    /**
     * Perbarui data acara kegiatan.
     */
    public function update(Request $request, HcmCompanyEvent $event): RedirectResponse
    {
        Gate::authorize('hcm.manage-events');

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'event_type' => ['required', 'string', 'max:50'],
            'organizer_name' => ['nullable', 'string', 'max:100'],
            'target_audience' => ['nullable', 'string', 'max:100'],
            'reminder_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'is_annual_recurring' => ['boolean'],
            'invitation_file_url' => ['nullable', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'start_time' => ['nullable', 'string', 'max:10'],
            'end_time' => ['nullable', 'string', 'max:10'],
            'location' => ['nullable', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
            'color_code' => ['nullable', 'string', 'max:25'],
            'is_public' => ['boolean'],
        ]);

        $event->update($validated);

        return redirect()->back()->with('success', "Kegiatan '{$event->title}' berhasil diperbarui.");
    }

    /**
     * Hapus acara kegiatan.
     */
    public function destroy(HcmCompanyEvent $event): RedirectResponse
    {
        Gate::authorize('hcm.manage-events');

        $title = $event->title;
        $event->delete();

        return redirect()->back()->with('success', "Kegiatan '{$title}' berhasil dihapus.");
    }
}
