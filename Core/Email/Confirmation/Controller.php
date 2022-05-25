<?php

namespace Minds\Core\Email\Confirmation;

use Minds\Core\Di\Di;
use Minds\Core\Security\TwoFactor\Manager;
use Minds\Entities\User;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\Response\JsonResponse;

/**
 * Email Confirmation Controller. Handles confirmation of email addresses
 * when confirmEmail is called, by delegating responsibility to the
 * TwoFactor managers gatekeeper. - which on success will verify email.
 */
class Controller
{
    /**
     * Constructor.
     * @param ?Manager $manager - TwoFactor manager.
     */
    public function __construct(
        private ?Manager $manager = null
    ) {
        $this->manager ??= Di::_()->get('Security\TwoFactor\Manager');
    }

    /**
     * Called when confirming email - will pass authentication request to gatekeeper
     * which will handle giving the user trusted state if they pass MFA.
     * @param ServerRequest $request - server request object.
     * @throws TwoFactorRequiredException - If two-factor is required to confirm.
     * @throws TwoFactorInvalidCodeException - If code is invalid.
     * @return JsonResponse - contains status success on success.
     */
    public function confirmEmail(ServerRequest $request): JsonResponse
    {
        /** @var User */
        $user = $request->getAttribute('_user');

        $this->manager->gatekeeper($user, $request);

        return new JsonResponse([
            'status' => 'success'
        ]);
    }
}
