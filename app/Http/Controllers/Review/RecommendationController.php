<?php

namespace App\Http\Controllers\Review;

// Parent controller that every controller extends.
use App\Http\Controllers\Controller;
// Helper that builds the ranked recommendation list.
use App\Support\DssRecommender;
// Helper exposing the tunable DSS rule settings.
use App\Support\DssSettings;
// The incoming HTTP request.
use Illuminate\Http\Request;
// Return type for actions that render a Blade template.
use Illuminate\View\View;

/**
 * The decision support recommendations: what a student should review next and
 * the reason behind each suggestion.
 */
class RecommendationController extends Controller
{
    /**
     * Rank the topics the student should work on.
     */
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        return view('review.recommendations', [
            // The ranked recommendation list built by the DSS recommender.
            'recommendations' => DssRecommender::forUser($user),
            // The current tunable rule values, shown for transparency.
            'rules' => DssSettings::all(),
            // A label describing the student's LET track.
            'trackLabel' => $user->trackLabel(),
        ]);
    }
}
