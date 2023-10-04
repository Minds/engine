<?php

/**
 * Minds Newsfeed API
 *
 * @version 1
 * @author Mark Harding
 */

namespace Minds\Controllers\api\v1;

use Exception;
use Minds\Api\Factory;
use Minds\Core;
use Minds\Core\Di\Di;
use Minds\Core\Security;
use Minds\Core\Supermind\Exceptions\SupermindNotFoundException;
use Minds\Core\Supermind\Manager as SupermindManager;
use Minds\Core\Supermind\SupermindRequestStatus;
use Minds\Entities;
use Minds\Entities\Activity;
use Minds\Exceptions\DeprecatedException;
use Minds\Helpers;
use Minds\Interfaces;

class newsfeed implements Interfaces\Api
{
    /**
     * Returns the newsfeed
     * @param array $pages
     *
     * API:: /v1/newsfeed/
     */
    public function get($pages)
    {
        $response = [];
        $loadNext = '';

        if (!isset($pages[0])) {
            $pages[0] = 'network';
        }

        $pinned_guids = null;
        switch ($pages[0]) {
            case 'single':
                $activity = new Activity($pages[1]);

                if (!Security\ACL::_()->read($activity)) {
                    return Factory::response([
                        'status' => 'error',
                        'message' => 'You do not have permission to view this post',
                    ]);
                }

                if (!$activity->guid || Helpers\Flags::shouldFail($activity)) {
                    return Factory::response(['status' => 'error']);
                }
                return Factory::response(['activity' => $activity->export()]);
                break;
            default:
            case 'personal':
                $options = [
                    'owner_guid' => isset($pages[1]) ? $pages[1] : elgg_get_logged_in_user_guid(),
                ];
                if (isset($_GET['pinned']) && count($_GET['pinned']) > 0) {
                    $pinned_guids = [];
                    $p = explode(',', $_GET['pinned']);
                    foreach ($p as $guid) {
                        $pinned_guids[] = (string) $guid;
                    }
                }

                break;
            case 'network':
                $options = [
                    'network' => isset($pages[1]) ? $pages[1] : core\Session::getLoggedInUserGuid(),
                ];
                break;
            case 'top':
                $offset = isset($_GET['offset']) ? $_GET['offset'] : "";
                $result = Di::_()->get('Trending\Repository')
                    ->getList([
                        'type' => 'newsfeed',
                        'rating' => isset($_GET['rating']) ? (int) $_GET['rating'] : 1,
                        'limit' => 12,
                        'offset' => $offset,
                    ]);
                ksort($result['guids']);
                $options['guids'] = $result['guids'];
                if (!$options['guids']) {
                    return Factory::response([]);
                }
                $loadNext = base64_encode($result['token']);
                break;
            case 'featured':
                $db = Di::_()->get('Database\Cassandra\Indexes');
                $offset = isset($_GET['offset']) ? $_GET['offset'] : "";
                $guids = $db->getRow('activity:featured', ['limit' => 24, 'offset' => $offset]);
                if ($guids) {
                    $options['guids'] = $guids;
                } else {
                    return Factory::response([]);
                }
                break;
            case 'container':
                $options = [
                    'container_guid' => isset($pages[1]) ? $pages[1] : elgg_get_logged_in_user_guid(),
                ];

                if (isset($_GET['pinned']) && count($_GET['pinned']) > 0) {
                    $pinned_guids = [];
                    $p = explode(',', $_GET['pinned']);
                    foreach ($p as $guid) {
                        $pinned_guids[] = (string) $guid;
                    }
                }
                break;
        }

        if (get_input('count')) {
            $offset = get_input('offset', '');

            if (!$offset) {
                return Factory::response([
                    'count' => 0,
                    'load-previous' => '',
                ]);
            }

            $namespace = Core\Entities::buildNamespace(array_merge([
                'type' => 'activity',
            ], $options));

            $db = Di::_()->get('Database\Cassandra\Indexes');
            $guids = $db->get($namespace, [
                'limit' => 5000,
                'offset' => $offset,
                'reversed' => false,
            ]);

            if (isset($guids[$offset])) {
                unset($guids[$offset]);
            }

            if (!$guids) {
                return Factory::response([
                    'count' => 0,
                    'load-previous' => $offset,
                ]);
            }

            return Factory::response([
                'count' => count($guids),
                'load-previous' => (string) end(array_values($guids)) ?: $offset,
            ]);
        }
    
        $activity = Core\Entities::get(array_merge([
            'type' => 'activity',
            'limit' => get_input('limit', 5),
            'offset' => get_input('offset', ''),
        ], $options));
        if (get_input('offset') && !get_input('prepend') && $activity) { // don't shift if we're prepending to newsfeed
            array_shift($activity);
        }

        $loadPrevious = $activity ? (string) current($activity)->guid : '';

        if ($activity) {
            if (!$loadNext) {
                $loadNext = (string) end($activity)->guid;
            }
            if ($pages[0] == 'featured') {
                $loadNext = (string) end($activity)->featured_id;
            }
            $response['load-previous'] = $loadPrevious;

            if ($pinned_guids) {
                $response['pinned'] = [];
                $entities = Core\Entities::get(['guids' => $pinned_guids]);

                if ($entities) {
                    foreach ($entities as $entity) {
                        $exported = $entity->export();
                        $exported['pinned'] = true;
                        $response['pinned'][] = $exported;
                    }
                }
            }

            $response['activity'] = factory::exportable($activity, ['boosted', 'boosted_guid'], true);
        }

        $response['load-next'] = $loadNext;

        return Factory::response($response);
    }

    /**
     * @deprecated for v2 endpoint.
     */
    public function post($pages)
    {
        throw new DeprecatedException();
    }

    public function put($pages)
    {
        $activity = new Activity($pages[0]);
        if (!$activity->guid) {
            return Factory::response(['status' => 'error', 'message' => 'could not find activity post']);
        }

        switch ($pages[1]) {
            case 'view':
                try {
                    Core\Analytics\App::_()
                        ->setMetric('impression')
                        ->setKey($activity->guid)
                        ->increment();

                    Core\Analytics\User::_()
                        ->setMetric('impression')
                        ->setKey($activity->owner_guid)
                        ->increment();
                } catch (Exception $e) {
                }
                break;
        }

        return Factory::response([]);
    }

    /**
     * @throws SupermindNotFoundException
     * @throws Exception
     */
    public function delete($pages): void
    {
        $activity = new Activity($pages[0]);
        if (!$activity->guid) {
            Factory::response(['status' => 'error', 'message' => 'could not find activity post']);
            return;
        }

        if ($activity->supermind) {
            if ($activity->supermind['is_reply']) {
                Factory::response(['status'=>'error', 'message' => "This activity cannot be deleted"]);
                return;
            }
            $supermindManager = $this->getSupermindManager();
            $supermindRequest = $supermindManager->getRequest($activity->supermind['request_guid']);
            if ($supermindRequest->getStatus() === SupermindRequestStatus::CREATED) {
                Factory::response(['status'=>'error', 'message' => "This activity cannot be deleted"]);
                return;
            }
        }

        if (!$activity->canEdit()) {
            Factory::response(['status' => 'error', 'message' => 'you don\'t have permission']);
            return;
        }
        /** @var Entities\User $owner */
        $owner = $activity->getOwnerEntity();

        if (
            $activity->entity_guid &&
            in_array($activity->custom_type, ['batch', 'video'], true)
        ) {
            // Delete attachment object
            try {
                $attachment = Entities\Factory::build($activity->entity_guid);

                if ($attachment && $owner->guid == $attachment->owner_guid) {
                    $attachment->delete();
                }
            } catch (Exception $e) {
                error_log("Cannot delete attachment: {$activity->entity_guid}");
            }
        }

        // remove from pinned
        $owner->removePinned($activity->guid);

        if ($activity->delete()) {
            Factory::response(['message' => 'removed ' . $pages[0]]);
            return;
        }

        Factory::response(['status' => 'error', 'message' => 'could not delete']);
    }

    /**
     * Returns a new instance of the Supermind Manager
     * @return SupermindManager
     */
    private function getSupermindManager(): SupermindManager
    {
        return Di::_()->get('Supermind\Manager');
    }
}
