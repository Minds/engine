<?php
namespace Minds\Core\Media\Video;

use Minds\Api\Exportable;
use Minds\Exceptions\UserErrorException;
use Minds\Core\Media\Video\Manager;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Di\Di;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\ServerRequest;
use Minds\Entities\Video;

/**
 * Video Controller
 * @package Minds\Core\Media\Video
 */
class Controller
{
    /** @var Manager */
    protected $manager;

    /** @var EntitiesBuilder */
    protected $entitiesBuilder;
    /**
     * Controller constructor.
     * @param null $manager
     */
    public function __construct(
        $manager = null,
        $entitiesBuilder = null
    ) {
        $this->manager = $manager ?? new Manager();
        $this->entitiesBuilder = $entitiesBuilder ?? Di::_()->get('EntitiesBuilder');
    }


    /**
     * Returns signed url with headers to be
     * used for downloading videos
     */
    public function getDownloadUrl(ServerRequest $request): JsonResponse
    {
        /** @var string */
        $guid = $request->getAttribute('parameters')['guid'] ?? null;

        if (!$guid) {
            throw new UserErrorException('Invalid GUID');
        }
        // /** @var Video */
        $video = $this->entitiesBuilder->single($guid);

        if (!$video || !$video instanceof Video) {
            throw new UserErrorException("Video not found");
        }

        $url = $this->manager->getPublicAssetUri($video, 'source', true);

        if (!$url) {
            throw new UserErrorException("Cannot access video");
        }

        return new JsonResponse([
            'status' => 'success',
            'url' => $url,
        ]);
    }
}
