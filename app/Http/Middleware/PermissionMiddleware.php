<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PermissionMiddleware
{
    public function handle(Request $request, Closure $next, string $menuKey): Response
    {
        $user = $request->user();

        abort_unless($user, 401);
        abort_unless($user->role_id, 403);
        abort_unless($user->hasMenuAccess($menuKey), 403);

        return $next($request);
    }
}
