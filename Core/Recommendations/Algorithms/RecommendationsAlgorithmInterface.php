<?php

namespace Minds\Core\Recommendations\Algorithms;

use Minds\Common\Repository\Response;
use Minds\Entities\User;

interface RecommendationsAlgorithmInterface
{
    /**
     * Sets the user to use for the recommendations algorithm
     * @param User|null $user
     * @return RecommendationsAlgorithmInterface
     */
    public function setUser(?User $user): self;

    /**
     * Returns the algorithm user-friendly name
     * @return string
     */
    public function getFriendlyName(): string;

    /**
     * Returns the list of recommendations based on the current recommendation's algorithm
     * @return Response
     */
    public function getRecommendations(?array $options = []): Response;
}
