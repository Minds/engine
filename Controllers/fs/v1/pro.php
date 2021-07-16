<?php
/**
 * pro
 * @author edgebal
 */

namespace Minds\Controllers\fs\v1;

use Minds\Core\Pro\Assets\Asset;
use Minds\Interfaces;

class pro implements Interfaces\Fs
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

        if (!$contents) {
            $this->fallback($pages);
        }

        header(sprintf("Content-Type: %s", $asset->getMimeType()));
        header(sprintf("Expires: %s", date('r', time() + 864000)));
        header('Pragma: public');
        header('Cache-Control: public');

        echo $contents;
        exit;
    }

    /**
     * Fallback
     * @param array $pages
     * @return void
     */
    private function fallback($pages): void
    {
        switch ($pages[1]) {
            case "background":
                $bannersFs = new banners();
                $bannersFs->get([ $pages[0] ]);
                exit;
                break;
            case "logo":
                $avatarsFs = new avatars();
                $avatarsFs->get([ $pages[0], 'large' ]);
                exit;
                break;
        }
    }
}
