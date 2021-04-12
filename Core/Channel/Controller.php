<?php
namespace Minds\Core\Channel;

use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\Entities\Actions\Save;
use Minds\Core\Channel\Manager;
use Minds\Exceptions\UserErrorException;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\ServerRequest;
use Minds\Api\Factory;

/**
 * Channel Controller
 * @package Minds\Core\Channel
 */
class Controller
{
    /** @var Manager */
    protected $manager;

    /**
     * Controller constructor.
     * @param null $manager
     */
    public function __construct($manager = null)
    {
        $this->manager = $manager ?? new Manager();
    }

    /**
     * @param ServerRequest $request
     */
    public function get(ServerRequest $request)
    {
        
        /** @var User */
        $user = $request->getAttribute('_user');

        $channel = $request->getAttribute('parameters')['channel'] ?? null;

        if ($channel == 'me') {
            $channel = $user->guid;
        }

        $response = $this->manager->getChannel($channel);

        if ($response) {
            return Factory::response($response);
        }
        
    }

    /**
     * @param ServerRequest $request
     */
    public function update(ServerRequest $request)
    {
        Factory::isLoggedIn();

        /** @var User */
        $owner = $request->getAttribute('_user');

        $guid = Core\Session::getLoggedinUser()->legacy_guid ?? $owner->guid;

        /** @var Core\Media\Imagick\Manager $manager */
        $manager = Core\Di\Di::_()->get('Media\Imagick\Manager');

        $response = [];

        switch ($request->getAttribute('parameters')['update']) {
            case "avatar":
                $response['avatar'] = true;
                break;
            case "banner":
                $response['banner'] = true;
                break;
            case "carousel":
                $response['carousel'] = true;
                break;
            case "info":
            default:
                $response['info'] = true;
        }

        return Factory::response($response);
        
    }

    /**
     * @param ServerRequest $request
     */
    public function delete(ServerRequest $request)
    {
        if (!Core\Session::getLoggedinUser()) {
            return Factory::response(['status' => 'error', 'message' => 'not logged in']);
        }

        switch ($request->getAttribute('parameters')['delete']) {
            case "carousel":
                $db = new Core\Data\Call('entities_by_time');
                //  $db->removeAttributes("object:carousel:user:" . elgg_get_logged_in_user_guid());
                $item = new \Minds\Entities\Object\Carousel($pages[1]);
                $item->delete();
                break;
            default:
                $channel = Core\Session::getLoggedinUser();
                $channel->enabled = 'no';
                $channel->save();

                (new Core\Data\Sessions())->destroyAll($channel->guid);
        }

        return Factory::response([]);
    }
}
