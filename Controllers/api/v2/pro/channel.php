<?php
/**
 * channel
 * @author edgebal
 */

namespace Minds\Controllers\api\v2\pro;

use Exception;
use Minds\Core\Di\Di;
use Minds\Core\Pro\Channel\Manager as ChannelManager;
use Minds\Core\Pro\Manager;
use Minds\Core\Session;
use Minds\Entities\User;
use Minds\Interfaces;
use Minds\Api\Factory;

class channel implements Interfaces\Api
{
    /**
     * Equivalent to HTTP GET method
     * @param array $pages
     * @return mixed|null
     * @throws Exception
     */
    public function get($pages)
    {
        $currentUser = Session::getLoggedinUser();

        $channel = new User($pages[0]);
        $channel->fullExport = true; //get counts
        $channel->exportCounts = true;

        /** @var Manager $manager */
        $manager = Di::_()->get('Pro\Manager');
        $manager->setUser($channel);

        /** @var ChannelManager $manager */
        $channelManager = Di::_()->get('Pro\Channel\Manager');
        $channelManager->setUser($channel);

        switch ($pages[1] ?? '') {
            case 'content':
                return Factory::response([
                    'content' => $channelManager->getAllCategoriesContent(),
                ]);

            default:
                $exportedChannel = $channel->export();
                $exportedChannel['pro_settings'] = $manager->get();

                return Factory::response([
                    'channel' => $exportedChannel,
                    'me' => $currentUser ? $currentUser->export() : null,
                ]);
        }
    }

    /**
     * Equivalent to HTTP POST method
     * @param array $pages
     * @return mixed|null
     */
    public function post($pages)
    {
        return Factory::response([]);
    }

    /**
     * Equivalent to HTTP PUT method
     * @param array $pages
     * @return mixed|null
     */
    public function put($pages)
    {
        return Factory::response([]);
    }

    /**
     * Equivalent to HTTP DELETE method
     * @param array $pages
     * @return mixed|null
     */
    public function delete($pages)
    {
        return Factory::response([]);
    }
}
