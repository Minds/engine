<?php
namespace Minds\Core\Media\Audio;

use Minds\Core\Router\Exceptions\ForbiddenException;
use Minds\Core\Security\ACL;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\RedirectResponse;

class AudioPsrController
{
    public function __construct(
        private readonly AudioService $audioService,
        private readonly ACL $acl,
    ) {
        
    }

    /**
     * Redirects to the asset url
     */
    public function downloadAudioAsset(ServerRequestInterface $request): RedirectResponse
    {
        $guid = $request->getAttribute('parameters')['guid'];

        $audioEntity = $this->audioService->getByGuid($guid);

        if (!$this->acl->read($audioEntity)) {
            throw new ForbiddenException();
        }

        $downloadUrl = $this->audioService->getDownloadUrl($audioEntity);

        return new RedirectResponse($downloadUrl);
    }

}
