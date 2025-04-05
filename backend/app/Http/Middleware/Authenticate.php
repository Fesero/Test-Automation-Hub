<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    protected function redirectTo(Request $request): ?string
    {
        // If the request expects JSON (typical for APIs), return null.
        // This prevents redirection and allows the unauthenticated handler to return a 401 JSON response.
        // Otherwise, for web requests, redirect to the login route (if defined).
        return $request->expectsJson() ? null : route('login');
    }
} 