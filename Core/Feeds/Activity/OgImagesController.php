<?php
namespace Minds\Core\Feeds\Activity;

use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Media\Imagick;
use Minds\Core\Media\Thumbnails;
use Minds\Core\Router\Exceptions\UnauthorizedException;
use Minds\Core\Security\ACL;
use Minds\Entities\Activity;
use Minds\Exceptions\NotFoundException;
use Minds\Exceptions\UserErrorException;
use Zend\Diactoros\Response\TextResponse;
use Zend\Diactoros\ServerRequest;

class OgImagesController
{
    public function __construct(
        protected ?EntitiesBuilder $entitiesBuilder = null,
        protected ?ACL $acl = null,
        protected ?Imagick\Manager $imagickManager = null,
        protected ?Thumbnails $mediaThumbnails = null,
    ) {
        $this->entitiesBuilder ??= Di::_()->get('EntitiesBuilder');
        $this->acl ??= Di::_()->get('Security\ACL');
        $this->imagickManager ??= Di::_()->get('Media\Imagick\Manager');
        $this->mediaThumbnails ??= Di::_()->get('Media\Thumbnails');
    }

    /**
     *
     */
    public function renderOgImage(ServerRequest $request): TextResponse
    {
        /** @var string */
        $activityGuid = $request->getAttribute('parameters')['guid'] ?? '';

        $imageBlob = '';
        $contentType = 'image/png';

        try {
            if (!$activityGuid) {
                throw new UserErrorException('You must provide a guid');
            }
        
            /** @var Activity */
            $activity = $this->entitiesBuilder->single($activityGuid);

            if (!$activity instanceof Activity) {
                throw new NotFoundException();
            }

            /** @var User */
            $owner = $this->entitiesBuilder->single($activity->getOwnerGuid());

            if (!$this->acl->read($activity)) {
                throw new UnauthorizedException();
            }

            if ($activity->getSupermind() && $activity->getMessage()) {
                $image = $this->imagickManager->annotate(
                    width: 1000,
                    text: $activity->getMessage(),
                    username: $owner->getUsername(),
                );

                $imageBlob = $image->getImageBlob();
            } elseif ($activity->hasAttachments()) {
                $mediaGuid = $activity->getCustomType() === 'video' ?
                    $activity->getCustomData()['guid'] :
                    $activity->getCustomData()[0]['guid'];

                $thumbnail = $this->mediaThumbnails->get($mediaGuid, 'xlarge');
                $thumbnail->open('read');
                $imageBlob = $thumbnail->read();
                $contentType = 'image/jpeg';
            } else {
                throw new \Exception("Can't handle this yet. Default will display.");
            }
        } catch (\Exception $e) {
            $imageBlob = file_get_contents('./Assets/logos/og-default.png');
        }

        return new TextResponse($imageBlob, 200, [
            'Content-Type' => $contentType,
            'Expires' => date('r', strtotime('today + 6 months')),
            'Pragma' => 'public',
            'Cache-Control' => 'public',
            'Content-Length' => strlen($imageBlob)
        ]);
    }
}
