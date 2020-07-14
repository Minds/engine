<?php
namespace Minds\Core\Monetization\EarningsOverview;

use Minds\Traits\MagicAttributes;

/**
 * @method OverviewModel setPayouts(array $payouts)
 * @method array getPayouts()
 * @method OverviewModel setEarnings(array $earnings)
 * @method array getEarnings()
 */
class OverviewModel
{
    use MagicAttributes;

    /** @var array */
    private $payouts = [];

    /** @var array */
    private $earnings = [];
    
    /**
     * Export the overview
     * @return array
     */
    public function export(): array
    {
        return [
            'payouts' => array_map(function ($payout) {
                return $payout->export();
            }, $this->payouts),
            'earnings' => array_map(function ($earningsGroup) {
                return $earningsGroup->export();
            }, $this->earnings),
        ];
    }
}
