<?php

// Unit tests live in this namespace; no Laravel application is booted here.
namespace Tests\Unit;

// Uses PHPUnit's plain TestCase, so this test touches no database or HTTP layer.
use PHPUnit\Framework\TestCase;

// A minimal unit test class that proves PHPUnit itself is wired up correctly.
class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    // PHPUnit runs every public method whose name starts with "test".
    public function test_that_true_is_true(): void
    {
        // :void means the test asserts internally and returns nothing.
        // Asserts that true is true; it passes whenever PHPUnit runs at all.
        $this->assertTrue(true);
    }
}
