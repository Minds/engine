<?php
/**
 * Minds Banners FS endpoint
 *
 * @version 1
 * @author Mark Harding
 */
namespace Minds\Controllers\fs\v1;

use Minds\Core;
use Minds\Entities;
use Minds\Interfaces;
use Minds\Helpers\File;
use Minds\Core\Features\Manager as FeaturesManager;

define('DEFAULT_BANNER_PATHS', [
    'Assets/banners/0.jpg',
    'Assets/banners/1.jpg',
    'Assets/banners/2.jpg',
    'Assets/banners/3.jpg',
    'Assets/banners/4.jpg',
    'Assets/banners/5.jpg',
    'Assets/banners/6.jpg',
    'Assets/banners/7.jpg',
    'Assets/banners/8.jpg',
    'Assets/banners/9.jpg',
]);

class banners implements Interfaces\FS
{
    public function get($pages)
    {
        $entity = Entities\Factory::build($pages[0]);

        if (!$entity) {
            exit;
        }

        $type = '';
        $filepath = "";

        if (method_exists($entity, 'getType')) {
            $type = $entity->getType();
        } elseif (property_exists($entity, 'type')) {
            $type = $entity->type;
        }

        $content = "";


        switch ($type) {
          case "user":
            $size = isset($pages[1]) ? $pages[1] : 'fat';
            $carousels = Core\Entities::get(['subtype'=>'carousel', 'owner_guid'=>$entity->guid]);
            if ($carousels) {
                $f = new Entities\File();
                $f->owner_guid = $entity->guid;
                $f->setFilename("banners/{$carousels[0]->guid}.jpg");
                $f->open('read');
                $content = $f->read();
                if (!$content) {
                    $filepath =  Core\Config::build()->dataroot . 'carousel/' . $carousels[0]->guid . $size;
                    $f = Core\Di\Di::_()->get('Storage')->open($filepath, 'read');
                    $content = $f->read();
                }
            } else {
                $featuresManager = new FeaturesManager;
                if ($featuresManager->has('channels')) {
                    $content = file_get_contents(
                        Core\Config::build()->path . 'engine/' . $this->getSeededBannerPath($entity->guid)
                    );
                }
            }
            break;
          case "group":
            $f = new Entities\File();
            $f->owner_guid = $entity->owner_guid ?: $entity->getOwnerObj()->guid;
            $f->setFilename("group/{$entity->getGuid()}.jpg");
            $f->open('read');
            // no break
          case "object":
            break;
        }

        switch ($entity->subtype) {
          case "blog":
            $f = new Entities\File();
            $f->owner_guid = $entity->owner_guid;
            $f->setFilename("blog/{$entity->guid}.jpg");
            $f->open('read');
            break;
          case "cms":
            break;
          case "carousel":
            $size = isset($pages[1]) ? $pages[1] : 'fat';
            $f = new Entities\File();
            $f->owner_guid = $entity->owner_guid;
            $f->setFilename("banners/{$entity->guid}.jpg");
            $f->open('read');

            $content = $f->read();
            if (!$content) {
                $filepath =  Core\Config::build()->dataroot . 'carousel/' . $entity->guid . $size;
                $f = Core\Di\Di::_()->get('Storage')->open($filepath, 'read');
                $content = $f->read();
            }
            //$filepath = $f->getFilenameOnFilestore();
            //if (!file_exists($filepath)) {
            //    $filepath =  Core\Config::build()->dataroot . 'carousel/' . $entity->guid . $size;
            //}
            break;
        }


        //if (!file_exists($filepath)) {
        //    exit;
        //}

        if (!$content && $f) {
            $content = $f->read();
            if (!$content) {
                exit;
            }
        }

        $mimetype = File::getMime($content);

        header('Content-Type: '.$mimetype);
        header('Expires: ' . date('r', time() + 864000));
        header("Pragma: public");
        header("Cache-Control: public");
        echo $content;
        exit;
    }

    /**
     * Derives the seeded banner path for the user.
     *
     * @param string $guid - guid
     * @return string - banner path.
     */
    private function getSeededBannerPath(string $guid = '0'): string
    {
        return DEFAULT_BANNER_PATHS[$guid % count(DEFAULT_BANNER_PATHS)];
    }
}
