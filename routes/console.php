<?php

// Inspiring is a Laravel helper class that returns a random motivational quote.
use Illuminate\Foundation\Inspiring;
// The Artisan facade is how we register custom command-line (CLI) commands.
use Illuminate\Support\Facades\Artisan;

// Registers a custom Artisan command called "inspire".
// The closure is the command's handler: it runs when "php artisan inspire" is typed.
Artisan::command('inspire', function () {
    // $this refers to the command instance; comment() prints a styled line to the console.
    // Inspiring::quote() provides the random quote that is printed.
    $this->comment(Inspiring::quote());
    // purpose() sets the one-line description shown in the "php artisan list" output.
})->purpose('Display an inspiring quote');
