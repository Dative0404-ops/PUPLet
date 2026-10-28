<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

// Handles the public contact/feedback form.
class FeedbackController extends Controller
{
    /**
     * Save a message sent from the contact form.
     */
    public function store(Request $request): RedirectResponse
    {
        // Validate the form. On failure Laravel redirects back with the errors automatically.
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:150'],
            // Category must be one of the keys defined on the model.
            'category' => ['required', Rule::in(array_keys(Feedback::CATEGORIES))],
            'subject' => ['required', 'string', 'max:150'],
            'message' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        // Save the validated fields as a new feedback row.
        Feedback::create($validated);

        // Go back to the contact page with a one-time success message.
        return redirect()
            ->route('contact')
            ->with('status', 'Thank you! Your message has been received. We will review it and reply through your email address.');
    }
}
