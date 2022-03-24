<?php

namespace Minds\Core\AccountQuality;

use Minds\Core\AccountQuality\Models\UserQualityScore;

interface ManagerInterface
{
    /**
     * Retrieves the account quality score based on the userId provided
     * @param string $userId
     * @return UserQualityScore
     */
    public function getAccountQualityScore(string $userId): UserQualityScore;

    /**
     * Retrieves the account quality score based on the userId provided as a float.
     * @param string $userId - id of the user to get the score for.
     * @return float value of account quality score.
     */
    public function getAccountQualityScoreAsFloat(string $userId): float;
}
