<?php

namespace Minds\Core\Feeds\ClusteredRecommendations;

/**
 *
 */
class RepositoryFactory
{
    public function getInstance(string $type): RepositoryInterface
    {
        return match ($type) {
            MySQLRepository::class => new MySQLRepository(),
            default => new ElasticSearchRepository()
        };
    }
}
