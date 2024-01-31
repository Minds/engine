<?php declare(strict_types=1);
/**
 * Router
 * @author edgebal
 */

namespace Minds\Core;

use Minds\Core\Di\Di;
use Minds\Core\Router\Dispatcher;
use Minds\Core\Router\Hooks\ShutdownHandlerManager;
use Minds\Core\Router\Middleware\Kernel;
use Minds\Core\Router\PrePsr7\Fallback;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\ServerRequestFactory;
use Zend\Diactoros\Uri;

class Router
{
    /** @var Dispatcher */
    protected $dispatcher;

    /** @var Fallback */
    protected $fallback;

    /** @var ShutdownHandlerManager */
    protected $shutdownHandlerManager;

    /**
     * Router constructor.
     * @param Dispatcher $dispatcher
     * @param Fallback $fallback
     */
    public function __construct(
        $dispatcher = null,
        $fallback = null,
        ShutdownHandlerManager $shutDownHandlerManager = null
    ) {
        $this->dispatcher = $dispatcher ?: Di::_()->get('Router');
        $this->fallback = $fallback ?: new Fallback();
        $this->shutdownHandlerManager = $shutDownHandlerManager ?? Di::_()->get('Router\Hooks\ShutdownHandlerManager');
        $this->shutdownHandlerManager->registerAll();
    }

    public function handleRequest(RequestInterface $request): ResponseInterface
    {
        return $this->dispatcher
            ->pipe(new Kernel\ContentNegotiationMiddleware())
            ->pipe(new Kernel\ErrorHandlerMiddleware())
            ->pipe(new Kernel\MultiTenantBootMiddleware())
            ->pipe(
                (new Kernel\RouteResolverMiddleware())
                    ->setAttributeName('_request-handler')
            ) // Note: Pre-PSR7 routes will not advance further than this
            ->pipe(new Kernel\CorsMiddleware())
            ->pipe(new Kernel\JsonPayloadMiddleware())
            ->pipe(new Kernel\FrameSecurityMiddleware())
            ->pipe(
                (new Kernel\SessionMiddleware())
                    ->setAttributeName('_user')
            )
            ->pipe(
                (new Kernel\OauthMiddleware())
                    ->setAttributeName('_user')
            )
            ->pipe(new Kernel\XsrfCookieMiddleware())
            ->pipe(
                (new Kernel\RequestHandlerMiddleware())
                    ->setAttributeName('_request-handler')
            )
            ->handle($request);
    }

    /**
     * @param string|null $uri
     * @param string|null $method
     * @param string|null $host
     */
    public function route(ServerRequestInterface $request): void
    {
        $response = $this->handleRequest($request);

        foreach ($response->getHeaders() as $header => $values) {
            foreach ($values as $value) {
                header(sprintf('%s: %s', $header, $value), false);
            }
        }

        http_response_code($response->getStatusCode());
        echo $response->getBody();
    }
}
