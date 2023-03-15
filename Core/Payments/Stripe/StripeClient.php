<?php
declare(strict_types=1);

namespace Minds\Core\Payments\Stripe;

use Minds\Core\Di\Di;
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
        protected ?StripeApiKeyConfig $stripeApiKeyConfig = null
    ) {
        $this->stripeApiKeyConfig ??= Di::_()->get(StripeApiKeyConfig::class);

        if (!isset($config['api_key'])) {
            $config['api_key'] = $this->stripeApiKeyConfig->get();
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
            $config['api_key'] = $this->stripeApiKeyConfig->get($user);
        }
        return new self($config);
    }
}
