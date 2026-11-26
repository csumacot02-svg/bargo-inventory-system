<?php

namespace App\Support;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;

class AuthAudit
{
    public static function log(
        ?User $actor,
        string $action,
        string $description,
        ?Request $request = null,
        ?User $target = null,
    ): void {
        ActivityLog::create([
            'user_id' => $actor?->id,
            'action' => $action,
            'module' => 'auth',
            'description' => $target
                ? $description.' [target: '.$target->email.']'
                : $description,
            'ip_address' => $request?->ip(),
        ]);
    }
}
