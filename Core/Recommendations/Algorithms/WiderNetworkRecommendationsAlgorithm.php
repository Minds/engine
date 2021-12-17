<?php

namespace Minds\Core\Recommendations\Algorithms;

use Minds\Common\Repository\Response;
use Minds\Core\Recommendations\Repository;
use Minds\Core\Recommendations\RepositoryInterface;
use Minds\Entities\User;

class WiderNetworkRecommendationsAlgorithm implements RecommendationsAlgorithmInterface
{
    /**
     * @type string
     */
    private const FRIENDLY_ALGORITHM_NAME = "wider-network";
    private ?User $user;

    public function __construct(
        private ?AlgorithmOptions $options = null,
        private ?RepositoryInterface $repository = null
    ) {
        $this->options = $this->options ?? new AlgorithmOptions();
        $this->repository = $this->repository ?? new Repository();
    }

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
        return $this->repository?->getList();
    }
}
