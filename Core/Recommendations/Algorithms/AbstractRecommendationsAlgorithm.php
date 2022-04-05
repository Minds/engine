<?php

namespace Minds\Core\Recommendations\Algorithms;

use Minds\Entities\User;

/**
 * Base class for recommendations algorithms
 */
abstract class AbstractRecommendationsAlgorithm implements RecommendationsAlgorithmInterface
{
    /**
     * @type string
     */
    protected const FRIENDLY_ALGORITHM_NAME = "suggested-channels";

    protected ?User $user;

    /**
     * Returns the algorithm user-friendly name
     * @return string
     */
    public function getFriendlyName(): string
    {
        return static::FRIENDLY_ALGORITHM_NAME;
    }

    /**
     * Sets the user to use for the recommendations algorithm
     * @param User|null $user
     * @return RecommendationsAlgorithmInterface
     */
    public function setUser(?User $user): RecommendationsAlgorithmInterface
    {
        $this->user = $user;
        return $this;
    }
}
