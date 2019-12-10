<?php

namespace Minds\Controllers\api\v2\boost\campaigns;

use Minds\Api\AbstractApi;
use Minds\Common\Urn;
use Minds\Core\Boost\Network\Campaign;
use Minds\Core\Boost\Network\CampaignStats;

class preview extends AbstractApi
{
    /**
     * Equivalent to HTTP POST method
     * @param array $pages
     * @return mixed|null
     */
    public function post($pages): void
    {
        $urns = $_POST['entity_urns'] ?? [];

        if (empty($urns)) {
            $this->sendNotAcceptable('No entity_urns');
            return;
        }

        try {
            $urn = new Urn($urns[0]);
            $entityGuid = (string)$urn->getNss();
        } catch (\Exception $e) {
            $this->sendNotAcceptable($e->getMessage());
            return;
        }

        $campaign = (new Campaign())
            ->setType($_POST['type'] ?? '')
            ->setEntityGuid($entityGuid)
            ->setStart((int)($_POST['start'] ?? 0))
            ->setEnd((int)($_POST['end'] ?? 0))
            ->setBudget((float)($_POST['budget'] ?? 0))
            ->setImpressions($_POST['impressions']);

        $this->send([
            'preview' => (new CampaignStats())->setCampaign($campaign)->getAll()
        ]);
    }
}
