<?php

namespace App\Models;

use Database\Factories\UserFactory;
use App\Notifications\EmailOtpNotification;
use Illuminate\Auth\Events\Verified;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'employee_id',
        'id_number',
        'department',
        'religion',
        'sex',
        'date_of_birth',
        'birth_place',
        'avatar',
        'provider',
        'provider_id',
        'role',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'email_otp_hash',
    ];

    public const ROLE_ADMIN = 'admin';

    public const ROLE_STAFF = 'staff';

    public const STATUS_PENDING = 'pending';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_SUSPENDED = 'suspended';

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'email_otp_expires_at' => 'datetime',
            'date_of_birth' => 'date',
            'password' => 'hashed',
        ];
    }

    public function sendEmailVerificationNotification(): void
    {
        // Always 6 characters, including leading zeros (e.g. 051921).
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $this->forceFill([
            'email_otp_hash' => Hash::make($code),
            'email_otp_expires_at' => now()->addMinutes(10),
        ])->save();

        $this->notify(new EmailOtpNotification($code));
    }

    public function verifyEmailOtp(string $code): bool
    {
        // Keep as string so leading zeros are not dropped.
        $code = preg_replace('/\D+/', '', $code) ?? '';

        if (strlen($code) !== 6) {
            return false;
        }

        if (! filled($this->email_otp_hash)) {
            return false;
        }

        if ($this->email_otp_expires_at === null || $this->email_otp_expires_at->lt(now())) {
            return false;
        }

        if (! Hash::check($code, $this->email_otp_hash)) {
            return false;
        }

        $this->forceFill([
            'email_verified_at' => $this->freshTimestamp(),
            'email_otp_hash' => null,
            'email_otp_expires_at' => null,
        ])->save();

        event(new Verified($this));

        return true;
    }

    public function age(): ?int
    {
        return $this->date_of_birth?->age;
    }

    public function avatarUrl(): string
    {
        if ($this->avatar && file_exists(storage_path('app/public/'.$this->avatar))) {
            return asset('storage/'.$this->avatar);
        }

        $initials = urlencode(mb_strtoupper(mb_substr($this->name, 0, 1)));

        return 'https://ui-avatars.com/api/?name='.$initials.'&background=0D2E6B&color=fff&size=128';
    }

    public static function adminExists(): bool
    {
        return static::query()->where('role', self::ROLE_ADMIN)->exists();
    }

    public static function activeAdminExists(): bool
    {
        return static::activeAdminCount() > 0;
    }

    public static function activeAdminCount(): int
    {
        return static::query()
            ->where('role', self::ROLE_ADMIN)
            ->where('status', self::STATUS_ACTIVE)
            ->count();
    }

    public static function inactiveAdmins()
    {
        return static::query()
            ->where('role', self::ROLE_ADMIN)
            ->where('status', '!=', self::STATUS_ACTIVE)
            ->orderBy('name')
            ->get();
    }

    public function isLastActiveAdmin(): bool
    {
        return $this->isAdmin() && $this->isActive() && static::activeAdminCount() <= 1;
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isStaff(): bool
    {
        return $this->role === self::ROLE_STAFF;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function canAccessApplication(): bool
    {
        return $this->hasVerifiedEmail() && $this->isActive();
    }

    public function canManageInventory(): bool
    {
        return $this->isActive() && in_array($this->role, [self::ROLE_ADMIN, self::ROLE_STAFF], true);
    }

    public function canDeleteInventory(): bool
    {
        return $this->isActive() && $this->isAdmin();
    }

    public function canApproveRequests(): bool
    {
        return $this->canManageInventory();
    }

    public function canProcessPayments(): bool
    {
        return $this->canApproveRequests();
    }

    public function canViewAllRequests(): bool
    {
        return $this->canManageInventory();
    }

    public function canViewRequest(InventoryRequest $request): bool
    {
        if ($this->canViewAllRequests()) {
            return true;
        }

        return $this->isActive() && (int) $request->requester_id === (int) $this->id;
    }

    public function canActOnRequest(InventoryRequest $request): bool
    {
        if (! $this->canApproveRequests()) {
            return false;
        }

        if ((int) $request->requester_id === (int) $this->id) {
            // Admins may process requests they recorded for a client; lone staff may too.
            return $this->isAdmin() || $this->isOnlyActiveStaffOnDesk();
        }

        return true;
    }

    /**
     * Lone staff on duty may complete their own request when no peer staff is active
     * (admin may be off-site with an active account).
     */
    public function isOnlyActiveStaffOnDesk(): bool
    {
        if (! $this->isStaff()) {
            return false;
        }

        return ! static::query()
            ->where('role', self::ROLE_STAFF)
            ->where('status', self::STATUS_ACTIVE)
            ->whereKeyNot($this->getKey())
            ->exists();
    }

    public function canManageMasterData(): bool
    {
        return $this->canManageInventory();
    }

    public function canManageDocumentTemplates(): bool
    {
        return $this->canManageInventory();
    }

    public function canViewAuditLogs(): bool
    {
        return $this->canManageInventory();
    }

    public function canAdjustStock(): bool
    {
        return $this->canManageInventory();
    }

    public function canVoidStockIn(): bool
    {
        return $this->isActive() && $this->isAdmin();
    }

    public function canAccessReports(): bool
    {
        return $this->canManageInventory();
    }

    public function canScanItems(): bool
    {
        return $this->canManageInventory();
    }

    public function canManageUsers(): bool
    {
        return $this->isActive() && $this->isAdmin();
    }

    public function canSubmitRequests(): bool
    {
        return $this->canManageInventory();
    }

    public function roleLabel(): string
    {
        return match ($this->role) {
            self::ROLE_ADMIN => 'BARGO Administrator',
            default => 'BARGO Staff',
        };
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_PENDING => 'Pending approval',
            self::STATUS_REJECTED => 'Rejected',
            self::STATUS_SUSPENDED => 'Suspended',
            default => ucfirst((string) $this->status),
        };
    }

    public function homeRoute(): string
    {
        return $this->isAdmin() ? 'admin.dashboard' : 'staff.dashboard';
    }

    public function usesPassword(): bool
    {
        return filled($this->password);
    }

    public static function currentActiveStaff(): ?self
    {
        $actor = auth()->user();
        if ($actor instanceof self && $actor->isStaff() && $actor->isActive()) {
            return $actor;
        }

        return static::query()
            ->where('role', self::ROLE_STAFF)
            ->where('status', self::STATUS_ACTIVE)
            ->latest('id')
            ->first();
    }

    public static function currentActiveAdmin(): ?self
    {
        $actor = auth()->user();
        if ($actor instanceof self && $actor->isAdmin() && $actor->isActive()) {
            return $actor;
        }

        return static::query()
            ->where('role', self::ROLE_ADMIN)
            ->where('status', self::STATUS_ACTIVE)
            ->orderBy('id')
            ->first();
    }

    /**
     * Active BARGO coordinator for Payment Order / official forms.
     * Prefers the signed-in administrator; otherwise the primary active admin account.
     */
    public static function currentBargoCoordinator(?self $preferred = null): ?self
    {
        if ($preferred instanceof self && $preferred->isAdmin() && $preferred->isActive()) {
            return $preferred;
        }

        return static::currentActiveAdmin();
    }

    /**
     * @return array{name: string, title: string}
     */
    public static function paymentOrderCoordinator(?self $preferred = null): array
    {
        $coordinator = static::currentBargoCoordinator($preferred);
        $fallback = trim((string) config('bargo.payment_order.coordinator_name', ''));
        $name = static::signatureName($coordinator);

        if ($name === '—' && $fallback !== '') {
            $name = mb_strtoupper($fallback);
        }

        return [
            'name' => $name,
            'title' => (string) config('bargo.payment_order.coordinator_title', 'Coordinator, BARGO'),
        ];
    }

    /**
     * @return array{prepared_by_name: string, prepared_by_title: string, approved_by_name: string, approved_by_title: string}
     */
    public static function reportSignatories(): array
    {
        $staff = static::currentActiveStaff();
        if (! $staff instanceof self && auth()->user() instanceof self) {
            $staff = auth()->user();
        }

        $admin = static::currentBargoCoordinator();

        return [
            'prepared_by_name' => static::signatureName($staff),
            'prepared_by_title' => (string) config('bargo.signatories.prepared_by_title', 'Office Assistant'),
            'approved_by_name' => static::signatureName($admin),
            'approved_by_title' => (string) config('bargo.signatories.approved_by_title', 'BARGO, Head'),
        ];
    }

    public static function signatureName(?self $user): string
    {
        $name = trim((string) $user?->name);

        return $name !== '' ? mb_strtoupper($name) : '—';
    }
}
