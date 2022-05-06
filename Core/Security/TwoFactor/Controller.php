<?php

namespace Minds\Core\Security\TwoFactor;

use Minds\Core\Di\Di;
use Minds\Core\Security\TwoFactor\Manager;
use Minds\Entities\User;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\ServerRequestFactory;
use Zend\Diactoros\Response\JsonResponse;

/**
 * TwoFactor Controller
 */
class Controller
{
    /**
     * Constructor.
     * @param ?Manager $manager
     */
    public function __construct(
        private ?Manager $manager = null
    ) {
        $this->manager ??= Di::_()->get('Security\TwoFactor\Manager');
    }

    /**
     * Called when confirming email - will pass authentication request to gatekeeper
     * which will handle giving the user trusted state if they pass MFA.
     * @param ServerRequest $request
     * @return JsonResponse
     */
    public function confirmEmail(ServerRequest $request): JsonResponse
    {
        /** @var User */
        $user = $request->getAttribute('_user');

        $this->manager->gatekeeper($user, ServerRequestFactory::fromGlobals());

        return new JsonResponse([
            'success' => true
        ]);
    }
}
