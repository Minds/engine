<?php
namespace Minds\Core\Boost\V3\Insights;

use Cassandra\Duration;
use Minds\Core\Config\Config;
use Minds\Core\Di\Di;

class Manager
{
    public function __construct(
        protected ?ViewsScroller $viewsScroller = null,
        protected ?Repository $repository = null,
        protected ?Config $config = null
    ) {
        $this->viewsScroller ??= Di::_()->get(ViewsScroller::class);
        $this->repository ??= Di::_()->get(Repository::class);
        $this->config ??= Di::_()->get('Config');
    }

    /**
     * Stores the views a boost has received to the summaries table
     */
    // public function syncViews()
    // {
    //     // Get the cursor
    //     $cursor = new Timeuuid(strtotime('midnight'));

    //     $viewsByGuid = [];

    //     foreach ($this->viewsScroller->scroll(gtTimeuuid: $cursor) as $view) {
    //         $campaign = $row['campaign'];

    //         if ($campaign && $boost = $this->getBoostByCampaign($campaign)) {
    //             $this->updateViews($boost, val: 1); // Increment in-memory views
    //         }

    //         // "INSERT INTO boost_summaries (guid, day, views, reach) VALUES (123, 0, 1, 0) ON DUPLICATE KEY UPDATE views=views+1"
    //     }
    // }

    /**
     * Returns very inaccurate estimates for the boost system. We will improve over time as we get more data.
     * @param int $targetAudience
     * @param int $targetLocation
     * @param int $paymentMethod
     * @param int $dailyBid
     * @param int $duration
     * @return int[]
     */
    public function getEstimate(
        int $targetAudience,
        int $targetLocation,
        int $paymentMethod,
        int $dailyBid,
        int $duration = 1
    ): array {
        $estimates = $this->repository->getEstimate($targetAudience, $targetLocation, $paymentMethod);

        $minCpm = 1000 * $estimates['24h_bids'] / $estimates['24h_views']; // This is the min cpm we quote on
        $maxCpm = $minCpm * 5; // This is max cpm we will quote on

        $estimatedLow = round(min(1000 * ($dailyBid/$maxCpm), $estimates['24h_views']));
        $estimatedHigh = round(min(1000 * ($dailyBid/$minCpm), $estimates['24h_views']));

        // If the high estimate caps out, use a static value
        if ($estimatedHigh === round($estimates['24h_views'])) {
            $estimatedLow = $estimatedHigh / 5;
        }

        return [
            'views' => [
                'low' => $estimatedLow * $duration,
                'high' => $estimatedHigh * $duration,
            ],
            'cpm' => [
                'low' => $minCpm,
                'high' => $maxCpm,
            ]
        ];
    }
}
