<?php

namespace Minds\Core\DID;

use Minds\Exceptions\NotFoundException;
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
     * Accepts the root document or a user document
     * ie. /.well-known/did.json or /mark/did.json
     * @param ServerRequest $request
     * @return JsonResponse
     * @throws NotFoundException
     */
    public function getDIDDocument(ServerRequest $request): JsonResponse
    {
        $path = ltrim($request->getUri()->getPath(), '/');
        $username = str_replace('/did.json', '', $path);

        if ($username === '.well-known') {
            $document = $this->manager->getRootDocument();
        } else {
            $document = $this->manager->getUserDocument($username);
        }

        return new JsonResponse($document->export());
    }
}
