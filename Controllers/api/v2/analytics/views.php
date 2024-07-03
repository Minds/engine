<?php


namespace Minds\Controllers\api\v2\analytics;

use Minds\Api\Factory;
use Minds\Common\IpAddress;
use Minds\Common\Urn;
use Minds\Core;
use Minds\Core\Di\Di;
use Minds\Core\Entities\Resolver;
use Minds\Core\Router\Exceptions\ForbiddenException;
use Minds\Core\Session;
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
                $isLoggedIn = Session::getLoggedinUser();
                $identifier = $isLoggedIn ? Session::getLoggedInUserGuid() : (new IpAddress)->get();

                $keyValueLimiter = Di::_()->get('Security\RateLimits\KeyValueLimiter');
                $config = Di::_()->get('Config');
                $entityResolver = new Resolver();

                $keyValueLimiter
                    ->setKey('boost-view')
                    ->setValue(md5($identifier . ":" . $pages[1]))
                    ->setSeconds($config->get('boost_view_rate_limit') ?? 5)
                    ->setMax(1)
                    ->checkAndIncrement();

                $urn = $_POST['client_meta']['campaign'] ?? "urn:boost:newsfeed:{$pages[1]}";

                $boost = $entityResolver->single(new Urn($urn));

                if (!$boost) {
                    return Factory::response([
                        'status' => 'error',
                        'message' => 'Could not find boost'
                    ]);
                }

                if ($_POST['client_meta']['medium'] === 'boost-rotator' && $_POST['client_meta']['position'] < 0) {
                    return Factory::response([
                        'status' => 'error',
                        'message' => 'Boost rotator position can not be below 0'
                    ]);
                }

                Counters::increment($boost->getEntity()->guid, "impression");
                Counters::increment($boost->getEntity()->owner_guid, "impression");

                try {
                    if (!$boost->getEntity()) {
                        return;
                    }

                    // TODO: Ensure client_meta campaign matches this boost

                    $viewsManager->record(
                        view: (new Core\Analytics\Views\View())
                            ->setEntityUrn($boost->getEntity()->getUrn())
                            ->setOwnerGuid((string) $boost->getEntity()->getOwnerGuid())
                            ->setClientMeta($_POST['client_meta'] ?? [])
                            ->setExternal($isLoggedIn === false || ($_GET['external'] ?? false)),
                        entity: $boost->getEntity()
                    );
                } catch (\Exception $e) {
                    error_log($e);
                }

                return Factory::response([
                    'status' => 'success',
                ]);
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
                        view: (new Core\Analytics\Views\View())
                            ->setEntityUrn($entity->getUrn())
                            ->setOwnerGuid((string) $entity->getOwnerGuid())
                            ->setClientMeta($_POST['client_meta'] ?? []),
                        entity: $entity
                    );
                } catch (\Exception $e) {
                    error_log($e);
                }

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
