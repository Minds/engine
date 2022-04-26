<?php

namespace Minds\Core\Recommendations;

use Exception;
use Minds\Core\Di\Di;
use Minds\Core\Hashtags\User\Manager as UserHashtagsManager;
use Minds\Entities\User;

/**
 * Performs calculations to retrieve the recommendations cluster id for a user
 */
class UserRecommendationsCluster
{
    /**
     * @var string[]
     */
    private const TAG_LIST = [
        'art', // true
        'blockchain',
        'blog',
        'comedy',
        'crypto',
        'education',
        'fashion',
        'film',
        'food',
        'freespeech',
        'gaming',
        'health',
        'history',
        'journalism',
        'memes',
        'minds',
        'mindsth',
        'music', // true
        'myphoto',
        'nature',
        'news',
        'nutrition',
        'outdoors',
        'photography', // true
        'poetry',
        'politics',
        'science',
        'spirituality',
        'sports',
        'technology',
        'travel',
        'videos'
    ];

    /**
     * Each record in `medoids` is a boolean vector of tags selected from the `tag_list` above
     * Each record represents the medoid of one of 20 clusters of user tag selections
     *
     * @var float[][]
     */
    private const MEDOIDS = [
        [1.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 1.0, 0.0, 0.0, 0.0, 1.0, 0.0, 1.0, 1.0, 0.0, 1.0, 1.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 1.0, 0.0],
        [0.0, 1.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 1.0, 0.0, 0.0],
        [1.0, 1.0, 0.0, 0.0, 1.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 1.0, 0.0, 1.0, 0.0, 1.0, 1.0, 0.0, 1.0, 0.0, 0.0, 1.0, 1.0, 0.0, 0.0, 0.0, 0.0, 1.0, 1.0, 0.0],
        [0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 1.0, 1.0, 0.0, 0.0, 0.0, 0.0, 1.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0],
        [1.0, 0.0, 0.0, 0.0, 1.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 1.0, 0.0, 1.0, 1.0, 1.0, 0.0, 0.0, 1.0, 1.0, 1.0, 0.0, 0.0, 0.0, 1.0, 0.0, 1.0, 0.0],
        [0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0],
        [0.0, 0.0, 0.0, 0.0, 1.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 1.0, 0.0, 1.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 1.0, 0.0, 1.0, 0.0],
        [1.0, 1.0, 0.0, 0.0, 1.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 1.0, 0.0, 1.0, 0.0, 1.0, 1.0, 1.0, 1.0, 0.0, 1.0, 0.0, 1.0, 0.0, 0.0, 0.0, 1.0, 0.0, 1.0, 0.0],
        [1.0, 1.0, 0.0, 0.0, 1.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 1.0, 0.0, 1.0, 0.0, 1.0, 1.0, 0.0, 0.0, 0.0, 0.0, 1.0, 1.0, 0.0, 0.0, 0.0, 1.0, 1.0, 0.0, 0.0],
        [0.0, 0.0, 0.0, 0.0, 1.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 1.0, 0.0, 1.0, 0.0, 1.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0],
        [0.0, 1.0, 1.0, 0.0, 1.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 1.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 1.0, 0.0, 0.0, 0.0, 0.0, 1.0, 0.0, 1.0, 0.0, 0.0],
        [0.0, 1.0, 0.0, 0.0, 1.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 1.0, 0.0, 1.0, 0.0, 1.0, 1.0, 0.0, 1.0, 0.0, 0.0, 1.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 1.0, 0.0],
        [0.0, 0.0, 0.0, 0.0, 1.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 1.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 1.0, 0.0, 0.0],
        [0.0, 1.0, 0.0, 0.0, 1.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 1.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0],
        [0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 1.0, 1.0, 0.0],
        [1.0, 1.0, 0.0, 0.0, 1.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 1.0, 0.0, 1.0, 0.0, 1.0, 1.0, 1.0, 1.0, 0.0, 1.0, 1.0, 1.0, 0.0, 0.0, 0.0, 1.0, 1.0, 1.0, 0.0],
        [1.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0],
        [0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 1.0, 0.0, 0.0],
        [0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 1.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0],
        [0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 1.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0]
    ];

    /**
     * `means` is the variance of the per-tag coefficient values from logistic regression of engagement
     * across all documents that are regressed. It is a measure of how predictive a given tag selection is of
     * engagement, and is used to compute the manhattan distance for clustering users and assigning users to
     * a cluster
     *
     * @var float[]
     */
    private const MEANS = [
        0.3545987246994849, 0.241748598233092, 0.11993687044340288, 0.31503514512797737,
        0.2946099864613166, 0.030747736991024625, 0.06128259150456022, 0.22975133866678363,
        0.35005582559667153, 0.0899882774161916, 0.11275091790082134, 0.030101218816828722,
        0.041582341548202555, 0.31211355732437773, 0.37815002948556764, 0.3470340051741046,
        0.007656946531000879, 0.34159866682006373, 0.2581057344823159, 0.31274554357187684,
        0.34420010572402143, 0.3005038090966229, 0.3886378444247918, 0.3069674081204343,
        0.1653008549393813, 0.08200647465132051, 0.10135619994625591, 0.2895407129537117,
        0.2486517928058211, 0.3563846586489099, 0.2971924835643093, 0.271767184675284
    ];

    public function __construct(
        private ?UserHashtagsManager $userHashtagsManager = null
    ) {
        $this->userHashtagsManager ??= Di::_()->get('Hashtags\User\Manager');
    }

    /**
     * Calculated the recommendations cluster id for the provided user
     * @param User $user
     * @return int
     * @throws Exception
     */
    public function calculateUserRecommendationsClusterId(User $user): int
    {
        $this->userHashtagsManager->setUser($user);
        $userTags = $this->userHashtagsManager->get([]);
        $userVector = $this->getUserVector($userTags);

        $distance = $this->calculateMaxDistance();
        $clusterId = -1;

        foreach (self::MEDOIDS as $medoidIndex => $medoid) {
            $currentDistance = 0;
            
            foreach ($userVector as $userVectorIndex => $tagStatus) {
                if ($tagStatus != $medoid[$userVectorIndex]) {
                    $currentDistance += self::MEANS[$userVectorIndex];
                }
            }

            if ($currentDistance < $distance) {
                $distance = $currentDistance;
                $clusterId = $medoidIndex;
            }
        }
        return $clusterId;
    }

    /**
     * Sums up all the variances to calculate the possible max distance of a set of tags from the medoids
     * @return float
     */
    private function calculateMaxDistance(): float
    {
        return array_reduce(self::MEANS, function (float $a, float $b): float {
            return $a + $b;
        }, 0);
    }

    /**
     * Transforms the user tags from DB to the vector to be used for the recommendations cluster calculation
     * @param array $userTags
     * @return array
     */
    private function getUserVector(array $userTags): array
    {
        $userVector = [];
        foreach (self::TAG_LIST as $i => $tag) {
            $userVector[$i] = 0.0;

            $matchedEl = array_filter($userTags, function (array $tagEl, int $key) use ($tag): bool {
                return $tagEl['value'] == $tag && $tagEl['selected'];
            }, ARRAY_FILTER_USE_BOTH);
            if (count($matchedEl)) {
                $userVector[$i] = 1.0;
            }
        }

        return $userVector;
    }
}
