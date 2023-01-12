<?php

declare(strict_types=1);

namespace Minds\Core\Boost\V3\Utils;

use Minds\Core\Boost\V3\Enums\BoostStatus;
use Minds\Core\Boost\V3\Enums\BoostTargetLocation;
use Minds\Core\Boost\V3\Models\Boost;
use Minds\Core\Config\Config;
use Minds\Core\Di\Di;

/**
 * Builds a URL to the client-side boost console with query params
 * to direct users to the correct feed for their boost, based upon
 * it's state and location.
 */
class BoostConsoleUrlBuilder
{
    public function __construct(private ?Config $config = null)
    {
        $this->config = $config ?? Di::_()->get('Config');
    }

    /**
     * Builds boost console URL.
     * @param Boost $boost - boost to build for.
     * @param array $extraQueryParams - extra query params to append.
     * @return string url for boost console.
     */
    public function build(Boost $boost, array $extraQueryParams = []): string
    {
        $baseUrl = $this->config->get('site_url');

        $queryParams = http_build_query(array_merge([
            'state' => $this->getBoostStateParamValue($boost->getStatus()),
            'location' => $this->getBoostLocationParamValue($boost->getTargetLocation())
        ], $extraQueryParams));

        return $baseUrl . 'boost/boost-console?' . $queryParams;
    }

    /**
     * Gets boost state query param value.
     * @return string boost state query param value.
     */
    private function getBoostStateParamValue($state): string
    {
        return match ($state) {
            BoostStatus::COMPLETED => 'completed',
            BoostStatus::APPROVED => 'approved',
            BoostStatus::PENDING => 'pending',
            default => '' // not yet implemented
        };
    }

    /**
     * Gets boost location query param value.
     * @return string boost location query param value.
     */
    private function getBoostLocationParamValue(int $location): string
    {
        return match ($location) {
            BoostTargetLocation::NEWSFEED => 'newsfeed',
            BoostTargetLocation::SIDEBAR => 'sidebar',
            default => '' // not yet implemented
        };
    }
}
