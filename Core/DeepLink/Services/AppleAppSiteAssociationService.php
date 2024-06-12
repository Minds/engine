<?php
declare(strict_types=1);

namespace Minds\Core\DeepLink\Services;

use Minds\Core\MultiTenant\MobileConfigs\Services\MobileConfigReaderService;
use Minds\Exceptions\ServerErrorException;

/**
 * Service for the generation of an apple app site association file.
 */
class AppleAppSiteAssociationService
{
    public function __construct(
        private readonly MobileConfigReaderService $mobileConfigReaderService
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

        if (!($appleDevelopmentTeamId = $configs->appleDevelopmentTeamId)) {
            throw new ServerErrorException("Apple development team ID is not set");
        }

        return [
            'activitycontinuation' => [
                    "apps" => [
                            "$appleDevelopmentTeamId.com.minds.mobile",
                            "$appleDevelopmentTeamId.com.minds.chat"
                    ]
            ],
            'applinks' => [
                    'apps' => [],
                    'details' => [
                            [
                                    'appID' => "$appleDevelopmentTeamId.com.minds.mobile",
                                    'paths' => [
                                            'NOT /api/*',
                                            'NOT /register',
                                            'NOT /login',
                                            '/*'
                                    ]
                            ],
                            [
                                    'appID' => "$appleDevelopmentTeamId.com.minds.chat",
                                    'paths' => ['/*']
                            ]
                    ]
            ],
            'webcredentials' => [
                    'apps' => [
                            "$appleDevelopmentTeamId.com.minds.mobile",
                    ]
            ],
        ];
    }
}
