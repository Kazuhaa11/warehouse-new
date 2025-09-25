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
        return self::$user['id'] ?? null;
    }

    public static function role(): ?string
    {
        return self::$user['role'] ?? null;
    }

    public static function loggedIn(): bool
    {
        return self::$user !== null;
    }
}
