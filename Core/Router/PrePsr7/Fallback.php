<?php
/**
 * Fallback
 * @author edgebal
 */

namespace Minds\Core\Router\PrePsr7;

use Minds\Core\Config;
use Minds\Core\Di\Di;
use Minds\Core\SEO\Defaults as SEODefaults;
use Psr\Http\Message\RequestInterface;
use Zend\Diactoros\Response;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Diactoros\Stream;

class Fallback
{
    /** @var string[] */
    const ALLOWED = [
        '/api/v1/',
        '/api/v2/',
        '/emails/',
        '/fs/v1',
        '/oauth2/',
        '/checkout',
        '/deeplinks',
        '/icon',
        '/sitemap',
        '/sitemaps',
        '/thumbProxy',
        '/archive',
        '/wall',
        '/not-supported',
        '/apple-app-site-association',
    ];

    /** @var Config */
    protected $config;

    /**
     * Fallback constructor.
     * @param Config $config
     */
    public function __construct(
        $config = null
    ) {
        $this->config = $config ?: Di::_()->get('Config');
    }

    /**
     * @param string $route
     * @return bool
     */
    public function shouldRoute(string $route): bool
    {
        $route = sprintf("/%s", ltrim($route, '/'));
        $shouldFallback = false;

        foreach (static::ALLOWED as $allowedRoute) {
            if (stripos($route, $allowedRoute) === 0) {
                $shouldFallback = true;
                break;
            }
        }

        return $shouldFallback;
    }

    /**
     * @param RequestInterface $request
     * @return Response
     */
    public function handle(RequestInterface $request)
    {
        ob_clean();
        ob_start();
        (new Router())
            ->route($request->getUri()->getPath(), strtolower($request->getMethod()));

        $response = ob_get_contents();
        ob_end_clean();

        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $response);
        rewind($stream);

        return new Response(new Stream($stream), http_response_code());
    }

    /**
     * @param RequestInterface $request
     * @return HtmlResponse
     */
    public function handleStatic(RequestInterface $request)
    {
        ob_clean();
        ob_start();

        (new Router())
            ->route($request->getUri()->getPath(), strtolower($request->getMethod()));

        $html = ob_get_contents();
        ob_end_clean();

        return new HtmlResponse($html, 200);
    }

    /**
     * Complete routing fallback
     */
    public function route()
    {
        (new Router())->route();
    }
}
