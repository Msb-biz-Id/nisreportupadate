<?php

namespace App\Http\Controllers\Hcm;

use App\Http\Controllers\Controller;
use App\Models\Hcm\HcmCompensation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class HcmCompensationController extends Controller
{
    /**
     * Modul Kompensasi & Gaji: rekap gaji/honor per karyawan.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('hcm.manage-compensation');

        $search = $request->query('search', '');
        $statusFilter = $request->query('status', 'all');
        $entityFilter = $request->query('entity', 'all');

        $escapedSearch = str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $search);

        $compensations = HcmCompensation::with(['employee:id,employee_code,name,nickname,department,position,is_active'])
            ->when($escapedSearch, function ($q, $term) {
                $q->where(function ($sub) use ($term) {
                    $sub->where('contract_number', 'like', "%{$term}%")
                        ->orWhereHas('employee', function ($eq) use ($term) {
                            $eq->where('name', 'like', "%{$term}%")
                               ->orWhere('employee_code', 'like', "%{$term}%");
                        });
                });
            })
            ->when($statusFilter !== 'all', fn ($q) => $q->where('salary_status', $statusFilter))
            ->when($entityFilter !== 'all', fn ($q) => $q->where('legal_entity', $entityFilter))
            ->orderBy('id', 'desc')
            ->paginate(12)
            ->withQueryString();

        $all = HcmCompensation::query();

        $metrics = [
            'total_employees' => (clone $all)->count(),
            'total_initial' => (float) (clone $all)->sum('initial_salary'),
            'total_current' => (float) (clone $all)->sum('current_salary'),
            'total_increment' => (float) ((clone $all)->sum('current_salary') - (clone $all)->sum('initial_salary')),
            'avg_current' => (float) round((clone $all)->avg('current_salary') ?? 0, 2),
        ];

        $entities = HcmCompensation::whereNotNull('legal_entity')->distinct()->orderBy('legal_entity')->pluck('legal_entity');
        $statuses = HcmCompensation::whereNotNull('salary_status')->distinct()->orderBy('salary_status')->pluck('salary_status');

        return Inertia::render('Hcm/Compensations/Index', [
            'compensations' => $compensations,
            'filters' => [
                'search' => $search,
                'status' => $statusFilter,
                'entity' => $entityFilter,
            ],
            'metrics' => $metrics,
            'entities' => $entities,
            'statuses' => $statuses,
        ]);
    }
}
