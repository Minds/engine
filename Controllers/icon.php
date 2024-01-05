<?php

/**
 * Minds main page controller
 */

namespace Minds\Controllers;

use Imagick;
use ImagickDraw;
use ImagickPixel;
use LasseRafn\InitialAvatarGenerator\InitialAvatar;
use Minds\Core;
use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Entities\User;
use Minds\Interfaces;

class icon extends core\page implements Interfaces\page
{
    private const DEFAULT_AVATAR_COLORS = [
        [
            'color' => '#C60906',
            'background' => '#FF8C82'
        ],
        [
            'color' => '#CC2900',
            'background' => '#FFC27D'
        ],
        [
            'color' => '#CC7000',
            'background' => '#FFF394'
        ],
        [
            'color' => '#2B8F00',
            'background' => '#D1FF82'
        ],
        [
            'color' => '#2B8F00',
            'background' => '#D1FF82'
        ],
        [
            'color' => '#008F81',
            'background' => '#89FFDD'
        ],
        [
            'color' => '#0037B8',
            'background' => '#8CD5FF'
        ],
        [
            'color' => '#53319B',
            'background' => '#E4C6FA'
        ],
        [
            'color' => '#A71140',
            'background' => '#FE9AB8'
        ],
        [
            'color' => '#7A0000',
            'background' => '#FF8C82'
        ],
        [
            'color' => '#A62100',
            'background' => '#FFC27D'
        ],
        [
            'color' => '#AD5F00',
            'background' => '#FFF394'
        ],
        [
            'color' => '#206B00',
            'background' => '#D1FF82'
        ],
        [
            'color' => '#007367',
            'background' => '#89FFDD'
        ],
        [
            'color' => '#002E99',
            'background' => '#8CD5FF'
        ],
        [
            'color' => '#452981',
            'background' => '#E4C6FA'
        ],
        [
            'color' => '#910E38',
            'background' => '#FE9AB8'
        ],
    ];

    private const SIZES = [
        'xlarge' => 960,
        'large' => 425,
        'medium' => 100,
        'small' => 40,
        'tiny' => 25,
        'master' => 425,
        'topbar' => 100,
    ];

    /**
     * Get requests
     */
    public function get($pages)
    {
        global $CONFIG;
        $guid = $pages[0];

        if (!$guid) {
            exit;
        }


        $cacher = Core\Data\cache\factory::build('apcu');
        //if ($cached = $cacher->get("usericon:$guid")) {
        //    $join_date = $cached;
        //} else {
        $user = Di::_()->get(EntitiesBuilder::class)->single($guid, ['cacheTtl' => 259200]);

        if (isset($user->legacy_guid) && $user->legacy_guid) {
            $guid = $user->legacy_guid;
        }
        $join_date = $user->time_created;
        //    $cacher->set("usericon:$guid", $join_date);
        //}
        $last_cache = isset($pages[2]) ? $pages[2] : time();
        $etag = $last_cache . $guid;
        if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && trim($_SERVER['HTTP_IF_NONE_MATCH']) == $etag) {
            header("HTTP/1.1 304 Not Modified");
            exit;
        }
        $size = strtolower($pages[1]);
        if (!in_array($size, array_keys(self::SIZES), true)) {
            $size = "medium";
        }

        //check the user is enabled
        if ($user->enabled == 'no') {
            $contents = file_get_contents(Core\Config::build()->path . "engine/Assets/avatars/default-$size.png");
            $this->returnImage($contents, $etag);
        }

        $data_root = $CONFIG->dataroot;

        $file = new \ElggFile();
        $file->owner_guid = $user->getGuid();
        $file->setFilename("profile/{$guid}{$size}.jpg");
        $file->open("read");

        $contents = $file->read();

        if (empty($contents)) {
            $contents = $this->generateDefaultUserAvatar($user, $size);
        }

        $this->returnImage($contents, $etag);
    }

    private function returnImage($contents, $etag)
    {
        if (!empty($contents)) {
            header("Content-type: image/jpeg");
            header('Expires: ' . date('r', strtotime("today+6 months")), true);
            header("Pragma: public");
            header("Cache-Control: public");
            header("Content-Length: " . strlen($contents));
            header("ETag: $etag");
            header("X-No-Client-Cache:0");
            // this chunking is done for supposedly better performance
            $split_string = str_split($contents, 1024);
            foreach ($split_string as $chunk) {
                echo $chunk;
            }
        }
        exit;
    }

    private function generateDefaultUserAvatar(User $user, string $size): string
    {
        $avatarColorDetails = self::DEFAULT_AVATAR_COLORS[mt_rand(0, count(self::DEFAULT_AVATAR_COLORS) - 1)];
        /**
         * @var Core\Data\cache\Redis $cache
         */
        $cache = Di::_()->get('Cache');

        if ($cachedColorDetails = $cache->get("usericon:{$user->getGuid()}")) {
            $avatarColorDetails = $cachedColorDetails;
        }

        $cache->set("usericon:{$user->getGuid()}", $avatarColorDetails, 86400); // cached for 1 day

        $avatar = new Imagick();
        $avatar->newImage(self::SIZES[$size], self::SIZES[$size], new ImagickPixel($avatarColorDetails['background']), 'jpg');
        $avatarText = strtoupper(mb_str_split($user->getUsername())[0]);

        $text = new ImagickDraw();
        $text->setFont(__MINDS_ROOT__ . "/Assets/fonts/Inter/Inter-Bold.ttf");
        $text->setFontSize(50 * self::SIZES[$size] / 100);
        $text->setFillColor(new ImagickPixel($avatarColorDetails['color']));
        $text->setFontWeight(700);
        $text->setTextAntialias(true);
        $text->setGravity(Imagick::GRAVITY_CENTER);
        $text->setStrokeAntialias(true);

        $avatar->annotateImage($text, 0, 0, 0, $avatarText);

        return $avatar->getImageBlob();
    }

    public function post($pages)
    {
    }

    public function put($pages)
    {
    }

    public function delete($pages)
    {
    }
}
