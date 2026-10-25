<?php

// Groups this controller with the other admin-panel controllers.
namespace App\Http\Controllers\Admin;

// The base controller, providing shared controller behaviour.
use App\Http\Controllers\Controller;
// AdminOverview assembles the summary figures shown on the dashboard.
use App\Support\AdminOverview;
// View is the return type of actions that render a Blade template.
use Illuminate\View\View;

/**
 * The overview every administrator lands on.
 */
// The landing page of the admin panel.
class DashboardController extends Controller
{
    /**
     * Show users, question counts, and platform activity.
     */
    // Route action for GET /admin (the admin dashboard).
    public function index(): View
    {
        // Render the dashboard view with the collected statistics.
        return view('admin.dashboard', [
            // The summary metrics built by the AdminOverview support class.
            'overview' => AdminOverview::collect(),
        ]);
    }
}
