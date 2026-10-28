<?php

// Inspiring returns a random motivational quote.
use Illuminate\Foundation\Inspiring;
// Artisan is how we register custom command-line (CLI) commands.
use Illuminate\Support\Facades\Artisan;

// Registers the "inspire" command. The closure runs when "php artisan inspire" is typed.
Artisan::command('inspire', function () {
    // Print a random quote to the console.
    $this->comment(Inspiring::quote());
    // purpose() sets the description shown in "php artisan list".
})->purpose('Display an inspiring quote');
