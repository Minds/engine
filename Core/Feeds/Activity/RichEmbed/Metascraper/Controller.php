<?php

namespace Minds\Core\Feeds\Activity\RichEmbed\Metascraper;

use Minds\Core\Feeds\Activity\RichEmbed\Metascraper\Cache\Manager as CacheManager;
use Minds\Exceptions\UserErrorException;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\Response\JsonResponse;

/**
 * Metascraper Controller.
 */
class Controller
{
    /**
     * Constructor.
     * @param CacheManager|null $cacheManager - cache manager.
     */
    public function __construct(private ?CacheManager $cacheManager = null)
    {
        $this->cacheManager ??= new CacheManager();
    }

    /**
     * Purge a URL from the cache.
     * @param ServerRequest $request - server request.
     * @throws UserErrorException - when missing URL parameter.
     * @return JsonResponse - success response.
     */
    public function purge(ServerRequest $request): JsonResponse
    {
        $url = urldecode($request->getQueryParams()['url'] ?? '');

        if (!$url) {
            throw new UserErrorException('Missing URL parameter');
        }

        $this->cacheManager->delete($url);

        return new JsonResponse([
            'status' => 200
        ]);
    }
}
