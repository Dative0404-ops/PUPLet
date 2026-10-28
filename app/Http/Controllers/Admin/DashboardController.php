<?php

namespace App\Http\Controllers\Admin;

// Base class with shared controller helpers.
use App\Http\Controllers\Controller;
// Builds the summary figures shown on the dashboard.
use App\Support\AdminOverview;
// Returned when an action renders a Blade template.
use Illuminate\View\View;

/**
 * The admin landing dashboard.
 */
class DashboardController extends Controller
{
    /**
     * Show users, question counts, and platform activity.
     */
    public function index(): View
    {
        return view('admin.dashboard', [
            // The summary figures built by the AdminOverview helper.
            'overview' => AdminOverview::collect(),
        ]);
    }
}
