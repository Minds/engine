<?php
/**
 * Minds Admin: Ban
 *
 * @version 1
 * @author Emiliano Balbuena
 *
 */
namespace Minds\Controllers\api\v1\admin;

use Minds\Core;
use Minds\Core\Di\Di;
use Minds\Interfaces;
use Minds\Api\Factory;
use Minds\Core\EntitiesBuilder;
use Minds\Entities\User;

class ban implements Interfaces\Api, Interfaces\ApiAdminPam
{
    public function __construct(private ?EntitiesBuilder $entitiesBuilder = null)
    {
        $this->entitiesBuilder = Di::_()->get(EntitiesBuilder::class);
    }

    /**
     *
     */
    public function get($pages)
    {
        return Factory::response([]);
    }

    /**
     * @param array $pages
     */
    public function post($pages)
    {
        return Factory::response([]);
    }

    /**
     * Ban a user
     * @param array $pages
     */
    public function put($pages)
    {
        if (!isset($pages[0]) || !$pages[0]) {
            return [
                'error' => true
            ];
        }

        $user = $this->entitiesBuilder->single($pages[0]);

        if (!$user instanceof User) {
            return [
                'error' => true
            ];
        }

        $json = file_get_contents("php://input");
        $data = json_decode($json, true);
        $ban_reason = $data['note'] ?: $data['subject']['label'];

        /** @var Core\Channels\Ban $channelsBanManager */
        $channelsBanManager = Di::_()->get('Channels\Ban');

        $channelsBanManager
            ->setUser($user)
            ->ban($ban_reason);

        return Factory::response([
            'done' => true
        ]);
    }

    /**
     * Unban a user
     * @param array $pages
     */
    public function delete($pages)
    {
        if (!isset($pages[0]) || !$pages[0]) {
            return [
                'error' => true
            ];
        }

        $user = $this->entitiesBuilder->single($pages[0]);

        if (!$user instanceof User) {
            return [
                'error' => true
            ];
        }

        /** @var Core\Channels\Ban $channelsBanManager */
        $channelsBanManager = Di::_()->get('Channels\Ban');
        $channelsBanManager
            ->setUser($user)
            ->unban();

        return Factory::response([
            'done' => true
        ]);
    }
}
