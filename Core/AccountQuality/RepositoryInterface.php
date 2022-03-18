<?php

namespace Minds\Core\AccountQuality;

use Minds\Core\AccountQuality\Models\UserQualityScore;

interface RepositoryInterface
{
    /**
     * Retrieves the account quality score based on the userId provided
     * @param string $userId
     * @return UserQualityScore
     */
    public function getAccountQualityScore(string $userId): UserQualityScore;
}
