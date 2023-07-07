<?php
declare(strict_types=1);

namespace Minds\Core\Payments\Stripe;

use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\Sessions\ActiveSession;
use Minds\Entities\User;

/**
 * Stripe API key config class. Allows getting of API key for given user or no user.
 */
class StripeApiKeyConfig
{
    public function __construct(
        protected ?Config $config = null,
        protected ?ActiveSession $activeSession = null
    ) {
        $this->config ??= Di::_()->get('Config');
        $this->activeSession ??= Di::_()->get('Sessions\ActiveSession');
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

        return $this->shouldUseTestMode($user, $stripeConfig) ?
            $stripeConfig['test_api_key'] :
            $stripeConfig['api_key'];
    }

    /**
     * Whether a test key should be used based on whether the user is authorized.
     * @param User|null $user - user to get mode for.
     * @param string $testEmailSuffix - suffix for test email to be used in regex.
     * @return boolean true if a test key should be used.
     */
    private function shouldUseTestMode(?User $user = null, $stripeConfig = []): bool
    {
        $testEmailRegex = '/^[A-Za-z]+\+'.$stripeConfig['test_email'].'$/';

        return $user &&
            $user->getEmail() &&
            preg_match($testEmailRegex, $user->getEmail()) &&
            $user->isEmailConfirmed(); // Note this is not isTrusted as we want to require it is fully confirmed.
    }
}
