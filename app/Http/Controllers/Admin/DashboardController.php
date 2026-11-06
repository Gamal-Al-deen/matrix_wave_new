<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\DashboardMetricsService;
use App\Support\Currency;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Show the administrator landing page with aggregate indicators.
     */
    public function index(DashboardMetricsService $metrics): View
    {
        $currentCurrency = trim(Currency::code());

        if ($currentCurrency === '') {
            $currentCurrency = 'USD';
        }

        return view('admin.dashboard', [
            'metrics' => $metrics->metrics($currentCurrency),
            'currentCurrency' => $currentCurrency,
        ]);
    }
}
