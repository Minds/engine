<?php

/**
 * Blockchain Boost preparation
 *
 * @author emi
 */

namespace Minds\Controllers\api\v2\boost;

use Minds\Api\Factory;
use Minds\Core\Boost\Checksum;
use Minds\Core\Di\Di;
use Minds\Core\Guid;
use Minds\Exceptions\UserErrorException;
use Minds\Interfaces;

class prepare implements Interfaces\Api
{
    /**
     * Equivalent to HTTP GET method
     * @param  array $pages
     * @return mixed|null
     */
    public function get($pages)
    {
        if (!isset($pages[0]) || !is_numeric($pages[0])) {
            return Factory::response([
                'status' => 'error',
                'message' => 'Specify an Entity GUID'
            ]);
        }

        $entity = Di::_()->get('EntitiesBuilder')->single($pages[0]);

        if ($entity->getNsfw() || $entity->getNsfwLock()) {
            throw new UserErrorException('NSFW content cannot be boosted.');
        }

        $guid = Guid::build();
        $checksum = (new Checksum())
            ->setGuid($guid)
            ->setEntity($entity)
            ->generate();

        return Factory::response([
            'guid' => $guid,
            'checksum' => $checksum
        ]);
    }

    /**
     * Equivalent to HTTP POST method
     * @param  array $pages
     * @return mixed|null
     */
    public function post($pages)
    {
        return Factory::response([]);
    }

    /**
     * Equivalent to HTTP PUT method
     * @param  array $pages
     * @return mixed|null
     */
    public function put($pages)
    {
        return Factory::response([]);
    }

    /**
     * Equivalent to HTTP DELETE method
     * @param  array $pages
     * @return mixed|null
     */
    public function delete($pages)
    {
        return Factory::response([]);
    }
}
