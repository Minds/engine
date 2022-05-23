<?php

namespace Minds\Core\Nostr;

use Minds\Core\Di\Di;
use Minds\Exceptions\NotFoundException;
use Minds\Exceptions\UserErrorException;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\Response\JsonResponse;
use Ratchet\Server\IoServer;

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
}
