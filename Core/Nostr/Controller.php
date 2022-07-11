<?php

namespace Minds\Core\Nostr;

use Minds\Api\Exportable;
use Minds\Core\Di\Di;
use Minds\Core\Nostr\RequestValidators\GetEventsRequestValidator;
use Minds\Exceptions\NotFoundException;
use Minds\Exceptions\ServerErrorException;
use Minds\Exceptions\UserErrorException;
use Psr\Http\Message\ServerRequestInterface;
use Ratchet\Server\IoServer;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\ServerRequest;

class Controller
{
    public function __construct(
        protected ?Manager $manager = null,
        protected ?PocSync $pocSync = null
    ) {
        $this->manager ??= Di::_()->get('Nostr\Manager');
        $this->pocSync ??= Di::_()->get('Nostr\PocSync');
    }

    /**
     * https://github.com/nostr-protocol/nips/blob/master/05.md
     * ie. /.well-known/nostr.json?name=mark
     * @param ServerRequest $request
     * @return JsonResponse
     * @throws NotFoundException
     */
    public function resolveNip05(ServerRequest $request): JsonResponse
    {
        $name = $request->getQueryParams()['name'] ?? null;

        if (!$name) {
            throw new UserErrorException("?name must be provided");
        }

        $publicKey = $this->manager->getPublicKeyFromUsername($name);

        return new JsonResponse(
            [
                "names" => [
                    $name => $publicKey,
                ]
            ],
            200,
            [
                'Access-Control-Allow-Origin' => '*',
            ]
        );
    }

    /**
     * /api/v3/nostr/sync
     * @param ServerRequest $request
     * @return JsonResponse
     * @throws NotFoundException
     */
    public function sync(ServerRequest $request): JsonResponse
    {
        $username = $request->getQueryParams()['username'] ?? null;

        if (!$username) {
            throw new UserErrorException("?name must be provided");
        }

        $this->pocSync->syncChannel($username);

        return new JsonResponse([]);
    }

    /**
     * GET /api/v3/nostr/events
     * @param ServerRequestInterface $request
     * @return JsonResponse
     * @throws NotFoundException
     * @throws UserErrorException
     * @throws ServerErrorException
     */
    public function getNostrEvents(ServerRequestInterface $request): JsonResponse
    {
        $requestValidator = new GetEventsRequestValidator();
        $requestBody = $request->getParsedBody();

        if (!$requestValidator->validate($requestBody)) {
            throw new UserErrorException("Some errors where encountered whilst validating the request", errors: $requestValidator->getErrors());
        }

        $nostrEvents = $this->manager->getNostrEventsForAuthors($requestBody['authors']);

        return new JsonResponse(Exportable::_($nostrEvents));
    }
}
