<?php
namespace App\Libraries;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtService
{
    private string $algo = 'HS256';
    private string $secret;
    private ?string $issuer;
    private ?string $audience;

    public function __construct()
    {
        $this->secret = getenv('JWT_SECRET') ?: 'please-change-me';
        $this->issuer = getenv('JWT_ISSUER') ?: null;
        $this->audience = getenv('JWT_AUDIENCE') ?: null;

        if ($this->secret === 'please-change-me') {
            log_message('warning', 'JWT_SECRET belum diatur, gunakan nilai kuat di .env');
        }
    }

    /**
     * @param array 
     * @param int  
     */
    public function issue(array $claims, int $ttl): string
    {
        $now = time();
        $payload = array_merge($claims, [
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + $ttl,
        ]);

        if ($this->issuer)
            $payload['iss'] = $this->issuer;
        if ($this->audience)
            $payload['aud'] = $this->audience;

        return JWT::encode($payload, $this->secret, $this->algo);
    }

    /**
     * @return array 
     * @throws \Exception 
     */
    public function validate(string $jwt): array
    {
        $decoded = JWT::decode($jwt, new Key($this->secret, $this->algo));
        return json_decode(json_encode($decoded), true);
    }
}
