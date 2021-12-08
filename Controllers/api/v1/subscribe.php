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
        $offset = $_GET['offset'] ?? $_GET['from_timestamp'] ?? "";

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
        
        $users = array_filter($users->toArray(), function ($user) {
            return ($user->enabled != 'no' && $user->banned != 'yes' && $user->getUsername());
        });

        $response['users'] = array_values(Factory::exportable($users));
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

        $publisher = Entities\Factory::build($pages[0]);

        $canSubscribe = Security\ACL::_()->interact($publisher, Core\Session::getLoggedinUser(), 'subscribe');

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
