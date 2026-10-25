<?php

// Namespace for HTTP middleware classes in the app.
namespace App\Http\Middleware;

// The type of the "$next" callable that continues the request pipeline.
use Closure;
// Wraps the incoming HTTP request.
use Illuminate\Http\Request;
// Return type for middleware responses (Laravel/Symfony shared type).
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps the admin panel to accounts that hold the administrator role.
 */
// Middleware guarded by the "admin" alias in the route definitions.
class EnsureUserIsAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    // Runs before the protected admin routes; blocks anyone who is not an admin.
    public function handle(Request $request, Closure $next): Response
    {
        // The currently authenticated user, or null when signed out.
        $user = $request->user();

        // Pretend the route does not exist (404) unless a user is signed in AND is an admin.
        // Using 404 instead of 403 hides the admin panel's existence from outsiders.
        abort_unless($user !== null && $user->isAdmin(), 404);
        // If the account is suspended, stop with a 403 and a clear message.
        abort_if($user->isSuspended(), 403, 'This account has been suspended.');

        // All checks passed: hand the request to the next middleware/route handler.
        return $next($request);
    }
}
