<?php
namespace Minds\Core\Analytics\PostHog;

use Minds\Core\Config\Config;
use Minds\Entities\User;

class PostHogConfig
{
    public function __construct(
        private Config $config
    ) {
        
    }

    /**
     * The api key that can read the feature flags from posthog
     */
    public function getPersonalApiKey(): string
    {
        return $this->config->get('posthog')['personal_api_key'] ?? '';
    }

    /**
     * The 'public' api key can will only accept writes
     */
    public function getApiKey(): string
    {
        return $this->config->get('posthog')['api_key'] ?? '';
    }

    /**
     * The host that we will send events too
     */
    public function getHost(): string
    {
        return $this->config->get('posthog')['host'] ?? '';
    }

    /**
     * The project id that the app is using
     */
    public function getProjectId(): string
    {
        return $this->config->get('posthog')['project_id'] ?? '';
    }

    /**
     * Configs that can be exported publicly
     */
    public function getPublicExport(User $user = null): array
    {
        return [
            'api_key' => $this->getApiKey(),
            'host' => "https://{$this->getHost()}",
            'opt_out' => $user?->isOptOutAnalytics(),
        ];
    }
}
