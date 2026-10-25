<?php

// This is the base namespace where all the app's controllers live.
namespace App\Http\Controllers;

// Abstract base controller that every other controller in the app extends.
// Being abstract means it cannot be instantiated on its own.
abstract class Controller
{
    // Intentionally empty: shared controller behavior would be defined here.
    // Laravel's newer skeletons ship this as a bare base class on purpose.
    //
}
