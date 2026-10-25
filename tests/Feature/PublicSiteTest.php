<?php

// Feature tests live here; this class exercises the public, logged-out site.
namespace Tests\Feature;

// Eloquent model for a feedback message sent through the contact form.
use App\Models\Feedback;
// Eloquent model for an account (a student or an administrator).
use App\Models\User;
// The notification Laravel sends when a user asks for a password reset link.
use Illuminate\Auth\Notifications\ResetPassword;
// RefreshDatabase gives every test a fresh database (in-memory SQLite in tests).
use Illuminate\Foundation\Testing\RefreshDatabase;
// The Hash facade verifies a stored password hash against a plain-text value.
use Illuminate\Support\Facades\Hash;
// The Notification facade lets a test fake and inspect outgoing notifications.
use Illuminate\Support\Facades\Notification;
// Our shared base TestCase that boots the Laravel application for the test.
use Tests\TestCase;

// Covers the public marketing pages, the guest auth pages and the feedback form.
class PublicSiteTest extends TestCase
{
    // Wipes and re-migrates the database so tests never disturb development data.
    use RefreshDatabase;

    // Protects that the four public pages load and show their key wording.
    public function test_public_pages_are_reachable(): void
    {
        // Visits the home page; assertOk = HTTP 200, assertSee = body contains text.
        // assertSee escapes HTML by default, so raw markup is matched after encoding.
        $this->get('/')->assertOk()->assertSee('Get Started');
        // Visits the About page and checks its "Target users" section is rendered.
        $this->get('/about')->assertOk()->assertSee('Target users');
        // Visits the LET coverage page and checks it names the General Education area.
        $this->get('/let-coverage')->assertOk()->assertSee('General Education');
        // Visits the contact page and checks the form's submit button label.
        $this->get('/contact')->assertOk()->assertSee('Send a message');
    }

    // Checks the three guest auth screens render for a signed-out visitor.
    public function test_guest_auth_pages_are_reachable(): void
    {
        // The sign-in page should load for a visitor.
        $this->get('/login')->assertOk();
        // The sign-up page should load for a visitor.
        $this->get('/register')->assertOk();
        // The "forgot password" page should load for a visitor.
        $this->get('/forgot-password')->assertOk();
    }

    // Protects that the dashboard is private and bounces guests to the login page.
    public function test_dashboard_requires_authentication(): void
    {
        // A guest hitting /dashboard is redirected to /login instead of seeing it.
        $this->get('/dashboard')->assertRedirect('/login');
    }

    // Checks a visitor can submit the contact form and that the message is stored.
    public function test_visitor_can_submit_feedback(): void
    {
        // Posts the five contact-form fields exactly as the form would send them.
        $response = $this->post('/contact', [
            'name' => 'Maria Santos',
            'email' => 'maria@example.com',
            'category' => 'suggestion',
            'subject' => 'Add more math items',
            'message' => 'Please add more practice items on statistics and probability.',
        ]);

        // A successful submission redirects back to the contact page.
        $response->assertRedirect('/contact');
        // Asserts a matching feedback row now exists in the database.
        $this->assertDatabaseHas('feedbacks', [
            'email' => 'maria@example.com',
            'category' => 'suggestion',
        ]);
    }

    // Protects that the contact form rejects invalid input and stores nothing.
    public function test_feedback_submission_is_validated(): void
    {
        // Posts deliberately invalid values for every field.
        $response = $this->post('/contact', [
            'name' => '',
            'email' => 'not-an-email',
            'category' => 'unknown',
            'subject' => '',
            'message' => 'too short',
        ]);

        // Asserts each field produced its own validation error in the session.
        $response->assertSessionHasErrors(['name', 'email', 'category', 'subject', 'message']);
        // Asserts no feedback row was written because validation failed.
        $this->assertDatabaseCount('feedbacks', 0);
    }

    // Checks a visitor can register and that the new account is created and signed in.
    public function test_visitor_can_register_an_account(): void
    {
        // Posts a complete registration including the LET track and matching password.
        $response = $this->post('/register', [
            'name' => 'Juan Dela Cruz',
            'email' => 'juan@example.com',
            'exam_type' => 'secondary',
            'specialization' => 'English',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        // A successful registration redirects straight to the dashboard.
        $response->assertRedirect('/dashboard');
        // Asserts the session now holds an authenticated user.
        $this->assertAuthenticated();
        // Asserts the user row was saved with the chosen track and major.
        $this->assertDatabaseHas('users', [
            'email' => 'juan@example.com',
            'exam_type' => 'secondary',
            'specialization' => 'English',
        ]);
    }

    // Protects the rule that only a real LET track may be registered.
    public function test_registration_requires_a_valid_let_track(): void
    {
        // Posts an unknown exam type and a made-up specialization.
        $this->post('/register', [
            'name' => 'Juan Dela Cruz',
            'email' => 'juan@example.com',
            'exam_type' => 'unknown',
            'specialization' => 'Underwater basket weaving',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertSessionHasErrors(['exam_type', 'specialization']);

        // Asserts no account was created when the track was invalid.
        $this->assertDatabaseCount('users', 0);
    }

    // Checks that an email already in use cannot be registered again.
    public function test_registration_rejects_duplicate_email(): void
    {
        // Creates an existing account holding the email we will try to reuse.
        User::factory()->create(['email' => 'juan@example.com']);

        // Attempts to register a second account with that same email.
        $this->post('/register', [
            'name' => 'Juan Dela Cruz',
            'email' => 'juan@example.com',
            'exam_type' => 'secondary',
            'specialization' => 'English',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertSessionHasErrors('email');
    }

    // Checks a user can sign in with correct credentials and later sign out.
    public function test_user_can_sign_in_and_sign_out(): void
    {
        // Creates a user whose plain-text password is "password123".
        $user = User::factory()->create(['password' => 'password123']);

        // Posts the login form with the correct email and password.
        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password123',
        ])->assertRedirect('/dashboard');

        // Asserts the authenticated user is exactly the one we created.
        $this->assertAuthenticatedAs($user);

        // Signs out; a successful logout redirects back to the home page.
        $this->post('/logout')->assertRedirect('/');
        // Asserts the session is no longer authenticated.
        $this->assertGuest();
    }

    // Protects that a wrong password never signs the user in.
    public function test_sign_in_fails_with_wrong_password(): void
    {
        // Creates a valid user to attempt a login against.
        $user = User::factory()->create(['password' => 'password123']);

        // Posts the login form but with the wrong password.
        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');

        // Asserts the visitor is still a guest despite the attempt.
        $this->assertGuest();
    }

    // Checks a password reset link can be requested and is emailed out.
    public function test_password_reset_link_can_be_requested(): void
    {
        // Swaps the mailer for a fake so no real email leaves the test.
        Notification::fake();

        // Creates the user who will request the reset.
        $user = User::factory()->create();

        // Posts the forgot-password form with that user's email address.
        $this->post('/forgot-password', ['email' => $user->email])
            ->assertSessionHas('status');

        // Asserts a ResetPassword notification was sent to that user.
        Notification::assertSentTo($user, ResetPassword::class);
    }

    // Checks the password can actually be changed with a valid reset token.
    public function test_password_can_be_reset_with_a_valid_token(): void
    {
        // Creates the user whose password we will reset.
        $user = User::factory()->create();

        // Asks the password broker to generate a valid reset token for that user.
        $token = app('auth.password.broker')->createToken($user);

        // Posts the reset form with the token, email and matching new password pair.
        $this->post('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'new-password123',
            'password_confirmation' => 'new-password123',
        ])->assertRedirect('/login');

        // Asserts the stored hash now matches the new plain-text password.
        $this->assertTrue(Hash::check('new-password123', $user->fresh()->password));
    }
}
