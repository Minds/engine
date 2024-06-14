<?php
namespace Minds\Core\Router\Middleware\Kernel;

use Minds\Core\Authentication\PersonalApiKeys\PersonalApiKey;
use Minds\Core\Authentication\PersonalApiKeys\Services\PersonalApiKeyAuthService;
use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Router\Enums\RequestAttributeEnum;
use Minds\Core\Router\Exceptions\UnauthorizedException;
use Minds\Core\Router\RegistryEntry;
use Minds\Entities\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * If a valid personal api token is provided, we hydrate the user (_user) attribute and pass through
 * the available scopes
 */
class PersonalApiTokenMiddleware implements MiddlewareInterface
{
    public function __construct(
        private ?PersonalApiKeyAuthService $personalApiKeyAuthService = null,
        private ?EntitiesBuilder $entitiesBuilder = null,
    ) {
        $this->personalApiKeyAuthService ??= Di::_()->get(PersonalApiKeyAuthService::class);
        $this->entitiesBuilder ??= Di::_()->get(EntitiesBuilder::class);
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $personalApiKey = $this->getPersonalApiKey($request);

        if ($personalApiKey) {
            $user = $this->entitiesBuilder->single($personalApiKey->ownerGuid);
            if (!$user instanceof User || $user->isBanned() || !$user->isEnabled()) {
                // Bad user
                throw new UnauthorizedException();
            }

            if (!$this->personalApiKeyAuthService->validateKey($personalApiKey)) {
                throw new UnauthorizedException("Personal Api Key is no longer valid");
            }

            return $handler->handle(
                $request
                    ->withAttribute(RequestAttributeEnum::USER, $user)
                    ->withAttribute(RequestAttributeEnum::PERSONAL_API_KEY, $personalApiKey)
                    ->withAttribute(RequestAttributeEnum::SCOPES, $personalApiKey->scopes),
            );
        }

        // If no personal api key, allow to continue, unauthenticated

        return $handler
            ->handle($request);
    }

    /**
     * Returns a PersonalApiKey from the request header, if exists
     */
    private function getPersonalApiKey(ServerRequestInterface $request): ?PersonalApiKey
    {
        $apiKeyHeader = $request->getHeader('Authorization');
        if (isset($apiKeyHeader[0]) && strpos($apiKeyHeader[0], 'Bearer') === 0) {
            $bearerToken = substr($apiKeyHeader[0], 7);

            if (strpos($bearerToken, 'pak_') !== 0) {
                return null;
            }

            return $this->personalApiKeyAuthService->getKeyBySecret($bearerToken);
        }

        return null;
    }
}
