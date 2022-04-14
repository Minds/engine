<?php
/**
 * Minds media page controller.
 */

namespace Minds\Controllers\fs\v1;

use Minds\Api\Factory;
use Minds\Core;
use Minds\Core\Di\Di;
use Minds\Core\Features\Manager as FeaturesManager;
use Minds\Entities;
use Minds\Entities\Video;
use Minds\Interfaces;
use Minds\Helpers\File;

class thumbnail extends Core\page implements Interfaces\page
{
    public function get($pages)
    {
        if (!$pages[0]) {
            exit;
        }

        $guid = $pages[0] ?? null;

        if (!$guid) {
            return Factory::response([
                'status' => 'error',
                'message' => 'guid must be provided',
            ]);
        }

        $unlockPaywall = false;

        $signedUri = new Core\Security\SignedUri();
        $req = \Zend\Diactoros\ServerRequestFactory::fromGlobals();
        if ($req->getQueryParams()['jwtsig'] ?? null) {
            if ($signedUri->confirm((string) $req->getUri())) {
                Core\Security\ACL::$ignore = true;
                $unlockPaywall = (bool) $_GET['unlock_paywall'] ?? 0;
            }
        }

        $size = isset($pages[1]) ? $pages[1] : null;

        $entity = Entities\Factory::build($guid);

        if (!$entity) {
            return Factory::response([
                'status' => 'error',
                'message' => 'Entity not found',
            ]);
        }

        $featuresManager = new FeaturesManager();

        /** @var Core\Media\Thumbnails $mediaThumbnails */
        $mediaThumbnails = Di::_()->get('Media\Thumbnails');

        $opts = [
            'bypassPaywall' => true, // Bypasses payment amount check.
            'unlockPaywall' => $unlockPaywall, // Stops blurred image being set.
        ];

        $thumbnail = $mediaThumbnails->get($entity, $size, $opts);

        if ($thumbnail instanceof \ElggFile) {
            $thumbnail->open('read');
            $contents = $thumbnail->read();

            if (!$contents && $size) {
                // Size might not exist
                $thumbnail = $mediaThumbnails->get($pages[0], null, $opts);
                $thumbnail->open('read');
                $contents = $thumbnail->read();
            }

            // if media was locked and empty return the default blurred
            if (!$contents && $mediaThumbnails->isLocked($entity)) {
                $contents = file_get_contents($mediaThumbnails->getDefaultBlurred());
            }

            try {
                $contentType = File::getMime($contents);
            } catch (\Exception $e) {
                error_log($e);
                $contentType = 'image/jpeg';
            }

            header('Content-type: '.$contentType);
            header('Expires: '.date('r', strtotime('today + 6 months')), true);
            header('Pragma: public');
            header('Cache-Control: public');
            header('Content-Length: '.strlen($contents));

            if (isset($_GET['download']) && $_GET['download']) {
                $filename = date('\m\i\n\d\s\_Ymd\_His');
                header('Content-Disposition: attachment; filename='.$filename);
            }

            $chunks = str_split($contents, 1024);
            foreach ($chunks as $chunk) {
                echo $chunk;
            }
        } elseif (is_string($thumbnail)) {
            \forward($thumbnail);
        }

        exit;
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
