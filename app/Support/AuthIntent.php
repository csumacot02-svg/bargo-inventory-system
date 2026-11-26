<?php

namespace App\Support;

use App\Models\User;
use InvalidArgumentException;

class AuthIntent
{
    public const SESSION_KEY = 'auth_intent';

    public const ADMIN = 'admin';

    public const STAFF = 'staff';

    /** Unified BARGO desk sign-in (administrator or staff credentials). */
    public const BARGO = 'bargo';

    /** @return list<string> */
    public static function all(): array
    {
        return [self::ADMIN, self::STAFF, self::BARGO];
    }

    public static function isValid(?string $intent): bool
    {
        return in_array($intent, self::all(), true);
    }

    public static function assertValid(string $intent): string
    {
        if (! self::isValid($intent)) {
            throw new InvalidArgumentException('Invalid authentication intent.');
        }

        return $intent;
    }

    public static function put(string $intent): void
    {
        session([self::SESSION_KEY => self::assertValid($intent)]);
    }

    public static function get(): ?string
    {
        $intent = session(self::SESSION_KEY);

        return self::isValid($intent) ? $intent : null;
    }

    public static function require(): string
    {
        $intent = self::get();

        if ($intent === null) {
            throw new \Illuminate\Http\Exceptions\HttpResponseException(
                redirect()
                    ->route('gateway')
                    ->with('error', 'Please select how you want to access the system first.')
            );
        }

        return $intent;
    }

    public static function clear(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    public static function label(string $intent): string
    {
        return match (self::assertValid($intent)) {
            self::ADMIN => 'Administrator',
            self::STAFF => 'Staff',
            self::BARGO => 'BARGO',
        };
    }

    public static function accountMatchesIntent(string $userRole, string $intent): bool
    {
        $intent = self::assertValid($intent);

        if ($intent === self::BARGO) {
            return in_array($userRole, [User::ROLE_ADMIN, User::ROLE_STAFF], true);
        }

        return $userRole === self::registrationRole($intent);
    }

    public static function allowsSelfRegistration(string $intent): bool
    {
        return in_array(self::assertValid($intent), [self::STAFF, self::BARGO], true);
    }

    /**
     * Resolve the role that may be assigned during registration from session intent.
     * Never reads role from the HTTP request body.
     */
    public static function registrationRole(string $intent): string
    {
        $intent = self::assertValid($intent);

        return match ($intent) {
            self::ADMIN => User::ROLE_ADMIN,
            self::STAFF, self::BARGO => User::ROLE_STAFF,
        };
    }
}
