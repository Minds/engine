<?php
/**
 * Minds Subscriptions
 *
 * @version 1
 * @author Mark Harding
 */
namespace Minds\Controllers\api\v1;

use Minds\Core;
use Minds\Core\Security;
use Minds\Core\Queue;
use Minds\Entities;
use Minds\Interfaces;
use Minds\Api\Factory;
use Minds\Helpers;
use Minds\Core\Subscriptions;

class subscribe implements Interfaces\Api
{
    /**
     * Returns the entities
     * @param array $pages
     *
     * API:: /v1/subscribe/subscriptions/:guid or /v1/subscribe/subscribers/:guid
     */
    public function get($pages)
    {
        $manager = new Subscriptions\Manager();
        $response = [];

        $guid = $pages[1] ?? Core\Session::getLoggedInUser()->guid;
        $publisher = Entities\Factory::build($guid);
        $type = $pages[0] ?? "subscribers";
        $limit = $_GET['limit'] ?? 12;
        $offset = $_GET['offset'] ?? "";

        if ($type === 'subscribers' && $publisher->username === 'minds') {
            return Factory::response([
                'status' => 'error',
                'message' => 'Unable to load subscribers for this channel',
            ]);
        }

        $opts = [
            'guid'=>$guid,
            'type'=>$type,
            'limit'=>$limit,
            'offset'=>$offset,
        ];
      
        $users = $manager->getList($opts);

        if (!$users) {
            return Factory::response([
                'status' => 'error',
                'message' => 'Unable to find '.$type,
            ]);
        }
        $pagingToken = (string) $users->getPagingToken();
        
        $users = array_filter(Factory::exportable($users->toArray()), function ($user) {
            return ($user->enabled != 'no' && $user->banned != 'yes');
        });

        $response['users'] = $users;
        $response['load-next'] = $pagingToken;

        return Factory::response($response);
    }

    /**
     * Subscribes a user to another
     * @param array $pages
     *
     * API:: /v1/subscriptions/:guid
     */
    public function post($pages)
    {
        Factory::isLoggedIn();

        if ($pages[0] === 'batch') {
            $guids = $_POST['guids'];

            //temp: captcha tests
            if (Core\Session::getLoggedInUser()->captcha_failed) {
                return Factory::response(['status' => 'error']);
            }

            Queue\Client::build()
              ->setQueue('SubscriptionDispatcher')
              ->send([
                  'currentUser' => Core\Session::getLoggedInUser()->guid,
                  'guids' => $guids
              ]);

            return Factory::response(['status' => 'success']);
        }

        $publisher = Entities\Factory::build($pages[0]);

        $canSubscribe = Security\ACL::_()->interact(Core\Session::getLoggedinUser(), $pages[0]) &&
            Security\ACL::_()->interact($pages[0], Core\Session::getLoggedinUser(), 'subscribe');

        if (!$canSubscribe) {
            return Factory::response([
                'status' => 'error'
            ]);
        }


        $manager = new Subscriptions\Manager();
        $subscription = $manager->setSubscriber(Core\Session::getLoggedinUser())
            ->subscribe($publisher);

        $response = [];
        if (!$subscription) {
            $response = [
                'status' => 'error',
                'message' => 'Subscribing failed',
            ];
        }

        //TODO: move Core/Subscriptions/Delegates
        $event = new Core\Analytics\Metrics\Event();
        $event->setType('action')
            ->setAction('subscribe')
            ->setProduct('platform')
            ->setUserGuid((string) Core\Session::getLoggedInUser()->guid)
            ->setUserPhoneNumberHash(Core\Session::getLoggedInUser()->getPhoneNumberHash())
            ->setEntityGuid((string) $pages[0])
            ->push();

        return Factory::response($response);
    }

    public function put($pages)
    {
    }

    public function delete($pages)
    {
        Factory::isLoggedIn();
        $publisher = Entities\Factory::build($pages[0]);

        $manager = new Subscriptions\Manager();
        $subscription = $manager->setSubscriber(Core\Session::getLoggedinUser())
            ->unSubscribe($publisher);

        $event = new Core\Analytics\Metrics\Event();
        $event->setType('action')
            ->setAction('unsubscribe')
            ->setProduct('platform')
            ->setUserGuid((string) Core\Session::getLoggedInUser()->guid)
            ->setUserPhoneNumberHash(Core\Session::getLoggedInUser()->getPhoneNumberHash())
            ->setEntityGuid((string) $pages[0])
            ->push();

        $response = ['status'=>'success'];
        if (!$subscription) {
            $response = [
                'status' => 'error'
            ];
        }

        return Factory::response($response);
    }
}
