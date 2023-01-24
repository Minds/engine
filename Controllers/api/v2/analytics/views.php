<?php


namespace Minds\Controllers\api\v2\analytics;

use Minds\Api\Factory;
use Minds\Common\Urn;
use Minds\Core;
use Minds\Core\Boost\V3\Models\Boost;
use Minds\Core\Di\Di;
use Minds\Core\Entities\Resolver;
use Minds\Entities;
use Minds\Helpers\Counters;
use Minds\Interfaces;

class views implements Interfaces\Api, Interfaces\ApiIgnorePam
{
    public function get($pages)
    {
        return Factory::response([]);
    }

    public function post($pages)
    {
        $viewsManager = new Core\Analytics\Views\Manager();

        switch ($pages[0]) {
            case 'boost':
                $expire = Di::_()->get('Boost\Network\Expire');
                $metrics = Di::_()->get('Boost\Network\Metrics');
                $manager = Di::_()->get('Boost\Network\Manager');
                $entityResolver = new Resolver();

                $urn = $_POST['client_meta']['campaign'] ?? "urn:boost:newsfeed:{$pages[1]}";

                $boost = $entityResolver->single(new Urn($urn));
                if (!$boost) {
                    return Factory::response([
                        'status' => 'error',
                        'message' => 'Could not find boost'
                    ]);
                }

                $isV3 = ($boost instanceof Boost);

                if ($_POST['client_meta']['medium'] === 'boost-rotator' && $_POST['client_meta']['position'] < 0) {
                    return Factory::response([
                        'status' => 'error',
                        'message' => 'Boost rotator position can not be below 0'
                    ]);
                }

                if (!$isV3) {
                    $count = $metrics->incrementViews($boost);

                    if ($count > $boost->getImpressions()) {
                        $expire->setBoost($boost);
                        $expire->expire();
                    }
                } else {
                    $count = 0;
                }

                Counters::increment($boost->getEntity()->guid, "impression");
                Counters::increment($boost->getEntity()->owner_guid, "impression");

                try {
                    if (!$boost->getEntity()) {
                        return;
                    }

                    // TODO: Ensure client_meta campaign matches this boost

                    $viewsManager->record(
                        (new Core\Analytics\Views\View())
                            ->setEntityUrn($boost->getEntity()->getUrn())
                            ->setOwnerGuid((string) $boost->getEntity()->getOwnerGuid())
                            ->setClientMeta($_POST['client_meta'] ?? [])
                    );
                } catch (\Exception $e) {
                    error_log($e);
                }

                if ($isV3) {
                    Factory::response([
                        'status' => 'success',
                    ]);
                } else {
                    Factory::response([
                        'status' => 'success',
                        'impressions' => $boost->getImpressions(),
                        'impressions_met' => $count,
                    ]);
                }
                return;
                break;
            case 'activity':
            case 'entity':
                $entity = Entities\Factory::build($pages[1]);

                if (!$entity) {
                    return Factory::response([
                        'status' => 'error',
                        'message' => 'Could not the entity'
                    ]);
                }

                if ($entity->type === 'activity') {
                    try {
                        Core\Analytics\App::_()
                        ->setMetric('impression')
                        ->setKey($entity->guid)
                        ->increment();

                        Core\Analytics\User::_()
                        ->setMetric('impression')
                        ->setKey($entity->owner_guid)
                        ->increment();
                    } catch (\Exception $e) {
                        error_log($e->getMessage());
                    }
                }

                try {
                    $viewsManager->record(
                        (new Core\Analytics\Views\View())
                            ->setEntityUrn($entity->getUrn())
                            ->setOwnerGuid((string) $entity->getOwnerGuid())
                            ->setClientMeta($_POST['client_meta'] ?? [])
                    );
                } catch (\Exception $e) {
                    error_log($e);
                }

                Di::_()->get('Referrals\Cookie')
                    ->setEntity($entity)
                    ->create();

                break;
        }

        return Factory::response([]);
    }

    public function put($pages)
    {
        return Factory::response([]);
    }

    public function delete($pages)
    {
        return Factory::response([]);
    }
}
