<?php

namespace Minds\Core\Security\TwoFactor\Delegates;

use Minds\Core\Di\Di;
use Minds\Core\Email\Confirmation\Manager as EmailConfirmationManager;
use Minds\Core\Email\V2\Campaigns\Recurring\TwoFactor\TwoFactor as TwoFactorEmail;
use Minds\Core\Log\Logger;
use Minds\Core\Security\TwoFactor as TwoFactorService;
use Minds\Core\Security\TwoFactor\Store\TwoFactorSecret;
use Minds\Core\Security\TwoFactor\Store\TwoFactorSecretStore;
use Minds\Core\Security\TwoFactor\Store\TwoFactoSecretStoreInterface;
use Minds\Core\Security\TwoFactor\TwoFactorInvalidCodeException;
use Minds\Core\Security\TwoFactor\TwoFactorRequiredException;
use Minds\Entities\User;
use Zend\Diactoros\ServerRequestFactory;

/**
 * TwoFactor Email Delegate
 */
class EmailDelegate implements TwoFactorDelegateInterface
{
    /**
     * Constructor
     * @param TwoFactorService|null $twoFactorService - service handling two-factor.
     * @param Logger|null $logger - logger class.
     * @param TwoFactorEmail|null $twoFactorEmail - responsible for sending emails.
     * @param TwoFactoSecretStoreInterface|null $twoFactorSecretStore - handles storage of secrets.
     * @param EmailConfirmationManager|null $emailConfirmation - handles confirmation of email address.
     */
    public function __construct(
        private ?TwoFactorService $twoFactorService = null,
        private ?Logger $logger = null,
        private ?TwoFactorEmail $twoFactorEmail = null,
        private ?TwoFactoSecretStoreInterface $twoFactorSecretStore = null,
        private ?EmailConfirmationManager $emailConfirmation = null
    ) {
        $this->twoFactorService ??= new TwoFactorService();
        $this->logger ??= Di::_()->get('Logger');
        $this->twoFactorEmail ??= new TwoFactorEmail();
        $this->twoFactorSecretStore ??= new TwoFactorSecretStore();
        $this->emailConfirmation ??= Di::_()->get('Email\Confirmation');
    }

    /**
     * Should trigger the 2fa process
     * @param User $user
     * @throws TwoFactorRequiredException
     */
    public function onRequireTwoFactor(User $user): void
    {
        $key = $this->get2FAKeyHeader();
        if ($key) {
            $storedSecretObject = $this->twoFactorSecretStore->getByKey($key);
        } else {
            $storedSecretObject = $this->twoFactorSecretStore->get($user);
            $key = $storedSecretObject ? $this->twoFactorSecretStore->getKey($user) : '';
        }

        $secret = ($storedSecretObject && $storedSecretObject->getSecret()) ? $storedSecretObject->getSecret() : '';

        $storeEntry = false;
        if (!$secret) {
            $storeEntry = true;
            $secret = $this->twoFactorService->createSecret();
        }

        $this->logger->info('2fa - sending Email to ' . $user->getGuid());

        $code = $this->twoFactorService->getCode(
            secret: $secret,
            timeSlice: 1
        );

        $this->sendEmail($user, $code);

        if ($storeEntry) {
            $key = $this->twoFactorSecretStore->set($user, $secret);
        }

        @header("X-MINDS-EMAIL-2FA-KEY: $key", true);

        //forward to the twofactor page
        throw new TwoFactorRequiredException();
    }

    /**
     * Called upon authentication when the twofactor code has been provided
     * @param User $user
     * @param string $code
     * @return void
     * @throws TwoFactorInvalidCodeException
     */
    public function onAuthenticateTwoFactor(User $user, string $code): void
    {
        $request = ServerRequestFactory::fromGlobals();
        $key = $request->getHeader('X-MINDS-EMAIL-2FA-KEY')[0] ?? '';
        
        /** @var TwoFactorSecret */
        $storedSecretObject = $this->twoFactorSecretStore->getByKey($key);

        if (!$storedSecretObject) {
            throw new TwoFactorInvalidCodeException();
        }

        // we allow for 900 seconds for email confirmed users (15 mins) after we send a code.
        if ($storedSecretObject->getGuid()
            && $storedSecretObject->getTimestamp() > (time() - $this->twoFactorSecretStore->getTTL($user))
            && $user->getGuid() === (string) $storedSecretObject->getGuid()
        ) {
            $secret = $storedSecretObject->getSecret();
        } else {
            throw new TwoFactorInvalidCodeException();
        }

        if (!$this->twoFactorService->verifyCode(
            secret: $secret,
            code: $code,
            discrepancy: 1, // 30 second interval.
            // set current slice to 1 as code library isn't designed to handle
            // codes that expire after a day. Code expiry handled above.
            currentTimeSlice: 1
        )) {
            throw new TwoFactorInvalidCodeException();
        }

        // Trust a user if they have given a valid two-factor code.
        if (!$user->isTrusted()) {
            $this->emailConfirmation->approveConfirmation($user);
        }

        $this->twoFactorSecretStore->delete($key);
    }

    /**
     * Gets 2FA key header if present.
     * @return string - 2fa key header.
     */
    private function get2FAKeyHeader(): string
    {
        $request = ServerRequestFactory::fromGlobals();
        return $request->getHeader('X-MINDS-EMAIL-2FA-KEY')[0] ?? '';
    }

    /**
     * Sends an email with code.
     * @param User $user - user to send to.
     * @param string $code - code to send.
     * @return void
     */
    private function sendEmail(User $user, string $code): void
    {
        $this->twoFactorEmail->setUser($user);
        $this->twoFactorEmail->setCode($code);
        $this->twoFactorEmail->send();
    }
}
