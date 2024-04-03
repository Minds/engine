<?php
namespace Minds\Core\Analytics\PostHog;

use Minds\Core\Data\cache\SharedCache;
use Minds\Entities\User;
use PostHog\Client;
use Psr\Http\Message\ServerRequestInterface;
use WebSocket\Server;
use Zend\Diactoros\ServerRequestFactory;

class PostHogService
{
    private ?User $user;
    private ?ServerRequestInterface $serverRequest;

    public function __construct(
        private Client $postHogClient,
        private PostHogConfig $postHogConfig,
        private SharedCache $cache,
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
     * Provides the current user that should be identified on PostHog with
     */
    public function withUser(?User $user): PostHogService
    {
        $instance = clone $this;
        $instance->user = $user;
        return $instance;
    }

    /**
     * Captures a PostHog event
     */
    public function capture(array $data = []): bool
    {
        $data['$set'] = [
            'username' => $this->user->getUsername(),
        ];

        if ($this->user->getPlusExpires()) {
            $data['$set']['plus_expires'] = date('c', $this->user->getPlusExpires());
        }

        if ($this->user->getProExpires()) {
            $data['$set']['pro_expires'] = date('c', $this->user->getProExpires());
        }

        $data['$set_once'] = [
            'joined_timestamp' => date('c', $this->user->time_created),
        ];
        
        /**
         * If the browser sends the page the api was on when called,
         * we can supply this to posthog
         */
        if ($referrerUrl = $this->getServerRequestHeader('Referer')) {
            $urlParts = parse_url($referrerUrl[0]);
            $data['properties']['$current_url'] = $referrerUrl[0];
            $data['properties']['$pathname'] = $urlParts['path'];
            $data['properties']['$host'] = $urlParts['host'];
        }

        /**
         * Our reverse proxy will provide us with the real IP
         */
        if ($xForwardedFor = $this->getServerRequestHeader('X-Forwarded-For')) {
            $data['properties']['$ip'] = $xForwardedFor[0];
        }

        return $this->postHogClient->capture([
            'distinctId' => $this->user->getGuid(),
            ... $data
        ]);
    }

    /**
     * Returns feature flags for a given user
     */
    public function getFeatureFlags(bool $useCache = true): array
    {
        if ($useCache && $this->cache->has($this->getCacheKey())) {
            $this->postHogClient->featureFlags = $this->cache->get($this->getCacheKey());
        } else {
            $this->postHogClient->loadFlags();
            $this->cache->set($this->getCacheKey(), $this->postHogClient->featureFlags);
        }

        return $this->postHogClient->getAllFlags(
            distinctId: isset($this->user) ? $this->user->getGuid() : '',
            personProperties : [
                'environment' => getenv('MINDS_ENV') ?: 'development',
            ],
            onlyEvaluateLocally: true,
        );
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
