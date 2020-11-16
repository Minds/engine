<?php

namespace Minds\Core\Feeds\Activity;

use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\Response\JsonResponse;
use Minds\Entities\Activity;

class Controller
{
    /** @var Manager */
    protected $manager;

    public function __construct($manager = null)
    {
        $this->manager = $manager ?? new Manager();
    }

    /**
     * Delete entity enpoint
     * @param ServerRequest $request
     * @return JsonResponse
     */
    public function delete(ServerRequest $request): JsonResponse
    {
        $parameters = $request->getAttribute('parameters');
        if (!($parameters['urn'] ?? null)) {
            return new JsonResponse([
                'status' => 'error',
                'message' => ':urn not provided'
            ]);
        }

        /** @var string */
        $urn = $parameters['urn'];

        $entity = $this->manager->getByUrn($urn);

        if (!$entity) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'The post does not appear to exist',
            ]);
        }

        if ($entity->canEdit()) {
            if ($this->manager->delete($entity)) {
                return new JsonResponse([
                    'status' => 'success',
                ]);
            }
        }

        return new JsonResponse([
            'status' => 'error',
            'message' => 'There was an unknown error deleting this post',
        ]);
    }
}
