<?php
/**
 * pro
 * @author edgebal
 */

namespace Minds\Controllers\fs\v1;

use Minds\Core\Pro\Assets\Asset;
use Minds\Interfaces;

class pro implements Interfaces\FS
{
    /**
     * Equivalent to HTTP GET method
     * @param array $pages
     * @return mixed|null
     * @throws \IOException
     * @throws \InvalidParameterException
     * @throws \Exception
     */
    public function get($pages)
    {
        $asset = new Asset();
        $asset
            ->setType($pages[1] ?? null)
            ->setUserGuid($pages[0] ?? null);

        $file = $asset->getFile();
        $file->open('read');

        $contents = $file->read();

        header(sprintf("Content-Type: %s", $asset->getMimeType()));
        header(sprintf("Expires: %s", date('r', time() + 864000)));
        header('Pragma: public');
        header('Cache-Control: public');

        echo $contents;
        exit;
    }
}
