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

class avatars implements Interfaces\Fs
{
    public function get($pages)
    {
        $entity = Entities\Factory::build($pages[0]);

        $size = strtolower($pages[1]);
        if (!in_array($size, ['large', 'medium', 'small', 'tiny', 'master', 'topbar'], true)) {
            $size = "medium";
        }

        $filepath = "";
        $guid = null;
        $type = null;

        if ($entity && method_exists($entity, 'getType')) {
            $type = $entity->getType();
        } elseif ($entity && property_exists($entity, 'type')) {
            $type = $entity->type;
        }

        if (method_exists($entity, 'getGuid')) {
            $guid = $entity->getGuid();
        } elseif (property_exists($entity, 'guid')) {
            $guid = $entity->guid;
        }

        switch ($type) {
            case "user":
                //coming soon
                break;
            case "group":
                $f = new Entities\File();
                $f->owner_guid = $entity->owner_guid;
                $f->setFilename("groups/{$guid}{$size}.jpg");
                $f->open('read');
                break;
            case "object":
                break;
        }

        $contents = isset($f) ? $f->read() : null;
        if (empty($contents)) {
            $filepath = Core\Config::build()->path . "engine/Assets/avatars/default-$size.png";
            $contents = file_get_contents($filepath);
        }

        if (!empty($contents)) {
            $mimetype = File::getMime($contents);
        } else {
            $mimetype = 'image/jpeg';
        }

        header('Content-Type: '.$mimetype);
        header('Expires: ' . date('r', time() + 864000));
        header("Pragma: public");
        header("Cache-Control: public");
        //header("ETag: \"$etag\"");
        echo $contents;
        exit;
    }
}
