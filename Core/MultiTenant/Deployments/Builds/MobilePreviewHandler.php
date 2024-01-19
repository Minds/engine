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
     * @param int $tenantId
     * @return void
     * @throws GuzzleException
     * @throws Exception
     */
    public function triggerPipeline(?int $tenantId = null): void
    {
        $response = $this->httpClient->post(
            uri: '',
            options: [
                "form_params" => [
                    "token" => $this->config->get("gitlab")["mobile"]["pipeline"]["trigger_token"],
                    "ref" => $this->config->get("gitlab")["mobile"]["pipeline"]["branch"],
                    "variables[BUILD_MODE]" => self::BUILD_MODE,
                    "variables[TENANT_ID]" => $tenantId ?? ($this->config->get("tenant_id") ?? -1),
                    "variables[WEBHOOK_URL]" => "",
                ]
            ]
        );

        if ($response->getStatusCode() !== 200) {
            throw new Exception("Failed to request mobile preview");
        }

        //TODO: log request
    }
}
