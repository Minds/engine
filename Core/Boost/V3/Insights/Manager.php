<?php
namespace Minds\Core\Boost\V3\Insights;

use Minds\Core\Config\Config;
use Minds\Core\Di\Di;

class Manager
{
    public function __construct(
        protected ?Repository $repository = null,
        protected ?Config $config = null
    ) {
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
        $historicCpms = $this->repository->getHistoricCpms($targetAudience, $targetLocation, $paymentMethod) ?: [ 3, 15 ];

        $magnitude = 1;
        $count = count($historicCpms);
        $mean = array_sum($historicCpms) / $count;

        // Calculate to standard deviate
        $sd = sqrt(
            array_sum(
                array_map(
                    function ($val, $mean) {
                        return pow($val - $mean, 2);
                    },
                    $historicCpms,
                    array_fill(0, $count, $mean)
                )
            ) / $count
        ) * $magnitude;

        // Apply the standard deviation to remove outliers
        $normalizedHistoricCpms = array_filter($historicCpms, function ($val) use ($mean, $sd) {
            return ($val <= $mean + $sd && $val >= $mean - $sd);
        });

        $minCpm = min($normalizedHistoricCpms);
        $maxCpm = max($normalizedHistoricCpms);

        $estimatedLow = max(round(1000 * ($dailyBid/$maxCpm * 0.66), -2), 0); // Reduce by 1/3rd. Round to nearest 100.
        $estimatedHigh = round(1000 * ($dailyBid/$minCpm), -2); // Round to nearest 100

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
