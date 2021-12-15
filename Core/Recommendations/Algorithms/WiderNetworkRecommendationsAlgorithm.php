<?php

namespace Minds\Core\Recommendations\Algorithms;

use Minds\Common\Repository\Response;
use Minds\Entities\User;

class WiderNetworkRecommendationsAlgorithm implements RecommendationsAlgorithmInterface
{
    /**
     * @type string
     */
    private const FRIENDLY_ALGORITHM_NAME = "wider-network";
    private ?User $user;

    public function setUser(?User $user): RecommendationsAlgorithmInterface
    {
        $this->user = $user;
        return $this;
    }

    public function getFriendlyName(): string
    {
        return self::FRIENDLY_ALGORITHM_NAME;
    }

    public function getRecommendations(): Response
    {
    }
}
