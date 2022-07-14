<?php
/**
 * Minds Newsfeed API
 *
 * @version 1
 * @author Mark Harding
 */
namespace Minds\Controllers\api\v1\newsfeed;

use Minds\Core;
use Minds\Entities;
use Minds\Interfaces;
use Minds\Api\Factory;
use Minds\Core\Di\Di;

class preview implements Interfaces\Api
{
    /**
     * Returns a preview of a url
     * @param array $pages
     *
     * API:: /v1/newsfeed/preview
     */
    public function get($pages)
    {
        try {
            $meta = $this->getMetadata($_GET['url']);
        } catch (\Exception $e) {
            return Factory::response([
                'status' => 'error',
                'message' => 'An unknown error has occurred'
            ]);
        }
        return Factory::response($meta);
    }

    public function post($pages)
    {
        return Factory::response([]);
    }

    public function put($pages)
    {
        return Factory::response([]);
    }

    public function delete($pages)
    {
        return Factory::response([]);
    }

    /**
     * Get Metadata from either metascraper or iframely.
     * @param string $url - url to get metadata for.
     * @return array - response ready array.
     */
    private function getMetadata(string $url): array
    {
        if (Di::_()->get('Experiments\Manager')->isOn('front-5392-metascraper-previews')) {
            return Di::_()->get('Metascraper\Service')->scrape($url);
        }
        return Di::_()->get('Feeds\Activity\RichEmbed\Manager')->getRichEmbed($url);
    }
}
