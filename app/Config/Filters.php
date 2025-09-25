<?php
namespace Config;

use App\Filters\RoleAdmin;
use App\Filters\RoleMobile;
use CodeIgniter\Config\BaseConfig;

// Built-in filters yang pasti ada di CI4 ^4.0
use CodeIgniter\Filters\CSRF;
use CodeIgniter\Filters\DebugToolbar;
use CodeIgniter\Filters\Honeypot;
use CodeIgniter\Filters\InvalidChars;
use CodeIgniter\Filters\SecureHeaders;

// App filters (JWT stack kita)
use App\Filters\JwtCookieBridge;
use App\Filters\JwtAuthFilter;
use App\Filters\RoleFilter;

class Filters extends BaseConfig
{
    /**
     * Alias ke filter classes (gunakan FQCN).
     * Catatan: Jangan deklarasi alias untuk filter yang tidak ada kelasnya.
     */
    public $aliases = [
        // Built-in (pakai jika perlu)
        'csrf' => CSRF::class,
        'toolbar' => DebugToolbar::class,
        'honeypot' => Honeypot::class,
        'invalidchars' => InvalidChars::class,
        'secureheaders' => SecureHeaders::class,

        // JWT pipeline kita
        'auth' => [JwtCookieBridge::class, JwtAuthFilter::class],
        // authadmin = jwtcookie -> jwt -> role admin
        'authadmin' => [JwtCookieBridge::class, JwtAuthFilter::class, RoleAdmin::class],
        // authmobile = jwtcookie -> jwt -> role mobile
        'authmobile' => [JwtCookieBridge::class, JwtAuthFilter::class, RoleMobile::class],
    ];

    /**
     * Filters global (sebelum/sesudah setiap request)
     * Sesuaikan agar tidak mengganggu API.
     */
    public $globals = [
        'before' => [
            // Aktifkan CSRF hanya untuk non-API.
            // Jika kamu punya endpoint upload non-API, tambah di except.
            'csrf' => ['except' => ['api/*', 'admin/import-export/upload']],
            // 'invalidchars',
            // 'honeypot',
        ],
        'after' => [
            // 'secureheaders',
            // Toolbar berguna di dev; matikan di production.
            'toolbar',
        ],
    ];

    /**
     * Filters per-HTTP method (opsional)
     */
    public $methods = [
        // contoh: 'post' => ['csrf'],
    ];

    /**
     * Filters by URI pattern (opsional)
     */
    public $filters = [
        // contoh: 'csrf' => ['before' => ['forms/*']],
    ];
}
