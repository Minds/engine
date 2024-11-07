<?php
namespace Minds\Core\Media\Audio;

use Minds\Core\Router\Exceptions\ForbiddenException;
use Minds\Core\Security\ACL;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\RedirectResponse;
use Zend\Diactoros\Response\TextResponse;

class AudioPsrController
{
    public function __construct(
        private readonly AudioService $audioService,
        private readonly AudioThumbnailService $audioThumbnailService,
        private readonly ACL $acl,
    ) {
        
    }

    /**
     * Redirects to the asset url
     */
    public function downloadAudioAsset(ServerRequestInterface $request): RedirectResponse
    {
        $guid = (int)  $request->getAttribute('parameters')['guid'];

        $audioEntity = $this->audioService->getByGuid($guid);

        if (!$this->acl->read($audioEntity)) {
            throw new ForbiddenException();
        }

        $downloadUrl = $this->audioService->getDownloadUrl($audioEntity);

        return new RedirectResponse($downloadUrl);
    }

    /**
     * Returns the thumbnail data
     */
    public function getThumbnail(ServerRequestInterface $request): TextResponse
    {
        $guid = $request->getAttribute('parameters')['guid'];

        $audioEntity = $this->audioService->getByGuid($guid);

        if (!$this->acl->read($audioEntity)) {
            throw new ForbiddenException();
        }

        $data = $this->audioThumbnailService->get($audioEntity);

        return new TextResponse($data, 200, [
            'Content-Type' => 'image/jpeg',
            'Expires' => date('r', strtotime("today+6 months")),
            'Pragma' => 'public',
            'Cache-Control' => 'public',
            'Content-Length' => strlen($data),
            'X-No-Client-Cache' => 0
        ]);
    }
}
