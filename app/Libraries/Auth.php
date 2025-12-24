<?php

namespace App\Libraries;

/**
 * Penyimpan konteks user per-request (sederhana, tanpa Shield).
 * Aman untuk PHP-FPM karena siklus request terpisah.
 */
class Auth
{
    private static ?array $user = null;

    public static function setUser(?array $user): void
    {
        self::$user = $user;
    }

    public static function user(): ?array
    {
        return self::$user;
    }

    public static function id(): ?int
    {
        return self::$user ? (int) (self::$user['id'] ?? null) : null;
    }

    public static function role(): ?string
    {
        return self::$user ? (string) (self::$user['role'] ?? null) : null;
    }

    public static function loggedIn(): bool
    {
        return self::$user !== null;
    }

    public static function plant(): ?string
    {
        $p = self::$user['plant'] ?? null;
        if ($p === null || $p === '') return null;
        return (string) $p;
    }

    public static function isSuperAdmin(): bool
    {
        return self::role() === 'super_admin';
    }

    public static function clear(): void
    {
        self::$user = null;
    }
}
