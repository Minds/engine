<?php

namespace Minds\Core\Nostr;

use Minds\Exceptions\NotFoundException;
use Minds\Exceptions\UserErrorException;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\Response\JsonResponse;
use Ratchet\Server\IoServer;

class Controller
{
    public function __construct(
        protected ?Manager $manager = null
    ) {
        $this->manager ??= new Manager();
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

        return new JsonResponse([
            "names" => [
                $name => $publicKey,
            ]
        ]);
    }
}
