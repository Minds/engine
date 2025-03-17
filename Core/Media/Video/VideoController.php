<?php
namespace Minds\Core\Media\Video;

use Zend\Diactoros\Response\RedirectResponse;
use Zend\Diactoros\ServerRequest;

class VideoController
{
    public function __construct(private readonly Manager $videoManager)
    {
        
    }

    public function download(ServerRequest $request): RedirectResponse
    {
        $guid = $request->getAttribute('parameters')['guid'];
        $video = $this->videoManager->get($guid);
        $url = $this->videoManager->download($video);
        return new RedirectResponse($url);
    }
}
