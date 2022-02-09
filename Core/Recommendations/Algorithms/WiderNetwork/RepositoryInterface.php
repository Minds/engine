<?php

namespace Minds\Core\Recommendations\Algorithms\WiderNetwork;

use Generator;
use Minds\Core\Suggestions\Suggestion;

interface RepositoryInterface
{
    /**
     * Returns a list of entities
     * @param array|null $options
     * @return Generator|Suggestion[]
     */
    public function getList(?array $options = null): Generator;
}
