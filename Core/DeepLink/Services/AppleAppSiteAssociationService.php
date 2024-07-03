<?php
declare(strict_types=1);

namespace Minds\Core\DeepLink\Services;

use Minds\Core\Config\Config;
use Minds\Core\MultiTenant\MobileConfigs\Services\MobileConfigReaderService;
use Minds\Exceptions\ServerErrorException;

/**
 * Service for the generation of an apple app site association file.
 */
class AppleAppSiteAssociationService
{
    public function __construct(
        private readonly MobileConfigReaderService $mobileConfigReaderService,
        private readonly Config $configs
    ) {
    }

    /**
     * Get the site association file.
     * @throws ServerErrorException If the apple development team ID is not set.
     * @return array The site association file.
     */
    public function get(): array
    {
        $configs = $this->mobileConfigReaderService->getMobileConfig();

        if (!($appleDevelopmentTeamId = $configs?->appleDevelopmentTeamId)) {
            throw new ServerErrorException("Apple development team ID is not set");
        }

        if (!($appIosBundle = $configs?->appIosBundle)) {
            throw new ServerErrorException("iOS bundle ID is not set");
        }

        $data = [
            'activitycontinuation' => [
                "apps" => [
                    "$appleDevelopmentTeamId.$appIosBundle"
                ]
            ],
            'applinks' => [
                'apps' => [],
                'details' => [
                    [
                        'appID' => "$appleDevelopmentTeamId.$appIosBundle",
                        'paths' => [
                            'NOT /api/*',
                            'NOT /register',
                            'NOT /login',
                            '/*'
                        ]
                    ]
                ]
            ],
            'webcredentials' => [
                'apps' => [
                    "$appleDevelopmentTeamId.$appIosBundle",
                ]
            ],
        ];

        // Add the Chat app for non-tenant domain.
        if (!$this->configs->get('tenant_id')) {
            $data['activitycontinuation']['apps'][] = "$appleDevelopmentTeamId.com.minds.chat";
            $data['applinks']['details'][] = [
                'appID' => "$appleDevelopmentTeamId.com.minds.chat",
                'paths' => ['/*']
            ];
        }

        return $data;
    }
}
