<?php
/**
 * Minds media page controller
 */
namespace Minds\Controllers\fs\v1;

use Minds\Api\Factory;
use Minds\Common;
use Minds\Core;
use Minds\Core\Di\Di;
use Minds\Core\Features\Manager as FeaturesManager;
use Minds\Entities;
use Minds\Interfaces;
use Minds\Helpers\File;

class thumbnail extends Core\page implements Interfaces\page
{
    public function get($pages)
    {
        if (!$pages[0]) {
            exit;
        }

        Core\Security\ACL::$ignore = true;
        $guid = $pages[0] ?? null;

        if (!$guid) {
            return Factory::response([
                'status' => 'error',
                'message' => 'guid must be provided'
            ]);
        }

        $size = isset($pages[1]) ? $pages[1] : null;

        $entity = Entities\Factory::build($guid);

        if (!$entity) {
            return Factory::response([
                'status' => 'error',
                'message' => 'Entity not found'
            ]);
        }

        $featuresManager = new FeaturesManager;

        if ($entity->access_id !== Common\Access::PUBLIC && $featuresManager->has('cdn-jwt')) {
            $signedUri = new Core\Security\SignedUri();
            $uri = (string) \Zend\Diactoros\ServerRequestFactory::fromGlobals()->getUri();
            if (!$signedUri->confirm($uri)) {
                exit;
            }
        }

        /** @var Core\Media\Thumbnails $mediaThumbnails */
        $mediaThumbnails = Di::_()->get('Media\Thumbnails');


        $thumbnail = $mediaThumbnails->get($entity, $size, [ 'bypassPaywall' => true ]);
 
        if ($thumbnail instanceof \ElggFile) {
            $thumbnail->open('read');
            $contents = $thumbnail->read();

            if (!$contents && $size) {
                // Size might not exist
                $thumbnail = $mediaThumbnails->get($pages[0], null);
                $thumbnail->open('read');
                $contents = $thumbnail->read();
            }

            // Blur the image if paywalled
            // TODO: Consider moving this logic to a new controller

            $paywallManager = Di::_()->get('Wire\Paywall\Manager');
            
            if ($paywallManager->isPaywalled($entity)) {
                $allowed = $paywallManager
                    ->setUser(Core\Session::getLoggedInUser())
                    ->isAllowed($entity);
                $unlock = $_GET['unlock_paywall'] ?? false;
    
                if (!($unlock && $allowed)) {
                    $imagick = new \Imagick();
                    $imagick->readImageBlob($contents);
                    $imagick->blurImage(100, 500);
                    $contents = $imagick->getImageBlob();
                }
            }

            try {
                $contentType = File::getMime($contents);
            } catch (\Exception $e) {
                error_log($e);
                $contentType = 'image/jpeg';
            }

            header('Content-type: ' . $contentType);
            header('Expires: ' . date('r', strtotime('today + 6 months')), true);
            header('Pragma: public');
            header('Cache-Control: public');
            header('Content-Length: ' . strlen($contents));

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
