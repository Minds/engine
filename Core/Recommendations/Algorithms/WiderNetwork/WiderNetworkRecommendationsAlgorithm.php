<?php

namespace Minds\Core\Recommendations\Algorithms\WiderNetwork;

use Minds\Common\Repository\Response;
use Minds\Core\Recommendations\Algorithms\AbstractRecommendationsAlgorithm;
use Minds\Core\Recommendations\Algorithms\AlgorithmOptions;
use Minds\Core\Recommendations\Algorithms\RecommendationsAlgorithmInterface;
use Minds\Core\Recommendations\Repository;
use Minds\Core\Recommendations\RepositoryInterface;
use Minds\Entities\User;

/**
 * Recommendations algorithm to retrieve suggested channels for the logged-in user
 */
class WiderNetworkRecommendationsAlgorithm extends AbstractRecommendationsAlgorithm
{
    /**
     * @type string
     */
    protected const FRIENDLY_ALGORITHM_NAME = "wider-network";
    protected ?User $user;

    public function __construct(
        private ?AlgorithmOptions $options = null,
        private ?RepositoryInterface $repository = null
    ) {
        $this->options = $this->options ?? new AlgorithmOptions();
        $this->repository = $this->repository ?? new Repository();
    }

    /**
     * Sets the user to use for the recommendations algorithm
     * @param User|null $user
     * @return RecommendationsAlgorithmInterface
     */
    public function setUser(?User $user): RecommendationsAlgorithmInterface
    {
        $this->user = $user;

        $this->options->setUserGuid($this->user->getGuid());

        return $this;
    }

    /**
     * Set the options to use for the algorithm
     * @param AlgorithmOptions $options
     * @return $this
     */
    public function setOptions(AlgorithmOptions $options): self
    {
        $this->options = $options;
        return $this;
    }

    /**
     * Returns the list of recommendations based on the current recommendation's algorithm
     * @return Response
     */
    public function getRecommendations(): Response
    {
        return $this->repository?->getList($this->options->toArray());
    }

    public function getDiscoveryForYouFeed(?AlgorithmOptions $options = null): Response
    {
        $results = $this->repository?->getList($this->options->toArray());
        return new Response([]);
    }
}
