<?php
declare(strict_types=1);

namespace Minds\Core\Router\Middleware;

use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\Router\Exceptions\ForbiddenException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class MauticWebhookMiddleware implements MiddlewareInterface
{
    private const MAUTIC_SHARED_SECRET_HEADER = 'X-Mautic-Shared-Secret-Header';

    public function __construct(
        private ?Config $config = null
    ) {
        $this->config ??= Di::_()->get('Config');
    }

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     * @throws \Exception
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (count($request->getHeader(self::MAUTIC_SHARED_SECRET_HEADER)) === 0) {
            throw new ForbiddenException('Mautic shared secret header is missing');
        } elseif ($request->getHeader(self::MAUTIC_SHARED_SECRET_HEADER)[0] !== $this->config->get('email')['mautic']['shared_key']) {
            throw new ForbiddenException('Mautic shared secret header value mismatch');
        }

        return $handler->handle($request);
    }
}
