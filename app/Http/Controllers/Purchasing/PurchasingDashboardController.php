<?php

namespace App\Http\Controllers\Purchasing;

use App\Http\Controllers\Controller;
use App\Services\Purchasing\PurchasingDashboardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class PurchasingDashboardController extends Controller
{
    public function __construct(
        protected PurchasingDashboardService $dashboardService
    ) {}

    /**
     * Display Purchasing Dashboard with 5-Tier Color Coding Alert Center
     */
    public function index(Request $request): Response
    {
        Gate::authorize('purchasing.view');

        $data = $this->dashboardService->getDashboardData();

        return Inertia::render('Purchasing/Dashboard/Index', [
            'metrics' => $data['metrics'],
            'alerts' => $data['alerts'],
            'upcomingPayments' => $data['upcoming_payments'],
        ]);
    }
}
