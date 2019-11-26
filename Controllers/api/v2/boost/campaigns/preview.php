<?php

namespace Minds\Controllers\api\v2\boost\campaigns;

use Minds\Api\Api;
use Minds\Core\Boost\Campaigns\Campaign;
use Minds\Core\Boost\Campaigns\Stats;
use Minds\Core\Di\Di;

class preview extends Api
{
    /**
     * Equivalent to HTTP POST method
     * @param array $pages
     * @return mixed|null
     */
    public function post($pages): void
    {
        $campaign = (new Campaign())
            ->setType($_POST['type'] ?? '')
            ->setEntityUrns($_POST['entity_urns'] ?? [])
            ->setBudgetType($_POST['budget_type'] ?? '')
            ->setHashtags($_POST['hashtags'] ?? [])
            ->setStart((int)($_POST['start'] ?? 0))
            ->setEnd((int)($_POST['end'] ?? 0))
            ->setBudget((float)($_POST['budget'] ?? 0))
            ->setImpressions($_POST['impressions']);

        /** @var Stats $statsManager */
        $statsManager = Di::_()->get('Boost\Campaigns\Stats');

        $this->send([
            'preview' => $statsManager->setCampaign($campaign)->getAll()
        ]);
    }
}
