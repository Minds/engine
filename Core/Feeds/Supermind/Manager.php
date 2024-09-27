<?php

declare(strict_types=1);

namespace Minds\Core\Feeds\Supermind;

use Minds\Common\Repository\Response;
use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\Feeds\Elastic\Manager as ElasticSearchManager;

class Manager
{
    public function __construct(
        private ?ElasticSearchManager $elasticSearchManager = null,
        private ?Config $config = null,
    ) {
        $this->elasticSearchManager ??= Di::_()->get('Feeds\Elastic\Manager');
        $this->config ??= Di::_()->get('Config');
    }

    /**
     * @param int $limit
     * @return Response
     * @throws \Exception
     */
    public function getSupermindActivities(int $limit = 12): Response
    {
        return $this->elasticSearchManager->getList([
            'limit' => $limit,
            'type' => 'activity',
            'algorithm' => 'latest',
            'single_owner_threshold' => 0,
            'period' => 'all', // legacy option
            'to_timestamp' => null,
            'from_timestamp' => null,
            'supermind' => true,
            'exclude_owner_guids' => $this->getExcludedUserGuids(),
        ]);
    }

    /**
     * Gets an array of user guids that should be excluded from
     * global supermind feeds. This is useful for omitting test
     * users in production.
     * @return array<string>
     */
    private function getExcludedUserGuids(): array
    {
        return $this->config->get('supermind')['excluded_user_guids'] ?? [];
    }
}
