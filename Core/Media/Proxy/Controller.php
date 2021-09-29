<?php
/**
 * Minds Media Proxy
 *
 * @version 3
 */

namespace Minds\Core\Media\Proxy;

use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Zend\Diactoros\ServerRequest;

class Controller
{
    const MAX_TIME = 5;

    /** @var Config */
    protected $config;

    /** @var Download */
    protected $downloader;

    /** @var Resize */
    protected $resizer;

    public function __construct(
        $downloader = null,
        $resizer = null,
        $config = null
    ) {
        $this->downloader = $downloader ?: Di::_()->get('Media\Proxy\Download');
        $this->resizer = $resizer ?: Di::_()->get('Media\Proxy\Resize');
        $this->config = $config ?: Di::_()->get('Config');
    }

    /**
     * Proxies a media url
     * @param ServerRequest $request
     * @return mixed|null
     */
    public function proxy(ServerRequest $request)
    {
        $src = $request->getQueryParams()['src'];
        if (!isset($src)) {
            exit;
        }

        $size = $request->getQueryParams()['size'];
        $size = isset($size) ? (int)$size : 1024;
        if ($size < 0) {
            exit;
        }

        if (strpos($src, '//') === 0) {
            $src = 'https:' . $src;
        }

        $siteUrl = Di::_()->get('Config')->get('site_url');
        $cdnUrl = Di::_()->get('Config')->get('cdn_url');
        $mediaProxyRoutes = ["api/v2/media/proxy", "api/v3/media/proxy"];

        // loopback bug, so change domain to cdn
        if (strpos($src, $siteUrl) === 0) {
            $src = str_replace($siteUrl, $cdnUrl, $src);
        }

        // exit if src was a media proxy url
        foreach ($mediaProxyRoutes as $mediaProxyRoute) {
            if ($siteUrl && strpos($src, $siteUrl . $mediaProxyRoute) === 0) {
                exit;
            } elseif ($cdnUrl && strpos($src, $cdnUrl . $mediaProxyRoute) === 0) {
                exit;
            }
        }

        try {
            set_time_limit(static::MAX_TIME + 1);
            ini_set('max_execution_time', static::MAX_TIME + 1);

            $original = $this->downloader
                ->setSrc($src)
                ->setTimeout(static::MAX_TIME)
                ->download();

            $output = $this->resizer
                ->setImage($original)
                ->setSize($size)
                ->setUpscale(false)
                ->resize()
                ->getJpeg(75);

            $expires = date('r', strtotime('+6 months'));

            header("Expires: {$expires}", true);
            header('Pragma: public');
            header('Cache-Control: public');
            header('Content-Type: image/jpeg');

            echo $output;
        } catch (\Exception $e) {
            header("X-Minds-Exception: {$e->getMessage()}");
            http_response_code(415);
        }

        exit;
    }
}
