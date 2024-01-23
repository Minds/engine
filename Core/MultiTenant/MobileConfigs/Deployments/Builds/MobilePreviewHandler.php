<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\MobileConfigs\Deployments\Builds;

use Exception;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\GuzzleException;
use Minds\Core\Config\Config;

class MobilePreviewHandler
{
    private const BUILD_MODE = "PREVIEW";

    private const BRANCH = "master";

    public function __construct(
        private readonly HttpClient $httpClient,
        private readonly Config     $config,
    ) {
    }

    /**
     * @param int|null $tenantId
     * @return void
     * @throws GuzzleException
     * @throws Exception
     */
    public function triggerPipeline(?int $tenantId = null): void
    {
        $pipelineConfig = $this->config->get("gitlab")["mobile"]["pipeline"];
        $response = $this->httpClient->post(
            uri: '',
            options: [
                "form_params" => [
                    "token" => $pipelineConfig["trigger_token"],
                    "ref" => self::BRANCH,
                    "variables[BUILD_MODE]" => self::BUILD_MODE,
                    "variables[TENANT_ID]" => $tenantId ?? ($this->config->get("tenant_id") ?? -1),
                    "variables[WEBHOOK_URL]" => $this->config->get('site_url') . "/api/v3/multi-tenant/mobile-configs/update-preview",
                    "variables[GRAPHQL_URL]" => $this->config->get('site_url') . "/api/graphql"
                ]
            ]
        );

        if ($response->getStatusCode() !== 201) {
            throw new Exception("Failed to request mobile preview");
        }
    }
}
