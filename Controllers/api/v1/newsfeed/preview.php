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
use Minds\Core\Security\Spam;
use Minds\Exceptions\UserErrorException;

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
        $url = $_GET['url'] ?? false;

        if (!$url) {
            throw new UserErrorException('Missing URL parameter');
        }

        /** @var Spam */
        Di::_()->get('Security\Spam')->checkText($url);

        try {
            $meta = $this->getMetadata($url);
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
     * Get Metadata from metascraper.
     * @param string $url - url to get metadata for.
     * @return array - response ready array.
     */
    private function getMetadata(string $url): array
    {
        $data = Di::_()->get('Metascraper\Service')->scrape($url);

        if (isset($data['meta']['title']) && mb_strlen($data['meta']['title']) > 250) {
            $data['meta']['title'] = mb_substr($data['meta']['title'], 0, 247) . '...';
        }

        return $data;
    }
}
