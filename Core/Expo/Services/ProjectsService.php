<?php
declare(strict_types=1);

namespace Minds\Core\Expo\Services;

use Minds\Core\Config\Config;
use Minds\Core\Expo\Clients\ExpoHttpClient;
use Minds\Core\Expo\ExpoConfig;
use Minds\Core\MultiTenant\Configs\Manager as MultiTenantConfigsManager;
use Minds\Exceptions\ServerErrorException;

/**
 * Service for interacting with and creating Expo projects.
 */
class ProjectsService
{
    public function __construct(
        private ExpoHttpClient $expoHttpClient,
        private ExpoConfig $expoConfig,
        private Config $config,
        private MultiTenantConfigsManager $multiTenantConfigsManager
    ) {
    }

    /**
     * Creates a new Expo project for the tenant.
     * @param string|null $displayName - display name for the project.
     * @param string|null $slug - slug for the project.
     * @param string|null $privacy - privacy setting of the project e.g. 'unlisted'.
     * @return bool - true on success.
     */
    public function newProject(
        ?string $displayName = null,
        ?string $slug = null,
        ?string $privacy = null
    ): bool {
        $tenantId = $this->config->get('tenant_id') ??
            throw new ServerErrorException('No tenant id set. Ensure that you are on a tenant domain.');

        $multiTenantConfigs = $this->multiTenantConfigsManager->getConfigs();
        
        if ($multiTenantConfigs?->expoProjectId) {
            throw new ServerErrorException('Tenant is already linked to an Expo project with the ID: ' . $multiTenantConfigs->expoProjectId);
        }

        if (!$slug) {
            $slug = $this->buildSlug((string) $tenantId);
        }

        if (!$displayName) {
            $displayName = $this->config->get('site_name');
        }
        
        if (!$privacy) {
            $privacy = 'unlisted';
        }

        $response = $this->expoHttpClient->request('POST', $this->expoHttpClient::V2_PROJECTS_PATH, [
            'accountName' => $this->expoConfig->accountName,
            'appInfo' => [
                'displayName' => $displayName,
            ],
            'privacy' => $privacy,
            'projectName' => $slug
        ]);

        if (!$response || !$projectId = $response['id']) {
            throw new ServerErrorException('An error occurred when calling the Expo API');
        }

        $this->multiTenantConfigsManager->upsertConfigs(
            expoProjectId: $projectId
        );

        return (bool) $response;
    }

    /**
     * Builds the slug for the project.
     * @param string $tenantId - the tenant id.
     * @return string - the slug.
     */
    private function buildSlug(string $tenantId): string
    {
        return "tenant-$tenantId";
    }
}
