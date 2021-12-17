<?php

namespace Minds\Core\Recommendations\Algorithms;

use Minds\Common\Repository\Response;
use Minds\Core\Di\Di;
use Minds\Core\Suggestions\Manager as SuggestionsManager;
use Minds\Entities\User;

class SuggestedChannelsRecommendationsAlgorithm implements RecommendationsAlgorithmInterface
{
    /**
     * @type string
     */
    private const FRIENDLY_ALGORITHM_NAME = "suggested-channels";

    private ?User $user;

    public function __construct(
        private ?AlgorithmOptions $options = null,
        private ?SuggestionsManager $suggestionsManager = null
    ) {
        $this->options = $this->options ?? new AlgorithmOptions();
        $this->suggestionsManager = $this->suggestionsManager ?? Di::_()->get("Suggestions\Manager");
    }

    public function getFriendlyName(): string
    {
        return self::FRIENDLY_ALGORITHM_NAME;
    }

    public function getRecommendations(): Response
    {
        return $this->suggestionsManager?->getList();
    }

    public function setUser(?User $user): RecommendationsAlgorithmInterface
    {
        $this->user = $user;
        return $this;
    }
}
