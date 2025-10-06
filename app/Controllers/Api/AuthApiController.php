<?php
namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Libraries\JwtService;
use App\Models\RefreshTokenModel;
use App\Models\UserIdentityModel;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class AuthApiController extends BaseController
{
    use ResponseTrait;
    /** @var IncomingRequest */
    protected $request;

    private int $accessTTL = 3600;                  // 1 jam
    private int $refreshTTL = 60 * 60 * 24 * 30;     // 30 hari

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->request = service('request');
    }

    public function loginPage()
    {
        return view('auth/index', [
            'apiLoginUrl' => base_url('api/v1/auth/login'),
            'redirectAdminUrl' => base_url('admin/dashboard'),
            'redirectMobileUrl' => base_url('/'),
        ]);
    }

    public function login()
    {
        try {
            $data = $this->request->getJSON(true) ?? $this->request->getPost();
            $identity = trim((string) ($data['email'] ?? $data['username'] ?? ''));
            $password = (string) ($data['password'] ?? '');

            if ($identity === '' || $password === '') {
                return $this->failValidationErrors('email/username dan password wajib diisi.');
            }

            $ids = new UserIdentityModel();
            $row = $ids->findByEmailOrUsername($identity);
            if (!$row) {
                return $this->failUnauthorized('Kredensial tidak valid.');
            }

            $hash = $row['secret2'] ?? '';
            if (!is_string($hash) || $hash === '' || !password_verify($password, $hash)) {
                return $this->failUnauthorized('Kredensial tidak valid.');
            }

            $user = $ids->buildUserFromIdentity($row);
            $role = $user['role'] ?? 'mobile';

            $claims = [
                'sub' => (int) $user['id'],
                'email' => $user['email'] ?? null,
                'role' => $role,
            ];
            $jwt = (new JwtService())->issue($claims, $this->accessTTL);

            $refreshPlain = bin2hex(random_bytes(32));
            $refreshHash = password_hash($refreshPlain, PASSWORD_BCRYPT);
            $expiresAt = date('Y-m-d H:i:s', time() + $this->refreshTTL);

            $rt = new RefreshTokenModel();
            $rt->insert([
                'user_id' => (int) $user['id'],
                'refresh_hash' => $refreshHash,
                'revoked' => 0,
                'expires_at' => $expiresAt,
            ]);

            $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
            $this->response->setCookie(
                'access_token',
                $jwt,
                $this->accessTTL,
                '',
                '/',
                '',
                $secure,
                true,
                'Lax'
            );

            log_message('debug', 'JwtCookieBridge: injecting from cookie');
            return $this->respond([
                'token_type' => 'Bearer',
                'access_token' => $jwt,
                'expires_in' => $this->accessTTL,
                'refresh_token' => $refreshPlain,
                'user' => [
                    'id' => (int) $user['id'],
                    'username' => $user['username'] ?? null,
                    'email' => $user['email'] ?? null,
                    'role' => $role,
                ],
            ], 200);

        } catch (\Throwable $e) {
            log_message('error', 'Auth login error: {msg} at {file}:{line}', [
                'msg' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            if (ENVIRONMENT === 'development') {
                return $this->failServerError($e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
            }
            return $this->failServerError('Login gagal.');
        }
    }

    public function refresh()
    {
        $data = $this->request->getJSON(true) ?? $this->request->getPost();
        $refreshToken = $data['refresh_token'] ?? '';
        if (!$refreshToken) {
            return $this->failValidationErrors('refresh_token wajib diisi.');
        }

        $m = new RefreshTokenModel();
        $rows = $m->where('revoked', 0)
            ->where('expires_at >=', date('Y-m-d H:i:s'))
            ->orderBy('id', 'DESC')
            ->findAll(200);

        $match = null;
        foreach ($rows as $row) {
            if (password_verify($refreshToken, $row['refresh_hash'])) {
                $match = $row;
                break;
            }
        }
        if (!$match) {
            return $this->failUnauthorized('Refresh token tidak valid / kadaluarsa.');
        }

        $m->revokeById((int) $match['id']);

        $uid = (int) $match['user_id'];
        $ids = new UserIdentityModel();
        $identity = $ids->where('type', 'email_password')
            ->where('user_id', $uid)
            ->orderBy('id', 'DESC')
            ->first();
        if (!$identity) {
            return $this->failNotFound('User tidak ditemukan.');
        }
        $user = $ids->buildUserFromIdentity($identity);

        $jwt = (new JwtService())->issue([
            'sub' => $user['id'],
            'email' => $user['email'] ?? null,
            'role' => $user['role'] ?? 'mobile',
            'aud' => 'refresh',
            'dev' => 'refresh',
        ], $this->accessTTL);

        $plain = bin2hex(random_bytes(32));
        $hash = password_hash($plain, PASSWORD_BCRYPT);
        $exp = date('Y-m-d H:i:s', time() + $this->refreshTTL);
        $m->insert(['user_id' => $user['id'], 'refresh_hash' => $hash, 'revoked' => 0, 'expires_at' => $exp]);

        return $this->respond([
            'token_type' => 'Bearer',
            'access_token' => $jwt,
            'expires_in' => $this->accessTTL,
            'refresh_token' => $plain,
        ], 200);
    }

    public function logout()
    {
        $data = $this->request->getJSON(true) ?? $this->request->getPost();
        $uid = (int) ($data['user_id'] ?? 0); 
        $m = new RefreshTokenModel();

        if (!empty($data['refresh_token'])) {
            $rows = $m->where('user_id', $uid ?: null)->where('revoked', 0)->findAll(200);
            foreach ($rows as $r) {
                if (password_verify($data['refresh_token'], $r['refresh_hash'])) {
                    $m->revokeById((int) $r['id']);
                    break;
                }
            }
        } else {
            if ($uid > 0) {
                $m->revokeAllForUser((int) $uid);
            }
        }

        $this->response->deleteCookie('access_token', '', '/');

        $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        $this->response->setCookie(
            'access_token',
            '',
            -3600,
            '',
            '/',
            '',
            $secure,
            true,
            'Lax'
        );

        return $this->respond(['success' => true, 'message' => 'Logged out']);
    }

    public function me()
    {
        $authHeader = $this->request->getHeaderLine('Authorization');
        if (stripos($authHeader, 'Bearer ') !== 0) {
            return $this->failUnauthorized('Unauthorized');
        }
        $token = trim(substr($authHeader, 7));

        try {
            $payload = (new JwtService())->validate($token);
            $uid = (int) ($payload['sub'] ?? 0);
            if ($uid <= 0)
                return $this->failUnauthorized('Unauthorized');

            $ids = new UserIdentityModel();
            $identity = $ids->where('type', 'email_password')
                ->where('user_id', $uid)
                ->orderBy('id', 'DESC')
                ->first();
            if (!$identity)
                return $this->failUnauthorized('Unauthorized');

            $user = $ids->buildUserFromIdentity($identity);
            return $this->respond([
                'id' => (int) $user['id'],
                'username' => $user['username'] ?? null,
                'email' => $user['email'] ?? null,
                'role' => $user['role'] ?? null,
                'active' => 1,
            ], 200);

        } catch (\Throwable $e) {
            return $this->failUnauthorized('Unauthorized');
        }
    }
}
