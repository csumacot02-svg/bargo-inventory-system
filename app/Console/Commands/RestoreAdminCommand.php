<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\AuthAudit;
use Illuminate\Console\Command;

class RestoreAdminCommand extends Command
{
    protected $signature = 'bargo:restore-admin
                            {email? : Email of an inactive admin (or staff) to restore}
                            {--promote : Promote a staff account to active administrator}';

    protected $description = 'Emergency recovery when no active administrator can sign in.';

    public function handle(): int
    {
        $email = $this->argument('email');

        if ($email === null) {
            return $this->showStatus();
        }

        $user = User::query()->where('email', $email)->first();

        if (! $user) {
            $this->error('No user found with email: '.$email);

            return self::FAILURE;
        }

        if ($this->option('promote')) {
            return $this->promoteStaff($user);
        }

        return $this->reactivateAdmin($user);
    }

    private function showStatus(): int
    {
        $active = User::activeAdminCount();
        $this->info('Active administrators: '.$active);

        if ($active > 0) {
            $this->line('Setup is closed. Use User Management to create more admins.');

            return self::SUCCESS;
        }

        $this->warn('No active administrator. Recovery options:');
        $this->line('  1. Browser: open /setup/admin and create a new admin');
        $this->line('  2. Reactivate: php artisan bargo:restore-admin email@example.com');
        $this->line('  3. Promote staff: php artisan bargo:restore-admin email@example.com --promote');

        $inactive = User::inactiveAdmins();
        if ($inactive->isEmpty()) {
            $this->line('No inactive admin accounts on file.');
        } else {
            $this->table(
                ['ID', 'Name', 'Email', 'Status'],
                $inactive->map(fn (User $u) => [$u->id, $u->name, $u->email, $u->status])->all()
            );
        }

        return self::SUCCESS;
    }

    private function reactivateAdmin(User $user): int
    {
        if (! $user->isAdmin()) {
            $this->error($user->email.' is not an administrator. Use --promote to make a staff user an admin.');

            return self::FAILURE;
        }

        if ($user->isActive()) {
            $this->info($user->email.' is already an active administrator.');

            return self::SUCCESS;
        }

        $user->forceFill([
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => $user->email_verified_at ?? now(),
        ])->save();

        AuthAudit::log(null, 'admin_restore', 'Reactivated administrator via artisan bargo:restore-admin.', null, $user);

        $this->info('Restored active administrator: '.$user->email);

        return self::SUCCESS;
    }

    private function promoteStaff(User $user): int
    {
        if ($user->isAdmin() && $user->isActive()) {
            $this->info($user->email.' is already an active administrator.');

            return self::SUCCESS;
        }

        $user->forceFill([
            'role' => User::ROLE_ADMIN,
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => $user->email_verified_at ?? now(),
        ])->save();

        AuthAudit::log(null, 'admin_promote', 'Promoted user to administrator via artisan bargo:restore-admin --promote.', null, $user);

        $this->info('Promoted to active administrator: '.$user->email);

        return self::SUCCESS;
    }
}
