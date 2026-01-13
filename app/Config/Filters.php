<?php
namespace Config;

use App\Filters\CorsFilter;
use App\Filters\RoleAdmin;
use App\Filters\RoleMobile;
use CodeIgniter\Config\BaseConfig;

use CodeIgniter\Filters\CSRF;
use CodeIgniter\Filters\DebugToolbar;
use CodeIgniter\Filters\Honeypot;
use CodeIgniter\Filters\InvalidChars;
use CodeIgniter\Filters\SecureHeaders;

use App\Filters\JwtCookieBridge;
use App\Filters\JwtAuthFilter;
use App\Filters\SuperAdminFilter;

class Filters extends BaseConfig
{

    public $aliases = [
        'csrf' => CSRF::class,
        'toolbar' => DebugToolbar::class,
        'honeypot' => Honeypot::class,
        'invalidchars' => InvalidChars::class,
        'secureheaders' => SecureHeaders::class,
        'cors' => CorsFilter::class,
        'auth' => [JwtCookieBridge::class, JwtAuthFilter::class],
        // authadmin = jwtcookie -> jwt -> role admin
        'authadmin' => [JwtCookieBridge::class, JwtAuthFilter::class, RoleAdmin::class],
        // authmobile = jwtcookie -> jwt -> role mobile
        'authmobile' => [JwtCookieBridge::class, JwtAuthFilter::class, RoleMobile::class],
        // authsuperadmin = jwtcookie -> jwt -> role super_admin
        'authsuperadmin' => [JwtCookieBridge::class, JwtAuthFilter::class, SuperAdminFilter::class],
    ];


    public $globals = [
        'before' => [
            'cors',
        ],
        'after' => [
            'toolbar',
        ],
    ];

    public $methods = [
    ];

    public $filters = [
    ];
}
