<?php

// Base namespace shared by all top-level controllers in the app.
namespace App\Http\Controllers;

// Return type for actions that render a Blade template.
use Illuminate\View\View;

// Serves the site's static/public informational pages.
class PageController extends Controller
{
    /**
     * Display the home page.
     */
    // Renders the landing page.
    public function home(): View
    {
        // Return the "home" Blade view to the browser.
        return view('home');
    }

    /**
     * Display the about page.
     */
    // Renders the about page.
    public function about(): View
    {
        // Return the "about" Blade view.
        return view('about');
    }

    /**
     * Display the LET coverage page.
     */
    // Renders the page describing the exam coverage.
    public function coverage(): View
    {
        // Return the "coverage" Blade view.
        return view('coverage');
    }

    /**
     * Display the contact and feedback page.
     */
    // Renders the contact page that hosts the feedback form.
    public function contact(): View
    {
        // Return the "contact" Blade view.
        return view('contact');
    }
}
