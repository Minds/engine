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
}
