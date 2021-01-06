<?php
namespace Minds\Core\Boost\LiquiditySpot;

use Minds\Core\Boost\Network\Boost;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\ServerRequest;

class Controller
{
    /** @var Manager */
    protected $manager;

    public function __construct(Manager $manager = null)
    {
        $this->manager = $manager ?? new Manager();
    }

    /**
     * Returns the liquidity spot
     */
    public function get(ServerRequest $request): JsonResponse
    {
        /** @var Boost */
        $boost = $this->manager->get();
        return new JsonResponse([
            'status' => 'success',
            'entity' => $boost && $boost->getEntity() ? $boost->getEntity()->export() : null
        ]);
    }
}
