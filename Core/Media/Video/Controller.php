<?php
namespace Minds\Core\Media\Video;

use Minds\Api\Exportable;
use Minds\Exceptions\UserErrorException;
use Minds\Core\Media\Video\Manager;
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

    /**
     * Controller constructor.
     * @param null $manager
     */
    public function __construct(
        $manager = null
    ) {
        $this->manager = $manager ?? new Manager();
    }


    /**
     * Returns signed url with headers to be
     * used for downloading videos
     */
    public function getDownloadUrl(ServerRequest $request): JsonResponse
    {
        /** @var string */
        $guid = $request->getAttribute('parameters')['guid'];

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
