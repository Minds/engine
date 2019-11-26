<?php

namespace Minds\Controllers\api\v2\analytics;

use Minds\Api\Factory;
use Minds\Common\Urn;
use Minds\Core;
use Minds\Core\Di\Di;
use Minds\Entities;
use Minds\Helpers\Counters;
use Minds\Interfaces;
use Minds\Core\Boost;

class views implements Interfaces\Api
{
    public function get($pages)
    {
        return Factory::response([]);
    }

    /**
     * @param array $pages
     * @return void
     * @throws \Exception
     */
    public function post($pages)
    {
        $viewsManager = new Core\Analytics\Views\Manager();

        /** @var Core\Boost\Campaigns\Manager $campaignsManager */
        $campaignsManager = Di::_()->get(Boost\Campaigns\Manager::getDiAlias());

        /** @var Core\Boost\Campaigns\Metrics $campaignsMetricsManager */
        $campaignsMetricsManager = Di::_()->get(Boost\Campaigns\Metrics::getDiAlias());

        switch ($pages[0]) {
            case 'boost':
                $urn = new Urn(
                    is_numeric($pages[1]) ?
                        "urn:boost:newsfeed:{$pages[1]}" :
                        $pages[1]
                );

                if ($urn->getNid() === 'campaign') {
                    // Boost Campaigns

                    try {
                        $campaign = $campaignsManager->getCampaignByUrn((string)$urn);

                        $campaignsMetricsManager
                            ->setCampaign($campaign)
                            ->increment();

                        $campaignsManager
                            ->onImpression($campaign);

                        // NOTE: Campaigns have a _single_ entity, for now. Refactor this when we support multiple
                        // Ideally, we should use a composite URN, like: urn:campaign-entity:100000321:(urn:activity:100000500)
                        foreach ($campaign->getEntityUrns() as $entityUrn) {
                            $viewsManager->record(
                                (new Core\Analytics\Views\View())
                                    ->setEntityUrn($entityUrn)
                                    ->setClientMeta($_POST['client_meta'] ?? [])
                            );
                        }
                    } catch (\Exception $e) {
                        Factory::response([
                            'status' => 'error',
                            'message' => $e->getMessage(),
                        ]);
                        return;
                    }

                    Factory::response([]);
                    return;
                }

                $urn = (string) $urn;

                $metrics = new Boost\Network\Metrics();
                $manager = new Boost\Network\Manager();

                $boost = $manager->get($urn, [ 'hydrate' => true ]);
                if (!$boost) {
                    Factory::response([
                        'status' => 'error',
                        'message' => 'Could not find boost'
                    ]);
                    return;
                }
                
                $count = $metrics->incrementViews($boost);

                if ($count > $boost->getImpressions()) {
                    $manager->expire($boost);
                }

                Counters::increment($boost->getEntity()->guid, "impression");
                Counters::increment($boost->getEntity()->owner_guid, "impression");

                try {
                    // TODO: Ensure client_meta campaign matches this boost

                    $viewsManager->record(
                        (new Core\Analytics\Views\View())
                            ->setEntityUrn($boost->getEntity()->getUrn())
                            ->setClientMeta($_POST['client_meta'] ?? [])
                    );
                } catch (\Exception $e) {
                    error_log($e);
                }

                Factory::response([
                    'status' => 'success',
                    'impressions' => $boost->getImpressions(),
                    'impressions_met' => $count,
                ]);
                return;
                break;
            case 'activity':
                $activity = new Entities\Activity($pages[1]);

                if (!$activity->guid) {
                    Factory::response([
                        'status' => 'error',
                        'message' => 'Could not find activity post'
                    ]);
                    return;
                }

                try {
                    Core\Analytics\App::_()
                        ->setMetric('impression')
                        ->setKey($activity->guid)
                        ->increment();

                    if ($activity->remind_object) {
                        Core\Analytics\App::_()
                            ->setMetric('impression')
                            ->setKey($activity->remind_object['guid'])
                            ->increment();

                        Core\Analytics\App::_()
                            ->setMetric('impression')
                            ->setKey($activity->remind_object['owner_guid'])
                            ->increment();
                    }

                    Core\Analytics\User::_()
                        ->setMetric('impression')
                        ->setKey($activity->owner_guid)
                        ->increment();
                } catch (\Exception $e) {
                    error_log($e->getMessage());
                }

                try {
                    $viewsManager->record(
                        (new Core\Analytics\Views\View())
                            ->setEntityUrn($activity->getUrn())
                            ->setClientMeta($_POST['client_meta'] ?? [])
                    );
                } catch (\Exception $e) {
                    error_log($e);
                }

                break;
        }

        Factory::response([]);
    }

    public function put($pages)
    {
        Factory::response([]);
    }

    public function delete($pages)
    {
        Factory::response([]);
    }
}
