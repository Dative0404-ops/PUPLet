<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

// Serves the site's static informational pages.
class PageController extends Controller
{
    /**
     * Show the home page.
     */
    public function home(): View
    {
        return view('home');
    }

    /**
     * Show the about page.
     */
    public function about(): View
    {
        return view('about');
    }

    /**
     * Show the LET coverage page.
     */
    public function coverage(): View
    {
        return view('coverage');
    }

    /**
     * Show the contact and feedback page.
     */
    public function contact(): View
    {
        return view('contact');
    }
}
