<?php
/**
 * Minds media page controller.
 */

namespace Minds\Controllers\fs\v1;

use Imagick;
use Minds\Api\Factory;
use Minds\Core;
use Minds\Core\Di\Di;
use Minds\Entities;
use Minds\Helpers\File;
use Minds\Interfaces;
use Zend\Diactoros\Uri;

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

        error_log('@MDH2 - ABOUT TO CHECK JWT SIG');

        error_log(var_export($_SERVER, true));

        $signedUri = new Core\Security\SignedUri();
        $req = \Zend\Diactoros\ServerRequestFactory::fromGlobals()
            ->withMethod('GET')
            ->withUri(
                (new Uri(strtok($_SERVER['REDIRECT_ORIG_URI'] ?? $_SERVER['REQUEST_URI'], '?')))
                    ->withHost($_SERVER['HTTP_HOST'])
            );

        if ($req->getQueryParams()['jwtsig'] ?? null) {
            if ($signedUri->confirm((string) $req->getUri())) {
                error_log('@MDH2 - SETTING IGNORE FLAG IN THE ACL');
                Core\Security\ACL::$ignore = true;
                $unlockPaywall = (bool) $_GET['unlock_paywall'] ?? 0;
            }
        }
        error_log('@MDH2 - DONE CHECKING JWT SIG');
        $size = isset($pages[1]) ? $pages[1] : null;

        $entity = Entities\Factory::build($guid);

        if (!$entity) {
            return Factory::response([
                'status' => 'error',
                'message' => 'Entity not found',
            ]);
        }

        error_log('@MDH2 - ENTITY EXISTS');
        /** @var Core\Media\Thumbnails $mediaThumbnails */
        $mediaThumbnails = Di::_()->get('Media\Thumbnails');

        $opts = [
            'bypassPaywall' => true, // Bypasses payment amount check.
            'unlockPaywall' => $unlockPaywall, // Stops blurred image being set.
        ];

        error_log('@MDH2 - GETTING THUMBNAIL');
        $thumbnail = $mediaThumbnails->get($entity, $size, $opts);

        error_log('@MDH2 - GOT THUMBNAIL.');
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

            // Skip stripping EXIF data for Gifs since they don't support it.
            // This causes issues with them since only the first frame is read here.
            if (!(str_contains($contentType, 'image/gif'))) {
                $image = new Imagick();
                $image->readImageBlob($contents);

                $profiles = $image->getImageProfiles("icc", true);

                $image->stripImage();

                if (!empty($profiles)) {
                    $image->profileImage("icc", $profiles['icc']);
                }

                $contents = $image->getImageBlob();
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
