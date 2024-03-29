<?php
/**
 * Minds Storage Provider
 */

namespace Minds\Core\Storage;

use Minds\Core\Di\Provider;

class StorageProvider extends Provider
{
    public function register()
    {
        $this->di->bind('Storage\Disk', function ($di) {
            return new Services\Disk();
        }, ['useFactory'=>false]);

        $this->di->bind('Storage\S3', function ($di) {
            return new Services\S3();
        }, ['useFactory'=>false]);

        $this->di->bind('Storage', function ($di) {
            $config = $di->get('Config');
            if ($config->get('storage')['engine'] ?? false) {
                return $di->get('Storage\\' . $config->get('storage')['engine']);
            }
            return $di->get('Storage\Disk');
        }, ['useFactory'=>false]);
    }
}
