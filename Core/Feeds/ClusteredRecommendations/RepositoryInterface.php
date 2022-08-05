<?php

namespace Minds\Core\Feeds\ClusteredRecommendations;

use Generator;

/**
 * Interface representing a clustered recommendations' repository required methods
 */
interface RepositoryInterface
{
    public function getList(int $clusterId, int $limit, array $exclude = [], bool $demote = false, ?string $pseudoId = null): Generator;
}
