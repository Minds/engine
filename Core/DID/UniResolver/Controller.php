<?php

namespace Minds\Core\DID\UniResolver;

use Minds\Exceptions\UserErrorException;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\Response\JsonResponse;

class Controller
{
    public function __construct(
        protected ?Manager $manager = null
    ) {
        $this->manager ??= new Manager();
    }

    /**
     * @param ServerRequest $request
     * @return JsonResponse
     */
    public function resolve(ServerRequest $request): JsonResponse
    {
        $did = $request->getAttribute('parameters')['did'];

        if (!$did) {
            throw new UserErrorException('You must provide a DID');
        }

        return new JsonResponse($this->manager->resolve($did));
    }
}
