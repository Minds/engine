<?php
namespace Minds\Core\Router\Middleware\Kernel;

use Minds\Core\Router\Enums\ApiScopeEnum;
use Minds\Core\Router\Enums\RequestAttributeEnum;
use Minds\Core\Router\Exceptions\ForbiddenException;
use Minds\Core\Router\RegistryEntry;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Checks if the route has the available scopres
 */
class ValidateScopesMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $requestHandler = $request->getAttribute(RequestAttributeEnum::REQUEST_HANDLER);
        if ($requestHandler instanceof RegistryEntry && $request->getAttribute(RequestAttributeEnum::USER)) {
            $requiredScopes = [...$requestHandler->getScopes(), ApiScopeEnum::ALL];
            $allocatedScopes = $request->getAttribute(RequestAttributeEnum::SCOPES);

            foreach ($allocatedScopes as $allocatedScope) {
                if (in_array($allocatedScope, $requiredScopes, true)) {
                    return $handler
                        ->handle($request);
                }
            }

            throw new ForbiddenException("Invalid scope");

        }


        return $handler
            ->handle($request);
    }
}
