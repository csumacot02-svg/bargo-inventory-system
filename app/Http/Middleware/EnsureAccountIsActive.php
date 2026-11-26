<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountIsActive
{
    /**
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('gateway');
        }

        if ($user->status === 'pending') {
            return redirect()->route('account.pending');
        }

        if (in_array($user->status, ['rejected', 'suspended'], true)) {
            return redirect()->route('account.blocked');
        }

        if (! $user->isActive()) {
            abort(403, 'Your account is not active.');
        }

        return $next($request);
    }
}
