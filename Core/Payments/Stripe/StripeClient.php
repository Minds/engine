<?php
namespace Minds\Core\Payments\Stripe;

use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\Sessions\ActiveSession;
use Minds\Entities\User;
use Stripe;

/**
 * Wrapper around the Stripe Client that enables us to have
 * test-mode access for authorized users.
 */
class StripeClient extends Stripe\StripeClient
{
    public function __construct(
        $config = [], // Stripe provided
        protected ?Config $mindsConfig = null,
        protected ?ActiveSession $activeSession = null
    ) {
        $this->mindsConfig ??= Di::_()->get('Config');
        $this->activeSession ??= Di::_()->get('Sessions\ActiveSession');

        if (!isset($config['api_key'])) {
            $config['api_key'] = $this->getApiKeyFromConfig();
        }

        parent::__construct($config);
    }

    /**
     * Construct a new client instance for a given user - if given
     * user is a test mode user, will return a client targeting test-mode.
     * @param User|null $user - user to get client instance for.
     * @param array $config - extra config to pass.
     * @return self
     */
    public function withUser(?User $user = null, array $config = []): self
    {
        if (!isset($config['api_key'])) {
            $config['api_key'] = $this->getApiKeyFromConfig($user);
        }
        return new self($config);
    }

    /**
     * Whether a test key should be used based on whether the user is authorized.
     * @param User|null $user - user to get mode for.
     * @param string $testEmailSuffix - suffix for test email to be used in regex.
     * @return boolean true if a test key should be used.
     */
    private function shouldUseTestMode(?User $user = null, $stripeConfig = []): bool
    {
        if (!$user) {
            $user = $this->activeSession->getUser();
        }

        $testEmailRegex = '/^[A-Za-z]+\+'.$stripeConfig['test_email'].'$/';

        return $user &&
            preg_match($testEmailRegex, $user->getEmail()) &&
            $user->isEmailConfirmed(); // Note this is not isTrusted as we want to require it is fully confirmed.
    }

    /**
     * Gets API key from config based on whether user is a test-mode user.
     * @param User|null $user - user.
     * @return string api key from config.
     */
    private function getApiKeyFromConfig(?User $user = null): string
    {
        $stripeConfig = $this->mindsConfig->get('payments')['stripe'];

        return $this->shouldUseTestMode($user, $stripeConfig) ?
            $stripeConfig['test_api_key'] :
            $stripeConfig['api_key'];
    }
}
