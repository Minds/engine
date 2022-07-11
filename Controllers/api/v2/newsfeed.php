<?php

/**
 * Minds Newsfeed API.
 */

namespace Minds\Controllers\api\v2;

use Minds\Api\Factory;
use Minds\Core;
use Minds\Core\Security;
use Minds\Entities;
use Minds\Entities\Activity;
use Minds\Helpers;
use Minds\Helpers\Counters;
use Minds\Interfaces;
use Minds\Interfaces\Flaggable;
use Minds\Core\Di\Di;
use Minds\Core\Entities\Actions\Save;
use Minds\Common\EntityMutation;
use Minds\Core\Feeds\Activity\RemindIntent;

// WIP: Modernize. Use PSR-7 router.
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
                        'message' => 'You do not have permission to view this post'
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
                    'owner_guid' => isset($pages[1]) ? $pages[1] : elgg_get_logged_in_user_guid()
                ];
                if (isset($_GET['pinned']) && count($_GET['pinned']) > 0) {
                    $pinned_guids = [];
                    $p = explode(',', $_GET['pinned']);
                    foreach ($p as $guid) {
                        $pinned_guids[] = (string)$guid;
                    }
                }

                break;
            case 'network':
                $options = [
                    'network' => isset($pages[1]) ? $pages[1] : core\Session::getLoggedInUserGuid()
                ];
                break;
            case 'top':
                $offset = isset($_GET['offset']) ? $_GET['offset'] : "";
                $result = Core\Di\Di::_()->get('Trending\Repository')
                    ->getList([
                        'type' => 'newsfeed',
                        'rating' => isset($_GET['rating']) ? (int) $_GET['rating'] : 1,
                        'limit' => 12,
                        'offset' => $offset
                    ]);
                ksort($result['guids']);
                $options['guids'] = $result['guids'];
                if (!$options['guids']) {
                    return Factory::response([]);
                }
                $loadNext = base64_encode($result['token']);
                break;
            case 'featured':
                $db = Core\Di\Di::_()->get('Database\Cassandra\Indexes');
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
                    'container_guid' => isset($pages[1]) ? $pages[1] : elgg_get_logged_in_user_guid()
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
                    'load-previous' => ''
                ]);
            }

            $namespace = Core\Entities::buildNamespace(array_merge([
                'type' => 'activity'
            ], $options));

            $db = Core\Di\Di::_()->get('Database\Cassandra\Indexes');
            $guids = $db->get($namespace, [
                'limit' => 5000,
                'offset' => $offset,
                'reversed' => false
            ]);

            if (isset($guids[$offset])) {
                unset($guids[$offset]);
            }

            if (!$guids) {
                return Factory::response([
                    'count' => 0,
                    'load-previous' => $offset
                ]);
            }

            return Factory::response([
                'count' => count($guids),
                'load-previous' => (string)end(array_values($guids)) ?: $offset
            ]);
        }

        //daily campaign reward
        if (Core\Session::isLoggedIn()) {
            Helpers\Campaigns\HourlyRewards::reward();
        }

        $activity = Core\Entities::get(array_merge([
            'type' => 'activity',
            'limit' => get_input('limit', 5),
            'offset' => get_input('offset', '')
        ], $options));
        if (get_input('offset') && !get_input('prepend') && $activity) { // don't shift if we're prepending to newsfeed
            array_shift($activity);
        }

        $loadPrevious = $activity ? (string) current($activity)->guid : '';

        if ($this->shouldPrependBoosts($pages)) {
            try {
                $limit = isset($_GET['access_token']) && $_GET['offset'] ? 2 : 1;
                //$limit = 2;
                $cacher = Core\Data\cache\factory::build('Redis');
                $offset =  $cacher->get(Core\Session::getLoggedinUser()->guid . ':boost-offset:newsfeed');

                /** @var Core\Boost\Network\Iterator $iterator */
                $iterator = Core\Di\Di::_()->get('Boost\Network\Iterator');
                $iterator->setPriority(!get_input('offset', ''))
                    ->setType('newsfeed')
                    ->setLimit($limit)
                    ->setOffset($offset)
                    //->setRating(0)
                    ->setQuality(0)
                    ->setIncrement(false);


                foreach ($iterator as $guid => $boost) {
                    $boost->boosted = true;
                    $boost->boosted_guid = (string) $guid;
                    array_unshift($activity, $boost);
                    //if (get_input('offset')) {
                    //bug: sometimes views weren't being calculated on scroll down
                    //Counters::increment($boost->guid, "impression");
                    //Counters::increment($boost->owner_guid, "impression");
                    //}
                }
                $cacher->set(Core\Session::getLoggedinUser()->guid . ':boost-offset:newsfeed', $iterator->getOffset(), (3600 / 2));
            } catch (\Exception $e) {
            }

            if (isset($_GET['thumb_guids'])) {
                foreach ($activity as $id => $object) {
                    unset($activity[$id]['thumbs:up:user_guids']);
                    unset($activity[$id]['thumbs:down:user_guid']);
                }
            }
        }

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

    public function post($pages)
    {
        Factory::isLoggedIn();

        $manager = Di::_()->get('Feeds\Activity\Manager');

        //essentially an edit
        if (isset($pages[0]) && is_numeric($pages[0])) {
            $activity = Di::_()->get('EntitiesBuilder')->single($pages[0]);

            // When editing media posts, they can sometimes be non-activity entities
            // so we provide some additional field
            // TODO: Anoter possible bug is the descrepency between 'description' and 'message'
            // here we are updating message field. Propose fixing this at Object/Image level
            // vs patching on activity
            if (!$activity instanceof Activity) {
                $subtype = $activity->getSubtype();
                $type = $activity->getType();
                $activity = $manager->createFromEntity($activity);
                $activity->guid = $pages[0]; // createFromEntity makes a new entity
                $activity->subtype = $subtype;
                $activity->type = $type;
            }

            $activityMutation = new EntityMutation($activity);

            if (isset($_POST['message'])) {
                $activityMutation->setMessage($_POST['message']);
            }

            if (isset($_POST['title'])) {
                $activityMutation->setTitle($_POST['title']);
            }

            if (isset($_POST['entity_guid'])) {
                $activityMutation->setEntityGuid($_POST['entity_guid']);
            }

            if (isset($_POST['mature'])) {
                $activityMutation->setMature($_POST['mature']);
            }

            if (isset($_POST['tags'])) {
                $activityMutation->setTags($_POST['tags']);
            }

            if (isset($_POST['nsfw'])) {
                $activityMutation->setNsfw($_POST['nsfw']);
            }

            // TODO: remove this when new paywall is released
            if (isset($_POST['wire_threshold'])) {
                // Validation happend on Manager->onUpdate // PaywallDelegate->onUpdate

                $activityMutation->setWireThreshold($_POST['wire_threshold']);
                $activityMutation->setPaywall(!!$_POST['wire_threshold']);
            }

            if (isset($_POST['paywall']) && !$_POST['paywall']) {
                $activityMutation->setWireThreshold(null);
                $activityMutation->setPaywall(false);
            }

            $license = $_POST['license'] ?? $_POST['attachment_license'] ?? '';

            if ($license) {
                $activityMutation->setLicense($license);
            }

            // NOTE: Only update time created (schedule) if greater than current time)
            if (isset($_POST['time_created']) && $activity->getTimeCreated() > time()) {
                $activityMutation->setTimeCreated($_POST['time_created']);
            }

            // Rich embed fields (manager will override if entity_guid exists)

            if (isset($_POST['url'])) {
                $activityMutation
                            ->setBlurb(rawurldecode($_POST['blurb'] ?? ''))
                            ->setURL(rawurldecode($_POST['url'] ?? ''))
                            ->setThumbnail($_POST['thumbnail'] ?? '');
            }

            if (isset($_POST['video_poster'])) {
                $activityMutation->setVideoPosterBase64Blob($_POST['video_poster']);
            }

            if (isset($_POST['access_id'])) {
                error_log("accessId is: " . $_POST['access_id']);
                $activityMutation->setAccessId($_POST['access_id']);
            }

            // Update the entity

            try {
                $manager->update($activityMutation);
            } catch (\Exception $e) {
                return Factory::response([
                            'status' => 'error',
                            'message' => $e->getMessage(),
                        ]);
            }

            $activity->setExportContext(true);

            return Factory::response([
                        'guid' => $activity->guid,
                        'activity' => $activityMutation->getMutatedEntity()->export(),
                        'edited' => true
                    ]);
        }

        // New activity
        $activity = new Activity();

        $activity->setMature(isset($_POST['mature']) && !!$_POST['mature']);
        $activity->setNsfw($_POST['nsfw'] ?? []);

        $user = Core\Session::getLoggedInUser();

        $now = time();

        try {
            $timeCreatedDelegate = new Core\Feeds\Activity\Delegates\TimeCreatedDelegate();
            $timeCreatedDelegate->onAdd($activity, $_POST['time_created'] ?? $now, $now);
        } catch (\Exception $e) {
            return Factory::response([
                'status' => 'error',
                'message' => $e->getMessage(),
            ]);
        }

        if ($user->isMature()) {
            $activity->setMature(true);
        }

        if (isset($_POST['access_id'])) {
            $activity->access_id = $_POST['access_id'];
        }

        if (isset($_POST['message'])) {
            $activity->setMessage(rawurldecode($_POST['message']));
        }

        // Remind
        $remind = null;

        if (isset($_POST['remind_guid'])) {
            // Fetch the remind
            $remind = Di::_()->get('EntitiesBuilder')->single($_POST['remind_guid']);
            if (!$remind) {
                return Factory::response([
                            'status' => 'error',
                            'message' => 'Remind not found',
                        ]);
            }
                    
            // throw and error return response if acl interaction check fails.
            try {
                if (!Di::_()->get('Security\ACL')->interact($remind, $user)) {
                    throw new \Exception(null);
                }
            } catch (\Exception $e) {
                return Factory::response([
                            'status' => 'error',
                            'message' => 'You can not interact with this post',
                        ]);
            }

            $remindIntent = new RemindIntent();
            $remindIntent->setGuid($remind->getGuid())
                        ->setOwnerGuid($remind->getOwnerGuid())
                        ->setQuotedPost(!!($_POST['message'] ?? false));

            $activity->setRemind($remindIntent);
        }

        // Wire/Paywall

        if (isset($_POST['wire_threshold']) && $_POST['wire_threshold']) {
            // don't allow paywalling a paywalled remind
            if ($remind?->getPaywall()) {
                return Factory::response([
                    'status' => 'error',
                    'message' => 'You cannot monetize a remind',
                ]);
            }

            $activity->setWireThreshold($_POST['wire_threshold']);
            $paywallDelegate = new Core\Feeds\Activity\Delegates\PaywallDelegate();
            $paywallDelegate->onAdd($activity);
        }

        // Container

        $container = null;

        if (isset($_POST['container_guid']) && $_POST['container_guid']) {
            if (isset($_POST['wire_threshold']) && $_POST['wire_threshold']) {
                return Factory::response([
                            'status' => 'error',
                            'message' => 'You cannot monetize group posts',
                        ]);
            }
                    
            $activity->container_guid = $_POST['container_guid'];
            if ($container = Entities\Factory::build($activity->container_guid)) {
                $activity->containerObj = $container->export();
            }
            $activity->indexes = [
                        "activity:container:$activity->container_guid",
                        "activity:network:$activity->owner_guid"
                    ];

            $cache = Di::_()->get('Cache');
            $cache->destroy("activity:container:$activity->container_guid");

            Core\Events\Dispatcher::trigger('activity:container:prepare', $container->type, [
                        'container' => $container,
                        'activity' => $activity,
                    ]);
        }

        // Tags

        if (isset($_POST['tags'])) {
            $activity->setTags($_POST['tags']);
        }

        // NSFW

        $nsfw = $_POST['nsfw'] ?? [];
        $activity->setNsfw($nsfw);

        $activity->setLicense($_POST['license'] ?? $_POST['attachment_license'] ?? '');

        $entityGuid = $_POST['entity_guid'] ?? $_POST['attachment_guid'] ?? null;
        $url = $_POST['url'] ?? null;

        try {
            if ($entityGuid && !$url) {
                // Attachment

                if ($_POST['title'] ?? null) {
                    $activity->setTitle($_POST['title']);
                }

                // Sets the attachment
                (new Core\Feeds\Activity\Delegates\AttachmentDelegate())
                            ->setActor(Core\Session::getLoggedinUser())
                            ->onCreate($activity, (string) $entityGuid);
            } elseif (!$entityGuid && $url) {
                // Set-up rich embed

                $activity
                            ->setTitle(rawurldecode($_POST['title']))
                            ->setBlurb(rawurldecode($_POST['description']))
                            ->setURL(rawurldecode($_POST['url']))
                            ->setThumbnail($_POST['thumbnail']);
            } else {
                // TODO: Handle immutable embeds (like blogs, which have an entity_guid and a URL)
                        // These should not appear naturally when creating, but might be implemented in the future.
            }

            // TODO: Move this to Core/Feeds/Activity/Manager
            if ($_POST['video_poster'] ?? null) {
                $activity->setVideoPosterBase64Blob($_POST['video_poster']);
                $videoPosterDelegate = new Core\Feeds\Activity\Delegates\VideoPosterDelegate();
                $videoPosterDelegate->onAdd($activity);
            }

            // save entity
            $success = $manager->add($activity);

            // if posting to permaweb
            try {
                if (
                    Di::_()->get('Features\Manager')->has('permaweb')
                    && $_POST['post_to_permaweb']
                ) {
                    // get guid for linkback
                    $newsfeedGuid = $activity->custom_type === 'video' || $activity->custom_type === 'batch'
                                ? $activity->entity_guid
                                : $activity->guid;

                    // dry run to generate id and save it to this activity, but not commit it to the arweave network.
                    Di::_()->get('Permaweb\Delegates\GenerateIdDelegate')
                                ->setActivity($activity)
                                ->setNewsfeedGuid($newsfeedGuid)
                                ->dispatch();

                    // Save to permaweb.
                    Di::_()->get('Permaweb\Delegates\DispatchDelegate')
                                ->setActivity($activity)
                                ->setNewsfeedGuid($newsfeedGuid)
                                ->dispatch();
                }
            } catch (\Exception $e) {
                Di::_()->get('Logger')->error($e);
            }
        } catch (\Exception $e) {
            \Sentry\captureException($e);
            return Factory::response([
                        'status' => 'error',
                        'message' => $e->getMessage()
                    ]);
        }

        if ($success) {
            // Follow activity
            (new Core\Notification\PostSubscriptions\Manager())
                        ->setEntityGuid($activity->guid)
                        ->setUserGuid(Core\Session::getLoggedInUserGuid())
                        ->follow();

            if ($activity->getEntityGuid()) {
                // Follow activity entity as well
                (new Core\Notification\PostSubscriptions\Manager())
                            ->setEntityGuid($activity->getEntityGuid())
                            ->setUserGuid(Core\Session::getLoggedInUserGuid())
                            ->follow();
            }

            if ($container) {
                Core\Events\Dispatcher::trigger('activity:container', $container->type, [
                            'container' => $container,
                            'activity' => $activity,
                        ]);
            }

            $activity->setExportContext(true);
            return Factory::response(['guid' => $activity->guid, 'activity' => $activity->export()]);
        } else {
            return Factory::response(['status' => 'failed', 'message' => 'could not save']);
        }
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
                } catch (\Exception $e) {
                }
                break;
        }

        return Factory::response([]);
    }

    public function delete($pages)
    {
        $activity = new Activity($pages[0]);
        if (!$activity->guid) {
            return Factory::response(['status' => 'error', 'message' => 'could not find activity post']);
        }

        if (!$activity->canEdit()) {
            return Factory::response(['status' => 'error', 'message' => 'you don\'t have permission']);
        }

        // Delete attachment, if applicable
        $activity = (new Core\Feeds\Activity\Delegates\AttachmentDelegate())
            ->setActor(Core\Session::getLoggedinUser())
            ->onDelete($activity);

        // remove from pinned

        $activity->getOwnerEntity()->removePinned($activity->guid);

        if ($activity->delete()) {
            return Factory::response(['message' => 'removed ' . $pages[0]]);
        }

        return Factory::response(['status' => 'error', 'message' => 'could not delete']);
    }

    /**
     * To show boosts or not
     * @param array $pages
     * @return bool
     */
    protected function shouldPrependBoosts($pages = [])
    {
        //Plus Users -> NO
        $disabledBoost = Core\Session::getLoggedinUser()->plus && Core\Session::getLoggedinUser()->disabled_boost;
        if ($disabledBoost) {
            return false;
        }

        //Prepending posts -> NO
        if (isset($_GET['prepend'])) {
            return false;
        }

        //Not a network feed -> NO
        if ($pages[0] != 'network') {
            return false;
        }

        //Offset - YES
        if (isset($_GET['offset']) && $_GET['offset']) {
            return true;
        }

        //Mobile - YES
        if (isset($_GET['access_token'])) {
            return true;
        }

        return false;
    }
}
