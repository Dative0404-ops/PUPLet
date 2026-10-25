<?php

// Groups this controller with the other student-facing "Account" controllers.
namespace App\Http\Controllers\Account;

// The base controller, providing shared controller behaviour for the app.
use App\Http\Controllers\Controller;
// PerformanceAnalytics builds the progress figures the analytics page renders.
use App\Support\PerformanceAnalytics;
// Request is the incoming HTTP request, including access to the signed-in user.
use Illuminate\Http\Request;
// View is the return type of controller actions that render a Blade template.
use Illuminate\View\View;

// Handles the student's progress analytics page.
class AnalyticsController extends Controller
{
    /**
     * Display the progress analytics charts.
     */
    // Route action for GET /account/analytics.
    public function index(Request $request): View
    {
        // Render the "account.analytics" Blade view and pass it the data below.
        return view('account.analytics', [
            // The computed analytics figures for the signed-in student.
            'analytics' => array_merge(
                PerformanceAnalytics::forUser($request->user()),
                ['recent_attempts' => $request->user()->quizAttempts()->with('quiz.topic')->completed()->latest('completed_at')->take(5)->get()]
            ),
            // The signed-in user, so the view can greet them by name.
            'user' => $request->user(),
        ]);
    }
}
