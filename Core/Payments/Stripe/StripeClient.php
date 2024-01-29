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
class StripeClient
{
    private ?User $user;

    /**
     * @var \Stripe\Service\CoreServiceFactory
     */
    private $coreServiceFactory;

    public function __construct(
        private $config = [], // Stripe provided
        protected ?StripeApiKeyConfig $stripeApiKeyConfig = null
    ) {
        $this->stripeApiKeyConfig ??= Di::_()->get(StripeApiKeyConfig::class);
    }

    /**
     * Construct a new client instance for a given user - if given
     * user is a test mode user, client will will target test-mode.
     * @param User|null $user - user to get client instance for.
     * @param array $config - extra config to pass.
     * @return self
     */
    public function withUser(?User $user = null, array $config = []): self
    {
        $instance = clone $this;
        $instance->config = $config;
        if (!isset($instance->config['api_key'])) {
            $instance->user = $user;
        }
        return $instance;
    }

    /**
     * Intercept all properties and override with our stripe client
     */
    public function __get($name)
    {
        if (null === $this->coreServiceFactory) {
            $this->coreServiceFactory = new \Stripe\Service\CoreServiceFactory($this->buildStripeClient());
        }

        return $this->coreServiceFactory->__get($name);
    }

    /**
     * Calls the StripeClient->requestSearchResult
     */
    public function requestSearchResult($method, $path, $params, $opts)
    {
        return $this->buildStripeClient()->requestSearchResult($method, $path, $params, $opts);
    }

    /**
     * Provide our configs to the StripeClient
     */
    private function buildStripeClient(): Stripe\StripeClient
    {
        if (!isset($this->config['api_key'])) {

            if (isset($this->user)) {
                $this->config['api_key'] = $this->stripeApiKeyConfig->get($this->user);
            }

            $this->config['api_key'] = $this->stripeApiKeyConfig->get();
        }
    
        $className = "Stripe\StripeClient";
        return new $className($this->config);
    }
}
