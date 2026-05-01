<?php

if (! function_exists('hasMenuAccess')) {
    /**
     * Check whether the currently authenticated user has access to the given menu key.
     */
    function hasMenuAccess(string $key): bool
    {
        $user = auth()->user();

        return $user && $user->hasMenuAccess($key);
    }
}
