<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps the admin panel to accounts that hold the administrator role.
 */
class EnsureUserIsAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // The authenticated user, or null when signed out.
        $user = $request->user();

        // Pretend the route does not exist (404) unless a signed-in admin.
        // Using 404 instead of 403 hides the admin panel from outsiders.
        abort_unless($user !== null && $user->isAdmin(), 404);
        // Stop with a 403 if the account is suspended.
        abort_if($user->isSuspended(), 403, 'This account has been suspended.');

        // All checks passed: hand the request to the next handler.
        return $next($request);
    }
}
