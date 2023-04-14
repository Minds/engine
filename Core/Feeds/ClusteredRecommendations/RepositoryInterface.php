<?php

namespace Minds\Core\Feeds\ClusteredRecommendations;

use Generator;
use Minds\Entities\User;

/**
 * Interface representing a clustered recommendations' repository required methods
 */
interface RepositoryInterface
{
    public function setUser(User $user): void;
    public function getList(int $clusterId, int $limit, array $exclude = [], bool $demote = false, ?string $pseudoId = null, ?array $tags = null): Generator;
}
