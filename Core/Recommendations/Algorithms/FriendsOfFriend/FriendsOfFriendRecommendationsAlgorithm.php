<?php

namespace Minds\Core\Recommendations\Algorithms\FriendsOfFriend;

use Minds\Common\Repository\Response;
use Minds\Core\Di\Di;
use Minds\Core\Recommendations\Algorithms\AbstractRecommendationsAlgorithm;
use Minds\Core\Recommendations\Algorithms\AlgorithmOptions;
use Minds\Core\Recommendations\RepositoryInterface;
use Minds\Core\Suggestions\Manager as SuggestionsManager;
use Minds\Entities\User;

class FriendsOfFriendRecommendationsAlgorithm extends AbstractRecommendationsAlgorithm
{
    /**
     * @type string
     */
    protected const FRIENDLY_ALGORITHM_NAME = "friends-of-friend";
    protected ?User $user;

    public function __construct(
        private ?AlgorithmOptions $options = null,
        private ?RepositoryInterface $repository = null,
        private ?SuggestionsManager $suggestionsManager = null
    ) {
        $this->options ??= new AlgorithmOptions();
        $this->repository ??= new Repository();
        $this->suggestionsManager ??= Di::_()->get("Suggestions\Manager");
    }

    /**
     * Returns the recommendations to display
     * @param array|null $options
     * @return Response
     */
    public function getRecommendations(?array $options = []): Response
    {
        $options['targetUserGuid'] ??= $this->user->getGuid();

        $result = $this->repository->getList($options);
        
        if ($result->count() == 0) {
            return $this->suggestionsManager?->setUser($this->user)->getList();
        }

        return $result;
    }
}
