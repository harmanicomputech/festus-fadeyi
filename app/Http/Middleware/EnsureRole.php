<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();
        if (! $user || ! in_array($user->role, $roles, true)) {
            if ($user && $user->role === 'checkin') {
                return redirect()->route('admin.checkin');
            }
            abort(403);
        }

        return $next($request);
    }
}
