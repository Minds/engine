<?php
declare(strict_types=1);

namespace Minds\Core\Http\Cloudflare;

use Exception;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\GuzzleException;
use Minds\Core\MultiTenant\Enums\CustomHostnameStatusEnum;
use Minds\Core\MultiTenant\Types\CustomHostname;
use Minds\Core\MultiTenant\Types\CustomHostnameMetadata;
use Psr\Http\Message\ResponseInterface;

class Client
{
    public function __construct(
        private readonly HttpClient $client
    ) {
    }

    /**
     * @param string $hostname
     * @param int $tenantId
     * @return CustomHostname
     * @throws GuzzleException
     * @throws Exception
     */
    public function createCustomHostname(string $hostname, int $tenantId): CustomHostname
    {
        $response = $this->client->post(
            uri: "custom_hostnames",
            options: [
                "json" => [
                    'hostname' => $hostname,
                    'ssl' => [
                        'certificate_authority' => 'google',
                        'method' => 'http',
                        'type' => 'dv'
                    ]
                ]
            ]
        );

        return $this->processCustomHostnamePayload($response);
    }

    /**
     * @param string $cloudflareId
     * @return CustomHostname
     * @throws GuzzleException
     * @throws Exception
     */
    public function getCustomHostnameDetails(string $cloudflareId): CustomHostname
    {
        $response = $this->client->get(
            uri: "custom_hostnames/$cloudflareId",
        );

        return $this->processCustomHostnamePayload($response);
    }

    /**
     * @param string $cloudflareId
     * @param string $hostname
     * @param int $tenantId
     * @return CustomHostname
     * @throws GuzzleException
     */
    public function updateCustomHostnameDetails(
        string $cloudflareId,
        string $hostname,
        int $tenantId
    ): CustomHostname {
        // Delete existing custom hostname first
        $this->deleteCustomHostname($cloudflareId);

        return $this->createCustomHostname(
            hostname: $hostname,
            tenantId: $tenantId
        );
    }

    /**
     * @param string $cloudflareId
     * @return void
     * @throws GuzzleException
     * @throws Exception
     */
    private function deleteCustomHostname(string $cloudflareId): void
    {
        $response = $this->client->delete(
            uri: "custom_hostnames/$cloudflareId",
        );

        if ($response->getStatusCode() !== 200) {
            throw new Exception("Failed to delete custom hostname");
        }
    }

    /**
     * @param ResponseInterface $response
     * @return CustomHostname
     * @throws Exception
     */
    private function processCustomHostnamePayload(ResponseInterface $response): CustomHostname
    {
        if ($response->getStatusCode() !== 201) {
            throw new Exception("Failed to create custom hostname");
        }

        $payload = json_decode($response->getBody()->getContents());

        if (!$payload->success) {
            throw new Exception("Failed to create custom hostname");
        }

        return new CustomHostname(
            id: $payload->result->id,
            hostname: $payload->result->hostname,
            customOriginServer: $payload->result->custom_origin_server ?? "",
            status: CustomHostnameStatusEnum::tryFrom($payload->result->status),
            metadata: new CustomHostnameMetadata($payload->result->custom_metadata ?? []),
            createdAt: strtotime($payload->result->created_at)
        );
    }
}
