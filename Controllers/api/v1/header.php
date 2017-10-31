<?php
/**
 * Minds Blog API
 *
 * @version 1
 * @author Mark Harding
 */

namespace Minds\Controllers\api\v1;

use Minds\Api\Factory;
use Minds\Interfaces;

class header implements Interfaces\Api, Interfaces\ApiIgnorePam
{
    /**
     * Returns the conversations or conversation
     * @param array $pages
     *
     * API:: /v1/blog/:filter
     */
    public function get($pages)
    {
        $blog = new \Minds\Entities\Blog($pages[0]);
        $header = new \ElggFile();
        $header->owner_guid = $blog->owner_guid;
        $header->setFilename("blog/{$blog->guid}.jpg");
        $header->open('read');
        header('Content-Type: image/jpeg');
        header('Expires: ' . date('r', time() + 864000));
        header("Pragma: public");
        header("Cache-Control: public");

        try {
            echo $header->read();
        } catch (\Exception $e) {
        }
        exit;
    }

    public function post($pages)
    {
        return Factory::response(array());
    }

    public function put($pages)
    {
        return Factory::response(array());
    }

    public function delete($pages)
    {
        return Factory::response(array());
    }
}
