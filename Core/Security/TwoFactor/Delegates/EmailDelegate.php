<?php

namespace Minds\Core\Security\TwoFactor\Delegates;

use Minds\Core\Di\Di;
use Minds\Core\Email\Confirmation\Manager as EmailConfirmationManager;
use Minds\Core\Email\V2\Campaigns\Recurring\TwoFactor\TwoFactor as TwoFactorEmail;
use Minds\Core\Log\Logger;
use Minds\Core\Security\TwoFactor as TwoFactorService;
use Minds\Core\Security\TwoFactor\Store\Cassandra\TwoFactorSecretCassandraStore;
use Minds\Core\Security\TwoFactor\Store\TwoFactorSecret;
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
        private ?TwoFactorService             $twoFactorService = null,
        private ?Logger                       $logger = null,
        private ?TwoFactorEmail               $twoFactorEmail = null,
        private ?TwoFactoSecretStoreInterface $twoFactorSecretStore = null,
        private ?EmailConfirmationManager     $emailConfirmation = null
    ) {
        $this->twoFactorService ??= new TwoFactorService();
        $this->logger ??= Di::_()->get('Logger');
        $this->twoFactorEmail ??= new TwoFactorEmail();
        $this->twoFactorSecretStore ??= new TwoFactorSecretCassandraStore();
        $this->emailConfirmation ??= Di::_()->get('Email\Confirmation');
    }

    /**
     * Should trigger the 2fa process
     * @param User $user
     * @throws TwoFactorRequiredException
     */
    public function onRequireTwoFactor(User $user): void
    {
        $hasResendHeader = $this->hasResendHeader();

        /** @var TwoFactorSecret */
        $storedSecretObject = $this->twoFactorSecretStore->get($user);
        $secret = $storedSecretObject && $storedSecretObject->getSecret() ? $storedSecretObject->getSecret() : '';

        // TODO: Rate limit here.
        if (!$secret || $hasResendHeader) {
            // Unless we are resending, generate a new secret.
            if (!$hasResendHeader) {
                $secret = $this->twoFactorService->createSecret();
            }

            $this->logger->info('2fa - sending Email to ' . $user->getGuid());

            $code = $this->twoFactorService->getCode(
                secret: $secret,
                timeSlice: 1
            );

            $this->sendEmail($user, $code);

            // create a lookup of a random key. The user can then use this key along side their two-factor code to login.
            $key = $this->twoFactorSecretStore->set($user, $secret);
        } else {
            $key = $this->twoFactorSecretStore->getKey($user);
        }
        
        // Set a header with the 2fa request id
        @header("X-MINDS-EMAIL-2FA-KEY: $key", true);

        //forward to the twofactor page
        throw new TwoFactorRequiredException();
    }

    /**
     * Called upon authentication when the twofactor code has been provided
     * @param User $user
     * @param int $code
     * @return void
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
     * Gets TTL for entry in store.
     * @param User $user - user to get TTL for.
     * @return int - ttl in seconds.
     */
    private function getTtl(User $user): int
    {
        return $this->twoFactorSecretStore->getTtl($user);
    }

    /**
     * Whether a resend is being requested by header.
     * @return bool true if resend it being requested.
     */
    private function hasResendHeader(): bool
    {
        $request = ServerRequestFactory::fromGlobals();
        $header = $request->getHeader('X-MINDS-EMAIL-2FA-RESEND')[0] ?? '';
        return $header === '1';
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
