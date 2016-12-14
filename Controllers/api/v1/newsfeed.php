<?php
/**
 * Minds Newsfeed API
 *
 * @version 1
 * @author Mark Harding
 */
namespace Minds\Controllers\api\v1;

use Minds\Core;
use Minds\Core\Security;
use Minds\Helpers;
use Minds\Entities;
use Minds\Interfaces;
use Minds\Api\Factory;

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
        $response = array();

        if (!isset($pages[0])) {
            $pages[0] = 'network';
        }

        switch ($pages[0]) {
          case 'single':
              $activity = new \Minds\Entities\Activity($pages[1]);
              return Factory::response(array('activity'=>$activity->export()));
              break;
          default:
          case 'personal':
                $options = array(
                    'owner_guid' => isset($pages[1]) ? $pages[1] : elgg_get_logged_in_user_guid()
                );
                break;
            case 'network':
            $options = array(
                'network' => isset($pages[1]) ? $pages[1] : core\Session::getLoggedInUserGuid()
            );
            break;
          case 'featured':
            $db = Core\Di\Di::_()->get('Database\Cassandra\Indexes');
            $guids = $db->getRow('activity:featured', [ 'limit' => 24 ]);
            if ($guids) {
                $options['guids'] = $guids;
            } else {
                return Factory::response([]);
            }
            break;
          case 'container':
            $options = array(
              'container_guid' => isset($pages[1]) ? $pages[1] : elgg_get_logged_in_user_guid()
            );
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
                'load-previous' => (string) end(array_values($guids)) ?: $offset
            ]);
        }

        //daily campaign reward
        if (Core\Session::isLoggedIn()) {
            Helpers\Campaigns\HourlyRewards::reward();
        }

        $activity = Core\Entities::get(array_merge(array(
            'type' => 'activity',
            'limit' => get_input('limit', 5),
            'offset'=> get_input('offset', '')
        ), $options));
        if (get_input('offset') && !get_input('prepend')) { // don't shift if we're prepending to newsfeed
            array_shift($activity);
        }

        $loadPrevious = (string) current($activity)->guid;

     //   \Minds\Helpers\Counters::incrementBatch($activity, 'impression');

        if ($pages[0] == 'network' && !get_input('prepend')) { // No boosts when prepending
            try {
                $limit = isset($_GET['access_token']) || $_GET['offset'] ? 2 : 1;
                $boosts = Core\Boost\Factory::build("Newsfeed")->getBoosts($limit);
                foreach ($boosts as $boost) {
                    $boost->boosted = true;
                    array_unshift($activity, $boost);
                    //if (get_input('offset')) {
                      //bug: sometimes views weren't being calculated on scroll down
                      \Minds\Helpers\Counters::increment($boost->guid, "impression");
                    \Minds\Helpers\Counters::increment($boost->owner_guid, "impression");
                    //}
                }
                if (!$boosts) {
                    $cacher = Core\Data\cache\factory::build('apcu');
                    $offset =  $cacher->get(Core\Session::getLoggedinUser()->guid . ":newsfeed-blog-boost-offset") ?: "";
                    $guids = Core\Data\indexes::fetch('object:blog:featured', ['offset'=> $offset, 'limit'=> $limit]);
                    if ($guids) {
                        $blogs = Core\Entities::get(['guids'=>$guids]);
                        foreach ($blogs as $blog) {
                            $boost = new Entities\Activity();
                            $boost->guid = $blog->guid;
                            $boost->owner_guid = $blog->owner_guid;
                            $boost->{'thumbs:up:user_guids'} = $blog->{'thumbs:up:user_guids'};
                            $boost->{'thumbs:down:user_guids'} = $blog->{'thumbs:down:user_guids'};
                            $boost->setTitle($blog->title)
                                  ->setBlurb(strip_tags($blog->description))
                                  ->setURL($blog->getURL())
                                  ->setThumbnail($blog->getIconUrl())
                                  ->setFromEntity($blog);
                            $boost->boosted = true;
                            array_unshift($activity, $boost);
                        }
                        if (count($blogs) < 5) {
                            $cacher->set(Core\Session::getLoggedinUser()->guid . ":newsfeed-blog-boost-offset", "");
                        } else {
                            $cacher->set(Core\Session::getLoggedinUser()->guid . ":newsfeed-blog-boost-offset", end($blogs)->featured_id);
                        }
                    }
                }
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
            $response['activity'] = factory::exportable($activity, array('boosted'), true);
            $response['load-next'] = (string) end($activity)->guid;
            $response['load-previous'] = $loadPrevious;
        }

        return Factory::response($response);
    }

    public function post($pages)
    {
        Factory::isLoggedIn();

        //factory::authorize();
        switch ($pages[0]) {
            case 'remind':
                $embeded = new Entities\Entity($pages[1]);
                $embeded = core\Entities::build($embeded); //more accurate, as entity doesn't do this @todo maybe it should in the future

                //check to see if we can interact with the parent
                if (!Security\ACL::_()->interact($embeded)) {
                    return false;
                }

                \Minds\Helpers\Counters::increment($embeded->guid, 'remind');

                if ($embeded->owner_guid != Core\Session::getLoggedinUser()->guid) {
                    Core\Events\Dispatcher::trigger('notification', 'remind', array('to'=>array($embeded->owner_guid), 'notification_view'=>'remind', 'title'=>$embeded->title, 'entity'=>$embeded));
                }

                $message = '';

                if (isset($_POST['message'])) {
                    $message = rawurldecode($_POST['message']);
                }

                /*if ($embeded->owner_guid != Core\Session::getLoggedinUser()->guid) {
                    $cacher = \Minds\Core\Data\cache\Factory::build();
                    if (!$cacher->get(Core\Session::getLoggedinUser()->guid . ":hasreminded:$embeded->guid")) {
                        $cacher->set(Core\Session::getLoggedinUser()->guid . ":hasreminded:$embeded->guid", true);

                        Helpers\Wallet::createTransaction(Core\Session::getLoggedinUser()->guid, 1, $embeded->guid, 'remind');
                        Helpers\Wallet::createTransaction($embeded->owner_guid, 1, $embeded->guid, 'remind');
                    }
                }*/

                $activity = new Entities\Activity();
                switch ($embeded->type) {
                    case 'activity':
                        if ($message) {
                            $activity->setMessage($message);
                        }

                        if ($embeded->remind_object) {
                            $activity->setRemind($embeded->remind_object)->save();
                            \Minds\Helpers\Counters::increment($embeded->remind_object['guid'], 'remind');
                        } else {
                            $activity->setRemind($embeded->export())->save();
                        }
                     break;
                     default:
                         /**
                           * The following are actually treated as embeded posts.
                           */
                           switch ($embeded->subtype) {
                               case 'blog':
                                  if ($embeded->owner_guid == Core\Session::getLoggedInUserGuid()) {
                                      $activity->setTitle($embeded->title)
                                      ->setBlurb(strip_tags($embeded->description))
                                      ->setURL($embeded->getURL())
                                      ->setThumbnail($embeded->getIconUrl())
                                      ->setFromEntity($embeded)
                                      ->setMessage($message)
                                      ->save();
                                  } else {
                                      $activity->setRemind((new \Minds\Entities\Activity())
                                        ->setTitle($embeded->title)
                                        ->setBlurb(strip_tags($embeded->description))
                                        ->setURL($embeded->getURL())
                                        ->setThumbnail($embeded->getIconUrl())
                                        ->setFromEntity($embeded)
                                        ->export())
                                      ->setMessage($message)
                                      ->save();
                                  }
                                  break;
                                case 'video':
                                    if ($embeded->owner_guid == Core\Session::getLoggedInUserGuid()) {
                                        $activity->setFromEntity($embeded)
                                          ->setCustom('video', [
                                              'thumbnail_src'=>$embeded->getIconUrl(),
                                              'guid'=>$embeded->guid,
                                              'mature'=>$embeded instanceof \Minds\Interfaces\Flaggable ? $embeded->getFlag('mature') : false
                                            ])
                                          ->setTitle($embeded->title)
                                          ->setBlurb($embeded->description)
                                          ->setMessage($message)
                                          ->save();
                                    } else {
                                        $activity = new \Minds\Entities\Activity();
                                        $activity->setRemind((new \Minds\Entities\Activity())
                                          ->setFromEntity($embeded)
                                          ->setCustom('video', [
                                              'thumbnail_src'=>$embeded->getIconUrl(),
                                              'guid'=>$embeded->guid,
                                              'mature'=>$embeded instanceof \Minds\Interfaces\Flaggable ? $embeded->getFlag('mature') : false
                                            ])
                                          ->setTitle($embeded->title)
                                          ->setBlurb($embeded->description)
                                          ->export())
                                        ->setMessage($message)
                                        ->save();
                                    }
                                    break;
                                case 'image':
                                    if ($embeded->owner_guid == Core\Session::getLoggedInUserGuid()) {
                                        $activity->setCustom('batch', [[
                                          'src'=>elgg_get_site_url() . 'archive/thumbnail/'.$embeded->guid,
                                          'href'=>elgg_get_site_url() . 'archive/view/'.$embeded->container_guid.'/'.$embeded->guid,
                                          'mature'=>$embeded instanceof \Minds\Interfaces\Flaggable ? $embeded->getFlag('mature') : false
                                        ]])
                                        ->setFromEntity($embeded)
                                        ->setTitle($embeded->title)
                                        ->setBlurb($embeded->description)
                                        ->setMessage($message)
                                        ->save();
                                    } else {
                                        $activity->setRemind((new \Minds\Entities\Activity())
                                          ->setCustom('batch', [[
                                              'src'=>elgg_get_site_url() . 'archive/thumbnail/'.$embeded->guid,
                                              'href'=>elgg_get_site_url() . 'archive/view/'.$embeded->container_guid.'/'.$embeded->guid,
                                              'mature'=>$embeded instanceof \Minds\Interfaces\Flaggable ? $embeded->getFlag('mature') : false
                                             ]])
                                          ->setFromEntity($embeded)
                                          ->setTitle($embeded->title)
                                          ->setBlurb($embeded->description)
                                          ->export())
                                        ->setMessage($message)
                                        ->save();
                                    }
                                    break;
                            }
                }
                return Factory::response(array('guid'=>$activity->guid));
                break;
            default:
                //essentially an edit
                if (is_numeric($pages[0])) {
                    $activity = new Entities\Activity($pages[0]);
                    if (!$activity->canEdit()) {
                        return Factory::response(array('status'=>'error', 'message'=>'Post not editable'));
                    }

                    $allowed = array('message', 'title');
                    foreach ($allowed as $allowed) {
                        if (isset($_POST[$allowed]) && $_POST[$allowed] !== false) {
                            $activity->$allowed = $_POST[$allowed];
                        }
                    }

                    if (isset($_POST['mature'])) {
                        $activity->setMature($_POST['mature']);
                    }

                    if (isset($_POST['paywall'])) {
                        $activity->setPayWall($_POST['paywall']);
                    }

                    $activity->indexes = [ "activity:$activity->owner_guid:edits" ]; //don't re-index on edit
                    (new Core\Translation\Storage())->purge($activity->guid);
                    $activity->save();
                    $activity->setExportContext(true);
                    return Factory::response(array('guid'=>$activity->guid, 'activity'=> $activity->export(), 'edited'=>true));
                }

                $activity = new Entities\Activity();

                $activity->setMature(isset($_POST['mature']) && !!$_POST['mature']);

                if (isset($_POST['access_id'])) {
                    $activity->access_id = $_POST['access_id'];
                }

                if (isset($_POST['paywall'])) {
                    $activity->setPayWall($_POST['paywall']);
                }

                if (isset($_POST['message'])) {
                    $activity->setMessage(rawurldecode($_POST['message']));
                }

                if (isset($_POST['title']) && $_POST['title']) {
                    $activity->setTitle(rawurldecode($_POST['title']))
                        ->setBlurb(rawurldecode($_POST['description']))
                        ->setURL(\elgg_normalize_url(rawurldecode($_POST['url'])))
                        ->setThumbnail(rawurldecode($_POST['thumbnail']));
                }

                if (isset($_POST['attachment_guid']) && $_POST['attachment_guid']) {
                    $attachment = entities\Factory::build($_POST['attachment_guid']);
                    if (!$attachment) {
                        break;
                    }
                    $attachment->title = $activity->message;
                    $attachment->access_id = 2;

                    if ($attachment instanceof \Minds\Interfaces\Flaggable) {
                        $attachment->setFlag('mature', $activity->getMature());
                    }

                    if ($activity->isPaywall()) {
                        $attachment->access_id = 0;
                        $attachment->hidden = true;

                        if (method_exists($attachment, 'setFlag')) {
                            $attachment->setFlag('paywall', true);
                        }
                    }

                    $attachment->save();

                    switch ($attachment->subtype) {
                      case "image":
                        $activity->setCustom('batch', [[
                          'src'=>elgg_get_site_url() . 'archive/thumbnail/'.$attachment->guid,
                          'href'=>elgg_get_site_url() . 'archive/view/'.$attachment->container_guid.'/'.$attachment->guid,
                          'mature'=>$attachment instanceof \Minds\Interfaces\Flaggable ? $attachment->getFlag('mature') : false
                        ]])
                        ->setFromEntity($attachment)
                        ->setTitle($attachment->message);
                        break;
                      case "video":
                        $activity->setFromEntity($attachment)
                            ->setCustom('video', array(
                            'thumbnail_src'=>$attachment->getIconUrl(),
                            'guid'=>$attachment->guid,
                            'mature'=>$attachment instanceof \Minds\Interfaces\Flaggable ? $attachment->getFlag('mature') : false))
                            ->setTitle($attachment->message);
                        break;
                    }
                }

                $container = null;

                if (isset($_POST['container_guid']) && $_POST['container_guid']) {
                    $activity->container_guid = $_POST['container_guid'];
                    if ($container = Entities\Factory::build($activity->container_guid)) {
                        $activity->containerObj = $container->export();
                    }
                    $activity->indexes = [
                      "activity:container:$activity->container_guid",
                      "activity:network:$activity->owner_guid"
                    ];
                }

                if ($guid = $activity->save()) {
                    Helpers\Wallet::createTransaction(Core\Session::getLoggedinUser()->guid, 10, $guid, 'Post');
                    Core\Events\Dispatcher::trigger('social', 'dispatch', array(
                        'entity' => $activity,
                        'services' => array(
                            'facebook' => isset($_POST['facebook']) && $_POST['facebook'] ? $_POST['facebook'] : false,
                            'twitter' => isset($_POST['twitter']) && $_POST['twitter'] ? $_POST['twitter'] : false
                        ),
                        'data' => array(
                            'message' => rawurldecode($_POST['message']),
                            'perma_url'=> isset($_POST['url']) ? \elgg_normalize_url(rawurldecode($_POST['url'])) : \elgg_normalize_url($activity->getURL()),
                            'thumbnail_src' =>  isset($_POST['thumbnail']) ? rawurldecode($_POST['thumbnail']) : null,
                            'description' => isset($_POST['description']) ? rawurldecode($_POST['description']) : null
                        )
                    ));

                    if ($container) {
                        Core\Events\Dispatcher::trigger('activity:container', $container->type, [
                            'container' => $container,
                            'activity' => $activity,
                        ]);
                    }

                    $activity->setExportContext(true);
                    return Factory::response(array('guid'=>$guid, 'activity'=> $activity->export()));
                } else {
                    return Factory::response(array('status'=>'failed', 'message'=>'could not save'));
                }
        }
    }

    public function put($pages)
    {
        $activity = new Entities\Activity($pages[0]);
        if (!$activity->guid) {
            return Factory::response(array('status'=>'error', 'message'=>'could not find activity post'));
        }

        switch ($pages[1]) {
          case 'view':
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
            }
            break;
        }

        return Factory::response(array());
    }

    public function delete($pages)
    {
        $activity = new Entities\Activity($pages[0]);

        if (!$activity->guid) {
            return Factory::response(array('status'=>'error', 'message'=>'could not find activity post'));
        }

        if (!$activity->canEdit()) {
            return Factory::response(array('status'=>'error', 'message'=>'you don\'t have permission'));
        }

        $owner = $activity->getOwnerEntity();

        if (
            $activity->entity_guid &&
            in_array($activity->custom_type, ['batch', 'video'])
        ) {
            // Delete attachment object
            try {
                $attachment = Entities\Factory::build($activity->entity_guid);

                if ($attachment && $owner->guid == $attachment->owner_guid) {
                    $attachment->delete();
                }
            } catch (\Exception $e) {
                error_log("Cannot delete attachment: {$activity->entity_guid}");
            }
        }

        if ($activity->delete()) {
            return Factory::response(array('message'=>'removed ' . $pages[0]));
        }

        return Factory::response(array('status'=>'error', 'message'=>'could not delete'));
    }
}
