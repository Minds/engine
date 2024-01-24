<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\MobileConfigs\Deployments\Builds;

use Exception;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\GuzzleException;
use Minds\Core\Config\Config;
use Minds\Core\MultiTenant\Services\MultiTenantBootService;

class MobilePreviewHandler
{
    private const BUILD_MODE = "PREVIEW";

    private const BRANCH = "ci-preview-backend";

    private const PIPELINE_TRIGGER_URL = 'https://gitlab.com/api/v4/projects/10171280/trigger/pipeline';

    public function __construct(
        private readonly HttpClient             $httpClient,
        private readonly Config                 $config,
        private readonly MultiTenantBootService $multiTenantBootService,
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

        $tenantId = $tenantId ?? $this->config->get("tenant_id");
        $this->multiTenantBootService->resetRootConfigs();
        $audience = $this->config->get("site_url");
        $this->multiTenantBootService->bootFromTenantId($tenantId);
        $response = $this->httpClient->post(
            uri: self::PIPELINE_TRIGGER_URL,
            options: [
                "form_params" => [
                    "token" => $pipelineConfig["trigger_token"],
                    "ref" => self::BRANCH,
                    "variables[BUILD_MODE]" => self::BUILD_MODE,
                    "variables[TENANT_ID]" => $tenantId ?? ($this->config->get("tenant_id") ?? -1),
                    "variables[WEBHOOK_URL]" => $this->config->get('site_url') . "/api/v3/multi-tenant/mobile-configs/update-preview",
                    "variables[GRAPHQL_URL]" => $this->config->get('site_url') . "/api/graphql",
                    "variables[AUDIENCE]" => $audience
                ]
            ]
        );

        if ($response->getStatusCode() !== 201) {
            throw new Exception("Failed to request mobile preview");
        }
    }
}
