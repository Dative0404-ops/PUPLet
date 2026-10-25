<?php

// Feature tests live here; they exercise the app through HTTP requests.
namespace Tests\Feature;

// RefreshDatabase gives each test a fresh database wrapped in a transaction.
use Illuminate\Foundation\Testing\RefreshDatabase;
// Imports our shared base TestCase that boots the Laravel application.
use Tests\TestCase;

// A minimal feature test proving the app boots and serves the home page.
class ExampleTest extends TestCase
{
    // Enables the per-test database refresh described above.
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    // :void means the test asserts internally and returns nothing.
    public function test_the_application_returns_a_successful_response(): void
    {
        // Simulates a browser GET request to the site root, capturing the response.
        $response = $this->get('/');

        // Asserts the response carries HTTP status 200 (OK).
        $response->assertStatus(200);
    }
}
