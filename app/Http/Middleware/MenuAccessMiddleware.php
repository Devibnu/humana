<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MenuAccessMiddleware
{
    public function handle(Request $request, Closure $next, string ...$menuKeys): Response
    {
        $user = $request->user();

        abort_unless($user, 401);
        abort_unless($user->role_id, 403);

        $hasAccess = collect($menuKeys)->contains(fn ($menuKey) => $user->hasMenuAccess($menuKey));

        abort_unless($hasAccess, 403);

        return $next($request);
    }
}