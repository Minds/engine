<?php
namespace Minds\Core\Monetization\EarningsOverview;

use Minds\Traits\MagicAttributes;

class OverviewModel
{
    use MagicAttributes;

    /** @var  */
    private $payouts = [];

    /** @var */
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
