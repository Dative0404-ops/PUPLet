<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Support\PerformanceAnalytics;
use Illuminate\Http\Request;
use Illuminate\View\View;

// Shows the student's progress analytics page.
class AnalyticsController extends Controller
{
    /**
     * Show the progress analytics charts.
     */
    public function index(Request $request): View
    {
        // Progress figures plus the five most recent finished attempts.
        return view('account.analytics', [
            'analytics' => array_merge(
                PerformanceAnalytics::forUser($request->user()),
                ['recent_attempts' => $request->user()->quizAttempts()->with('quiz.topic')->completed()->latest('completed_at')->take(5)->get()]
            ),
            'user' => $request->user(),
        ]);
    }
}
