<?php declare(strict_types=1);
/**
 * CorsMiddleware
 * @author edgebal
 */

namespace Minds\Core\Router\Middleware\Kernel;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Diactoros\Response\EmptyResponse;

class CorsMiddleware implements MiddlewareInterface
{
    /**
     * Process an incoming server request.
     *
     * Processes an incoming server request in order to produce a response.
     * If unable to produce the response itself, it may delegate to the provided
     * request handler to do so.
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($request->getMethod() === 'OPTIONS') {
            return new EmptyResponse(204, [
                'Access-Control-Allow-Origin' => $request->getHeaderLine('Origin'),
                'Access-Control-Allow-Credentials' => 'true',
                'Access-Control-Max-Age' => '86400',
                'Access-Control-Allow-Methods' => implode(',', [
                    'GET',
                    'POST',
                    'PUT',
                    'DELETE',
                    'OPTIONS',
                ]),
                'Access-Control-Allow-Headers' => implode(',', [
                    'Accept',
                    'Authorization',
                    'Cache-Control',
                    'Content-Type',
                    'DNT',
                    'If-Modified-Since',
                    'Keep-Alive',
                    'Origin',
                    'User-Agent',
                    'X-Mx-ReqToken',
                    'X-Requested-With',
                    'X-No-Cache',
                ]),
            ]);
        }

        return $handler
            ->handle($request);
    }
}
