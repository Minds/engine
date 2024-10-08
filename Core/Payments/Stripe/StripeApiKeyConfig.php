<?php
declare(strict_types=1);

namespace Minds\Core\Payments\Stripe;

use Minds\Core\Config\Config;
use Minds\Core\Payments\Stripe\Exceptions\StripeNotConfiguredException;
use Minds\Core\Payments\Stripe\Keys\StripeKeysRepository;
use Minds\Core\Payments\Stripe\Keys\StripeKeysService;
use Minds\Core\Sessions\ActiveSession;
use Minds\Entities\User;

/**
 * Stripe API key config class. Allows getting of API key for given user or no user.
 */
class StripeApiKeyConfig
{
    public function __construct(
        protected Config $config,
        protected ActiveSession $activeSession,
        protected StripeKeysService $keysService,
    ) {
    }

    /**
     * Gets API key from config based on whether user is a test-mode user.
     * @param User|null $user - user - if null, will use session logged-in user.
     * @return string api key from config.
     */
    public function get(?User $user = null): string
    {
        if (!$user) {
            $user = $this->activeSession->getUser();
        }

        $stripeConfig = $this->config->get('payments')['stripe'];

        // Tenants will use the keystore, Minds.com is provided from env variable
        if ($this->config->get('tenant_id')) {
            $secKey = $this->keysService->getSecKey();
            if (!$secKey) {
                throw new StripeNotConfiguredException();
            }
            $stripeConfig['api_key'] = $secKey;
        }

        return $this->shouldUseTestMode($user) ?
            $stripeConfig['test_api_key'] :
            $stripeConfig['api_key'];
    }

    /**
     * Whether a test key should be used based on whether the user is authorized.
     * @param User|null $user - user to get mode for.
     * @param string $testEmailSuffix - suffix for test email to be used in regex.
     * @return boolean true if a test key should be used.
     */
    public function shouldUseTestMode(?User $user = null): bool
    {
        if (!$user) {
            $user = $this->activeSession->getUser();
        }

        $stripeConfig = $this->config->get('payments')['stripe'];
        $testEmailRegex = '/^[A-Za-z]+\+'.$stripeConfig['test_email'].'$/';

        return $user &&
            $user->getEmail() &&
            preg_match($testEmailRegex, $user->getEmail()) &&
            ($user->isEmailConfirmed() || $user->isAdmin()); // Note this is not isTrusted as we want to require it is fully confirmed.
    }
}
