<?php

namespace Minds\Core\AccountQuality;

interface RepositoryInterface
{
    /**
     * Retrieves the account quality score based on the userId provided
     * @param string $userId
     * @return float
     */
    public function getAccountQualityScore(string $userId): float;
}
