<?php

namespace Minds\Core\AccountQuality;

use Minds\Common\Repository\Response;

interface ManagerInterface
{
    /**
     * Retrieves the account quality score based on the userId provided
     * @param string $userId
     * @return Response
     */
    public function getAccountQualityScore(string $userId): Response;

    /**
     * Retrieves the account quality score based on the userId provided as a float.
     * @param string $userId - id of the user to get the score for.
     * @return float value of account quality score.
     */
    public function getAccountQualityScoreAsFloat(string $userId): float;
}
