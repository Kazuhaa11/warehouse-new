<?php
namespace App\Controllers\Api;

use CodeIgniter\Controller;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class BaseApiController extends Controller
{
    use ResponseTrait;

    /** @var IncomingRequest|CLIRequest */
    protected $request;

    public function initController(
        RequestInterface $request,
        ResponseInterface $response,
        LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);
        /** @var IncomingRequest|CLIRequest $request */
        $this->request = $request; 
    }

    protected function ok($data = [], $meta = [], int $code = 200)
    {
        return $this->respond(['success' => true, 'data' => $data, 'meta' => $meta], $code);
    }

    protected function failMsg(string $message, int $code = 400, $errors = null)
    {
        return $this->respond(['success' => false, 'error' => ['message' => $message, 'details' => $errors]], $code);
    }
}
