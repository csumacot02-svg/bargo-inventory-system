<?php

namespace App\Support;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;

class Audit
{
    public static function log(
        ?User $actor,
        string $action,
        string $module,
        string $description,
        ?Request $request = null,
    ): void {
        ActivityLog::create([
            'user_id' => $actor?->id,
            'action' => $action,
            'module' => $module,
            'description' => $description,
            'ip_address' => $request?->ip() ?? request()?->ip(),
        ]);
    }
}
