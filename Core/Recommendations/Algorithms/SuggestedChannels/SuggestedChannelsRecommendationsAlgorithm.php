<?php

namespace Minds\Core\Recommendations\Algorithms\SuggestedChannels;

use Minds\Common\Repository\Response;
use Minds\Core\Di\Di;
use Minds\Core\Recommendations\Algorithms\AbstractRecommendationsAlgorithm;
use Minds\Core\Recommendations\Algorithms\AlgorithmOptions;
use Minds\Core\Suggestions\Manager as SuggestionsManager;

/**
 * Recommendations algorithm to retrieve suggested channels for the logged-in user
 */
class SuggestedChannelsRecommendationsAlgorithm extends AbstractRecommendationsAlgorithm
{
    public function __construct(
        private ?AlgorithmOptions $options = null,
        private ?SuggestionsManager $suggestionsManager = null
    ) {
        $this->options = $this->options ?? new AlgorithmOptions();
        $this->suggestionsManager = $this->suggestionsManager ?? Di::_()->get("Suggestions\Manager");
    }

    /**
     * Returns the list of recommendations based on the current recommendation's algorithm
     * @return Response
     */
    public function getRecommendations(): Response
    {
        return $this->suggestionsManager?->getList();
    }
}
