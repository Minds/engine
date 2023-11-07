<?php declare(strict_types=1);

namespace Minds\Core\Router\Middleware;

use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\Router\Exceptions\UnauthorizedException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * This middleware will prevent an endpoint from being called if the site is a multitenant
 */
class NotMultiTenantMiddleware implements MiddlewareInterface
{
    public function __construct(
        private ?Config $config = null
    ) {
        $this->config ??= Di::_()->get('Config');
    }

    /**
     * Process an incoming server request.
     *
     * Processes an incoming server request in order to produce a response.
     * If unable to produce the response itself, it may delegate to the provided
     * request handler to do so.
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     * @throws UnauthorizedException
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!!$this->config->get('tenant_id')) {
            throw new UnauthorizedException();
        }

        return $handler
            ->handle($request);
    }
}
