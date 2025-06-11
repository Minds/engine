<?php

namespace Minds\Core\Router\Middleware;

use Minds\Core\Di\Di;
use Minds\Core\Experiments\Manager as ExperimentsManager;
use Minds\Core\Router\Exceptions\ForbiddenException;
use Minds\Core\Security\XSRF;
use Minds\Exceptions\InactiveFeatureFlagException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 *
 */
class FeatureFlagMiddleware implements MiddlewareInterface
{
    /**
     * @var callable|string[]
     */
    private $xsrfValidateRequest;

    public function __construct(
        private string $featureFlag,
        callable $xsrfValidateRequest = null,
        private ?ExperimentsManager $experimentsManager = null
    ) {
        $this->xsrfValidateRequest = $xsrfValidateRequest ?: [XSRF::class, 'validateRequest'];
        $this->experimentsManager ??= Di::_()->get('Experiments\Manager');
    }

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     * @throws InactiveFeatureFlagException
     * @throws ForbiddenException
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $loggedInUser = $request->getAttribute('_user');
        if (
            !$loggedInUser ||
            (!call_user_func($this->xsrfValidateRequest, $request) && !$request->getAttribute('oauth_user_id'))
        ) {
            throw new ForbiddenException();
        }

        $this->experimentsManager->setUser($loggedInUser);

        if (!$this->experimentsManager->isOn($this->featureFlag)) {
            throw new InactiveFeatureFlagException();
        }

        return $handler->handle($request);
    }
}
