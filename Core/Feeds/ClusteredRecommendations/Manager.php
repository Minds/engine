<?php

namespace Minds\Core\Feeds\ClusteredRecommendations;

use Minds\Core\Data\ElasticSearch\Client as ElasticSearchClient;
use Minds\Core\Di\Di;

class Manager
{
    public function __construct(
        private ?Repository $repository = null
    ) {
        $this->repository ??= new Repository();
    }

    public function getList(int $limit, int $offset): array
    {
    }
}
