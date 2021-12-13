<?php
namespace Minds\Core\Blockchain\SKALE;

use Minds\Core\Di\Di;
use Minds\Core\Features\Manager as FeaturesManager;
use Exception;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\ServerRequest;

/**
 * SKALE Controller.
 * @package Minds\Core\Blockchain\SKALE
 */
class Controller
{
    /**
     * Controller constructor.
     * @param Manager|null $manager - SKALE manager
     * @param FeaturesManager|null $featuresManager - features manager
     */
    public function __construct(
        protected ?Manager $manager = null,
        protected ?FeaturesManager $featuresManager = null
    ) {
        $this->manager = $manager ?? new Manager();
        $this->featuresManager = $featuresManager ?? Di::_()->get('Features\Manager');
    }

    /**
     * Makes a request for more funds from a faucet
     * @param ServerRequest $request - request containing logged in user.
     * @throws RateLimitException - when rate limits are exceeded.
     * @throws ServerErrorException - internal error.
     * @return JsonResponse - response containing the tx hash in 'data'.
     */
    public function requestFromFaucet(ServerRequest $request): JsonResponse
    {
        if (!$this->featuresManager->has('skale')) {
            throw new Exception('SKALE network is disabled');
        }

        $user = $request->getAttribute('_user');
        $address = $request->getParsedBody()['address'];

        $txHash = $this->manager->requestFromFaucet($user, $address);

        return new JsonResponse(array_merge([
            'status' => 'success',
            'data' => $txHash,
        ]));
    }
}
