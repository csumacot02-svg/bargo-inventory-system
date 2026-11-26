<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureFirstAdminSetupAvailable
{
    /**
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (User::activeAdminExists()) {
            return redirect()
                ->route('gateway')
                ->with('error', 'Administrator setup is closed. An active administrator already exists.');
        }

        return $next($request);
    }
}
