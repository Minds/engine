<?php

namespace Minds\Core\Email\Confirmation;

use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\Email\Confirmation\Exceptions\EmailConfirmationInvalidCodeException;
use Minds\Core\Email\Confirmation\Exceptions\EmailConfirmationMissingHeadersException;
use Minds\Core\Email\V2\Campaigns\Recurring\TenantUserWelcome\TenantUserWelcomeEmailer;
use Minds\Core\Log\Logger;
use Minds\Core\Security\TwoFactor\Manager;
use Minds\Core\Security\TwoFactor\TwoFactorInvalidCodeException;
use Minds\Core\Security\TwoFactor\TwoFactorRequiredException;
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
        private ?Manager $manager = null,
        private ?TenantUserWelcomeEmailer $tenantUserWelcomeEmailer = null,
        private ?Config $config = null,
        private ?Logger $logger = null
    ) {
        $this->manager ??= Di::_()->get('Security\TwoFactor\Manager');
        $this->config ??= Di::_()->get(Config::class);
        $this->tenantUserWelcomeEmailer ??= Di::_()->get(TenantUserWelcomeEmailer::class);
        $this->logger ??= Di::_()->get('Logger');
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

    /**
     * Send a new confirmation email
     * @param ServerRequest $request - server request object.
     * @return JsonResponse - contains key to be passed back on
     * subsequent requests and on code submission.
     */
    public function sendEmail(ServerRequest $request): JsonResponse
    {
        /** @var User */
        $user = $request->getAttribute('_user');

        try {
            $this->manager->requireEmailTwoFactor($user);
        } catch(TwoFactorRequiredException $e) {
            return new JsonResponse([
                'status' => 'success',
                'key' => $e->getKey()
            ]);
        }

        return new JsonResponse([
            'status' => 'success'
        ]);
    }

    /**
     * Verify an email confirmation code - expects headers to be set for
     * - X-MINDS-2FA-CODE: containing the code.
     * - X-MINDS-EMAIL-2FA-KEY: containing key from when confirmation was requested.
     * @param ServerRequest $request - server request object.
     * @throws EmailConfirmationMissingHeadersException - when 2FA code is missing.
     * @throws EmailConfirmationInvalidCodeException - when code is invalid.
     * @return JsonResponse - status success on success.
     */
    public function verifyCode(ServerRequest $request): JsonResponse
    {
        /** @var User */
        $user = $request->getAttribute('_user');

        $twoFactorHeader = $request->getHeader('X-MINDS-2FA-CODE');
        $code = (string) $twoFactorHeader[0];

        if (!$code || !$request->getHeader('X-MINDS-EMAIL-2FA-KEY')) {
            throw new EmailConfirmationMissingHeadersException('Both a X-MINDS-EMAIL-2FA-KEY and X-MINDS-2FA-CODE headers must be provided');
        }

        try {
            $this->manager->authenticateEmailTwoFactor($user, $code);
        } catch(TwoFactorInvalidCodeException $e) {
            throw new EmailConfirmationInvalidCodeException();
        }

        try {
            if((bool) $this->config->get('tenant_id')) {
                $this->tenantUserWelcomeEmailer
                    ->setUser($user)
                    ->queue($user);
            }
        } catch(\Exception $e) {
            $this->logger->error($e);
        }

        return new JsonResponse([
            'status' => 'success'
        ]);
    }
}
