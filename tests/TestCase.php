<?php

// Tests live under the Tests namespace, matching this file's location.
namespace Tests;

// Extends Laravel's own TestCase, which boots the application for every test.
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

// Abstract base class that all Feature tests extend; it is never run on its own.
abstract class TestCase extends BaseTestCase
{
    // Empty on purpose: the parent class already wires up the Laravel application.
}
