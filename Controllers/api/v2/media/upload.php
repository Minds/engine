<?php

/**
 * Client based upload
 *
 * @author Mark Harding
 */

namespace Minds\Controllers\api\v2\media;

use Minds\Api\Factory;
use Minds\Core\Di\Di;
use Minds\Core\Media\ClientUpload\Manager;
use Minds\Core\Session;
use Minds\Interfaces;
use Minds\Core\Media\ClientUpload\ClientUploadLease;
use Minds\Core\Media\ClientUpload\MediaTypeEnum;

class upload implements Interfaces\Api
{
    /**
     * Equivalent to HTTP GET method
     * @param  array $pages
     * @return mixed|null
     */
    public function get($pages)
    {
        return Factory::response([]);
    }


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
        /** @var Manager $manager */
        $manager = Di::_()->get(Manager::class);
        switch ($pages[0]) {
            case 'prepare':
                $mediaType = MediaTypeEnum::tryFrom($pages[1] ?? 'not-set');
                $lease = $manager->prepare($mediaType, Session::getLoggedinUser());
                return Factory::response([
                    'lease' => $lease->export(),
                ]);
                break;
            case 'complete':
                $mediaType = MediaTypeEnum::tryFrom($pages[1] ?? 'not-set');
                $guid = $pages[2] ?? null;

                $lease = new ClientUploadLease(
                    guid: $guid,
                    mediaType: $mediaType
                );
                $manager->complete($lease, Session::getLoggedinUser());
                break;
        }
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
