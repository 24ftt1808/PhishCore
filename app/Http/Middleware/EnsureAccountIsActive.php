<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Signs out anyone whose account was suspended while they were already logged in. The login form blocks a
 * suspended person from signing in; this closes the other gap, so an admin's "Suspend" takes effect on the
 * very next request instead of whenever the session happens to end.
 */
class EnsureAccountIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User && $user->isSuspended()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                return response()->json(['message' => User::SUSPENDED_MESSAGE], 403);
            }

            return redirect()->route('login')->withErrors(['email' => User::SUSPENDED_MESSAGE]);
        }

        return $next($request);
    }
}
