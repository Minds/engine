<?php declare(strict_types=1);

namespace Minds\Core\Router\Middleware\Kernel;

use Minds\Core\Router\Enums\RequestAttributeEnum;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Adds the Content-Security-Policy policy and also provides a nonce
 * for scripts that require inline
 */
class ContentSecurityPolicyMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $nonce = bin2hex(openssl_random_pseudo_bytes(32));
        $host = $request->getHeader('Host')[0];
    
        $policy = [
            'default-src' => "'self' $host",
            'script-src' =>  "blob: data: 'self' 'nonce-$nonce' $host",
            'style-src' => "$host data: 'self' 'unsafe-inline'",
            'frame-src' => '*',
            'connect-src' => "data: 'self' $host *.cloudflarestream.com"
        ];

        $policyStr = "";

        foreach ($policy as $k => $v) {
            $policyStr .= "$k $v;";
        }

        $policyStr .= 'block-all-mixed-content; upgrade-insecure-requests;';

        return $handler
            ->handle(
                $request
                ->withAttribute(RequestAttributeEnum::CSP_NONCE, $nonce)
            )
            ->withHeader('Content-Security-Policy', $policyStr);
    }
}
