<?php

// Base namespace shared by all top-level controllers in the app.
namespace App\Http\Controllers;

// Eloquent model representing a row in the feedback table.
use App\Models\Feedback;
// Return type for actions that redirect the browser to another URL.
use Illuminate\Http\RedirectResponse;
// Wraps the incoming HTTP request (form input, session, etc.).
use Illuminate\Http\Request;
// Provides the Rule::in() validation helper used below.
use Illuminate\Validation\Rule;

// Handles the public contact/feedback form submissions.
class FeedbackController extends Controller
{
    /**
     * Store a message submitted through the contact form.
     */
    // Receives the contact form POST and saves a feedback record.
    public function store(Request $request): RedirectResponse
    {
        // Validate the form input; on failure Laravel redirects back automatically
        // with the errors flashed into the session, so no manual error handling is needed.
        $validated = $request->validate([
            // Sender's name is mandatory, must be text, capped at 100 characters.
            'name' => ['required', 'string', 'max:100'],
            // Sender's email is mandatory, must be lowercase, a valid email, max 150 chars.
            'email' => ['required', 'string', 'lowercase', 'email', 'max:150'],
            // Category is mandatory and must be one of the keys defined on the model.
            'category' => ['required', Rule::in(array_keys(Feedback::CATEGORIES))],
            // Subject line is mandatory, text, capped at 150 characters.
            'subject' => ['required', 'string', 'max:150'],
            // Message body is mandatory, text, at least 10 and at most 2000 characters.
            'message' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        // Persist the validated fields as a new feedback row.
        Feedback::create($validated);

        // Send the visitor back to the contact page with a success flash message.
        return redirect()
            // Name the target route rather than a hard-coded URL.
            ->route('contact')
            // Flash a status string that the view can display once.
            ->with('status', 'Thank you! Your message has been received. We will review it and reply through your email address.');
    }
}
