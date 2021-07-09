<?php
namespace Minds\Core\Media\Video\CloudflareStreams;

use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\Response\JsonResponse;
use Minds\Api\Exportable;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Di\Di;
use Minds\Entities\Video;
use Minds\Exceptions\UserErrorException;

class Controllers
{
    /** @var Manager */
    protected $manager;

    /** @var EntitiesBuilder */
    protected $entitiesBuilder;

    public function __construct($manager = null, $entitiesBuilder = null)
    {
        $this->manager = $manager ?? new Manager();
        $this->entitiesBuilder = $entitiesBuilder ?? Di::_()->get('EntitiesBuilder');
    }

    /**
     * @param ServerRequest $request
     * @return JsonResponse
     */
    public function sources(ServerRequest $request): JsonResponse
    {
        /** @var string */
        $guid = $request->getAttribute('parameters')['guid'];

        /** @var Video */
        $video = $this->entitiesBuilder->single($guid);

        if (!$video || !$video instanceof Video) {
            throw new UserErrorException("Video not found");
        }

        $sources = $this->manager->getSources($video);

        return new JsonResponse([
            'sources' => Exportable::_($sources),
        ]);
    }
}
