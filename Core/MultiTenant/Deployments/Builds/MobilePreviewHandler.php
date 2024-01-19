<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Deployments\Builds;

use Exception;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\GuzzleException;
use Minds\Core\Config\Config;

class MobilePreviewHandler
{
    private const BUILD_MODE = "PREVIEW";
    private const WEBHOOK_URL = "";

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
                    "ref" => $pipelineConfig["branch"],
                    "variables[BUILD_MODE]" => self::BUILD_MODE,
                    "variables[TENANT_ID]" => $tenantId ?? ($this->config->get("tenant_id") ?? -1),
                    "variables[WEBHOOK_URL]" => $pipelineConfig["webhook_url"],
                    "variables[GRAPHQL_URL]" => $pipelineConfig["graphql_url"],
                ]
            ]
        );

        if ($response->getStatusCode() !== 201) {
            throw new Exception("Failed to request mobile preview");
        }

        //TODO: log request
    }
}
