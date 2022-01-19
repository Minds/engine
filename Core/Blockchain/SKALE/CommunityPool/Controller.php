<?php
namespace Minds\Core\Blockchain\SKALE\CommunityPool;

use Minds\Core\Di\Di;
use Minds\Core\Features\Manager as FeaturesManager;
use Minds\Exceptions\ServerErrorException;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\ServerRequest;

/**
 * SKALE CommunityPool Controller.
 * @package Minds\Core\Blockchain\SKALE\CommunityPool
 */
class Controller
{
    /**
     * Controller constructor.
     * @param Manager|null $manager - SKALE manager.
     * @param FeaturesManager|null $featuresManager - features manager.
     */
    public function __construct(
        public ?Manager $manager = null,
        public ?FeaturesManager $featuresManager = null
    ) {
        $this->manager = $manager ?? Di::_()->get('Blockchain\SKALE\CommunityPool\Manager');
        $this->featuresManager = $featuresManager ?? Di::_()->get('Features\Manager');
    }

    /**
     * Whether a user can exit from the SKALE chain based on a contract call determining they
     * have a high enough CommunityPool balance.
     * @param ServerRequest $request
     * @return JsonResponse
     */
    public function canExit(ServerRequest $request): JsonResponse
    {
        if (!$this->featuresManager->has('skale')) {
            throw new ServerErrorException('SKALE network is not enabled');
        }

        $address = $request->getQueryParams()['address'];

        if (!$address) {
            throw new ServerErrorException('Address is required');
        }

        $canExit = $this->manager->canExit($address);

        return new JsonResponse([
            'status' => 'success',
            'canExit' => $canExit,
        ]);
    }
}
