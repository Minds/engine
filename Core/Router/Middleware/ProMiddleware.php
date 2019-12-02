<?php
/**
 * ProMiddleware
 * @author edgebal
 */

namespace Minds\Core\Router\Middleware;

use Exception;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Pro\Manager;
use Minds\Core\Di\Di;
use Minds\Core\Pro\Domain;
use Minds\Core\Pro\SEO;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\ServerRequest;

class ProMiddleware implements RouterMiddleware
{
    /** @var Domain */
    protected $domain;

    /** @var Manager */
    protected $manager;

    /** @var SEO */
    protected $seo;

    /** @var EntitiesBuilder */
    protected $entitiesBuilder;

    /**
     * ProMiddleware constructor.
     * @param Domain $domain
     * @param Manager $manager
     * @param SEO $seo
     * @param EntitiesBuilder $entitiesBuilder
     */
    public function __construct(
        $domain = null,
        $manager = null,
        $seo = null,
        $entitiesBuilder = null
    ) {
        $this->domain = $domain ?: Di::_()->get('Pro\Domain');
        $this->manager = $manager ?: Di::_()->get('Pro\Manager');
        $this->seo = $seo ?: Di::_()->get('Pro\SEO');
        $this->entitiesBuilder = $entitiesBuilder ?: Di::_()->get('EntitiesBuilder');
    }

    /**
     * @param ServerRequest $request
     * @param JsonResponse $response
     * @return bool|null
     * @throws Exception
     */
    public function onRequest(ServerRequest $request, JsonResponse &$response): ?bool
    {
        $serverParams = $request->getServerParams() ?? [];
        $originalHost = $serverParams['HTTP_HOST'];

        $host = parse_url($serverParams['HTTP_ORIGIN'] ?? '', PHP_URL_HOST) ?: $originalHost;
        $scheme = parse_url($serverParams['HTTP_ORIGIN'] ?? '', PHP_URL_SCHEME) ?: $request->getUri()->getScheme();

        if (!$host) {
            return null;
        }

        $settings = $this->domain->lookup($host);

        if (!$settings) {
            return null;
        }

        header(sprintf("Access-Control-Allow-Origin: %s://%s", $scheme, $host));
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Max-Age: 86400');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Accept,Authorization,Cache-Control,Content-Type,DNT,If-Modified-Since,Keep-Alive,Origin,User-Agent,X-Mx-ReqToken,X-Requested-With,X-No-Cache,x-xsrf-token,x-pro-xsrf-jwt,x-minds-origin,x-version');

        if ($request->getMethod() === 'OPTIONS') {
            return false;
        }

        // Get Pro channel

        $user = $this->entitiesBuilder->single($settings->getUserGuid());

        // Hydrate with asset URLs

        $settings = $this->manager
            ->setUser($user)
            ->hydrate($settings);

        // Setup SEO

        $this->seo
            ->setUser($user)
            ->setup($settings);

        return null;
    }
}
