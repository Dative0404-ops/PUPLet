<?php

// This controller lives in the Review sub-namespace of the app.
namespace App\Http\Controllers\Review;

// Parent controller that every controller extends.
use App\Http\Controllers\Controller;
// Support class that builds the ranked recommendation list.
use App\Support\DssRecommender;
// Support class exposing the tunable DSS rule settings.
use App\Support\DssSettings;
// Wraps the incoming HTTP request.
use Illuminate\Http\Request;
// Return type for actions that render a Blade template.
use Illuminate\View\View;

/**
 * The decision support recommendations: what a student should review next and
 * the reason behind each suggestion.
 */
// Controller for the decision-support recommendation page.
class RecommendationController extends Controller
{
    /**
     * Rank the topics the student should work on.
     */
    // Invokable action: renders the recommendations for the current student.
    public function __invoke(Request $request): View
    {
        // The signed-in student.
        $user = $request->user();

        // Render the recommendations page.
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
