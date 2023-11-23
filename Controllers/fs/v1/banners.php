<?php
/**
 * Minds Banners FS endpoint
 *
 * @version 1
 * @author Mark Harding
 */
namespace Minds\Controllers\fs\v1;

use Minds\Core;
use Minds\Core\Channels\BannerService;
use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Entities;
use Minds\Interfaces;
use Minds\Helpers\File;

class banners implements Interfaces\Fs
{
    public function get($pages)
    {
        $entity = Entities\Factory::build($pages[0]);

        if (!$entity) {
            exit;
        }

        // Tenants banners are from the V2 banner system.
        if ((bool) Di::_()->get(Config::class)->get('tenant_id')) {
            $bannerV2Service = Di::_()->get(BannerService::class);

            $file = $bannerV2Service->getFile($entity->getGuid());
            $content = $file?->open('read')?->read();

            if (!$content) {
                $content = $bannerV2Service->getDefaultBannerContent($entity->getGuid());
            }

            header('Content-Type: ' . $file->getMimeType($content));
            header('Expires: ' . date('r', time() + 864000));
            header("Pragma: public");
            header("Cache-Control: public");
            echo $content;
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
                    $content = Di::_()->get(BannerService::class)
                        ->getDefaultBannerContent($entity->getGuid());
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

        if (!$content && isset($f)) {
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
}
