<?php

namespace Minds\Api;

use Minds\Interfaces;

abstract class AbstractApi implements Interfaces\Api
{
    protected $accessControlAllowOrigin = ['*'];
    protected $accessControlAllowHeaders = [];
    protected $accessControlAllowMethods = [];
    protected $defaultResponse = ['status' => 'success'];

    const HTTP_CODES = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Moved Temporarily',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Time-out',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Large',
        415 => 'Unsupported Media Type',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Time-out',
        505 => 'HTTP Version not supported',
    ];

    public function __construct()
    {
        $this->sendAccessControlHeaders();
    }

    protected function sendAccessControlHeaders(): void
    {
        $this->sendAccessControlAllowOrigin();
        $this->sendAccessControlAllowHeaders();
        $this->sendAccessControlAllowMethods();
    }

    protected function sendAccessControlAllowOrigin(): void
    {
        if (!empty($this->accessControlAllowOrigin)) {
            header("Access-Control-Allow-Origin: " .
                $this->parseAccessControlArray($this->accessControlAllowOrigin), false);
        }
    }

    protected function sendAccessControlAllowHeaders(): void
    {
        if (!empty($this->accessControlAllowHeaders)) {
            header("Access-Control-Allow-Headers: " .
                $this->parseAccessControlArray($this->accessControlAllowHeaders), false);
        }
    }

    protected function sendAccessControlAllowMethods(): void
    {
        if (!empty($this->accessControlAllowMethods)) {
            header("Access-Control-Allow-Methods: " .
                $this->parseAccessControlArray($this->accessControlAllowMethods), false);
        }
    }

    protected function parseAccessControlArray(array $accessControlArray): string
    {
        $output = "";
        $lastHeader = end($accessControlArray);
        foreach ($accessControlArray as $header) {
            $output .= $header;
            if ($header !== $lastHeader) {
                $output .= ",";
            }
        }

        return $output;
    }

    protected function setResponseCode(int $code = 200): int
    {
        if (!isset(self::HTTP_CODES[$code])) {
            exit('Unknown http status code "' . htmlentities($code) . '"');
        }

        $text = self::HTTP_CODES[$code];
        $protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0');
        header("${protocol} ${code} ${text}");

        return $code;
    }

    protected function sendArrayOfObjects($array, int $code = 200): void
    {
        $this->send(array_values($array), $code);
    }

    protected function send($responseArray, int $code = 200, $jsonOptions = 0): void
    {
        $responseArray = array_merge($this->defaultResponse, $responseArray);
        $returnString = json_encode($responseArray, $jsonOptions);
        $this->sendJsonString($returnString, $code);
    }

    protected function sendJsonString(string $jsonString, int $code = 200): void
    {
        header('Content-Type: application/json');
        header('Content-Length:' . strlen($jsonString));
        $this->setResponseCode($code);
        echo $jsonString;
    }

    protected function sendInternalServerError(): void
    {
        $this->sendError(500);
    }

    protected function sendBadRequest(string $message = null): void
    {
        $this->sendError(400, $message);
    }

    protected function sendNotImplemented(): void
    {
        $this->sendError(501);
    }

    protected function sendNotModified(): void
    {
        $this->sendError(304);
    }

    protected function sendNotAcceptable(string $message = null): void
    {
        $this->sendError(406, $message);
    }

    protected function sendUnauthorised(): void
    {
        $this->sendError(401);
    }

    protected function sendSuccess(): void
    {
        $this->send([]);
    }

    protected function sendError(int $code = 406, string $message = null): void
    {
        if (is_null($message)) {
            $message = self::HTTP_CODES[$code];
        }
        $this->send($this->buildError($message), $code);
    }

    protected function buildError(string $message): array
    {
        return [
            'status' => 'error',
            'message' => $message
        ];
    }

    public function get($pages): void
    {
        $this->sendNotImplemented();
    }

    public function post($pages): void
    {
        $this->sendNotImplemented();
    }

    public function put($pages): void
    {
        $this->sendNotImplemented();
    }

    public function delete($pages): void
    {
        $this->sendNotImplemented();
    }
}
