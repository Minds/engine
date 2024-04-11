<?php
namespace Minds\Core\Analytics\PostHog;

use Minds\Core\Config\Config;
use Minds\Core\Data\cache\SharedCache;
use Minds\Entities\Enums\FederatedEntitySourcesEnum;
use Minds\Entities\User;
use PostHog\Client;
use Psr\Http\Message\ServerRequestInterface;
use WebSocket\Server;
use Zend\Diactoros\ServerRequestFactory;

class PostHogService
{
    private ?ServerRequestInterface $serverRequest;

    public function __construct(
        private Client $postHogClient,
        private PostHogConfig $postHogConfig,
        private SharedCache $cache,
        private Config $config,
    ) {
    }

    /**
     * Provides the request a user made
     * (optional)
     */
    public function withServerRequest(ServerRequestInterface $request): PostHogService
    {
        $instance = clone $this;
        $instance->serverRequest = $request;
        return $instance;
    }

    /**
     * Captures a PostHog event
     */
    public function capture(
        string $event,
        User $user,
        array $properties = [],
        array $set = [],
        array $setOnce = []
    ): bool {

        // If a user has opted out of analytics, we will not process the event
        if ($user->isOptOutAnalytics()) {
            return false;
        }

        // We only want to have events from real users (not activity pub)
        if ($user->getSource() !== FederatedEntitySourcesEnum::LOCAL) {
            return false;
        }

        $set['guid'] = $user->getGuid();
        $set['username'] = $user->getUsername();
        $set['email'] = $user->getEmail();

        if ($user->getPlusExpires()) {
            $set['plus_expires'] = date('c', $user->getPlusExpires());
        }

        if ($user->getProExpires()) {
            $set['pro_expires'] = date('c', $user->getProExpires());
        }

        $setOnce['joined_timestamp'] = date('c', $user->time_created);

        if ($tenantId = $this->config->get('tenant_id')) {
            $setOnce['tenant_id'] = $tenantId;
            $properties['tenant_id'] = $tenantId;
        }

        /**
         * If the browser sends the page the api was on when called,
         * we can supply this to posthog
         */
        if ($referrerUrl = $this->getServerRequestHeader('Referer')) {
            $urlParts = parse_url($referrerUrl[0]);
            $properties['$current_url'] = $referrerUrl[0];
            $properties['$pathname'] = $urlParts['path'];
            $properties['$host'] = $urlParts['host'];
        }

        /**
         * Our reverse proxy will provide us with the real IP
         */
        if ($xForwardedFor = $this->getServerRequestHeader('X-Forwarded-For')) {
            $properties['$ip'] = $xForwardedFor[0];
        }

        /**
         * Information about our environment
         */
        $properties['environment'] = $set['environment'] = $this->getEnvironment();

        $success = $this->postHogClient->capture([
            'event' => $event,
            'distinctId' => $user->getGuid(),
            'properties' => [
                ...$properties,
                '$set' => $set,
                '$set_once' => $setOnce,
            ]
        ]);

        // Sends the request
        return $success && $this->postHogClient->flush();
    }

    /**
     * Returns feature flags for a given user
     */
    public function getFeatureFlags(
        User $user = null,
        bool $useCache = true
    ): array {
        if ($useCache && $this->cache->has($this->getCacheKey())) {
            $this->postHogClient->featureFlags = $this->cache->get($this->getCacheKey());
        } else {
            if (!$this->postHogConfig->getPersonalApiKey()) {
                // Personal API Key is not setup, we can't load any feature flags
                return [];
            }

            $this->postHogClient->loadFlags();
            $this->cache->set($this->getCacheKey(), $this->postHogClient->featureFlags);
        }

        return $this->postHogClient->getAllFlags(
            distinctId: isset($user) ? $user->getGuid() : '',
            personProperties : [
                'environment' => $this->getEnvironment(),
            ],
            onlyEvaluateLocally: true,
        );
    }

    /**
     * The environment php is running in (eg. staging)
     */
    private function getEnvironment(): string
    {
        return getenv('MINDS_ENV') ?: 'development';
    }

    /**
     * @return string[]
     */
    private function getServerRequestHeader(string $header): array
    {
        $serverRequest = $this->serverRequest ?? ServerRequestFactory::fromGlobals();
        return $serverRequest->getHeader($header);
    }

    /**
     * We return a cache key for each posthog api key
     */
    private function getCacheKey(): string
    {
        return 'posthog:feature-flags:' . $this->postHogConfig->getApiKey();
    }
}
