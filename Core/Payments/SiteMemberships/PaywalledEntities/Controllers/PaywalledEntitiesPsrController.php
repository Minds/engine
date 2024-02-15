<?php
namespace Minds\Core\Payments\SiteMemberships\PaywalledEntities\Controllers;

use Minds\Core\EntitiesBuilder;
use Minds\Entities\File;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response;
use Zend\Diactoros\Response\TextResponse;

class PaywalledEntitiesPsrController
{
    public function __construct(
        private EntitiesBuilder $entitiesBuilder
    ) {
        
    }
    
    /**
     * Renders a paywalled thumbnail
     */
    public function getThumbnail(ServerRequestInterface $request): Response
    {
        $guid = $request->getAttribute('parameters')['guid'];

        $activity = $this->entitiesBuilder->single($guid);

        $file = new File();
        $file->setFilename("paywall_thumbnails/{$activity->getGuid()}.jpg");
        $file->owner_guid = $activity->getOwnerGuid();
        $file->open('read');

        $contents = $file->read();

        return new TextResponse($contents, 200, [
            'headers' => [
                'Content-Type' => 'image/jpeg',
                'Expires: ' => date('r', time() + 864000),
                'Pragma' => 'public',
                'Cache-Control' => 'public',
            ]
        ]);
    }

}
