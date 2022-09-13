<?php

namespace Minds\Core\Rewards\Restrictions\Blockchain;

use Minds\Core\Di\Di;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\ServerRequest;

/**
 * Controller for blockchain restrictions - can be used to check a user is not restricted.
 */
class Controller
{
    public function __construct(
        protected ?Manager $restrictionsManager = null,
    ) {
        $this->restrictionsManager ??= Di::_()->get('Rewards\Restrictions\Blockchain\Manager');
    }

    /**
     * Check that an address is not restricted. Will ban if a user is restricted.
     * @param ServerRequest $request - request from server.
     * @throws RestrictedException - thrown if a user is restricted.
     * @return JsonResponse - returned if gatekeeper is passed.
     */
    public function check(ServerRequest $request): JsonResponse
    {
        $user = $request->getAttribute('_user');
        $address = $request->getAttribute('parameters')['address'];

        $this->restrictionsManager->gatekeeper($address, $user);

        return new JsonResponse([
            'status' => 'success'
        ]);
    }
}
