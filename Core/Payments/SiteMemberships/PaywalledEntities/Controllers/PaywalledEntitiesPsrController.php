<?php
namespace Minds\Core\Payments\SiteMemberships\PaywalledEntities\Controllers;

use Minds\Core\EntitiesBuilder;
use Minds\Core\Payments\SiteMemberships\PaywalledEntities\Services\PaywalledEntityService;
use Minds\Entities\File;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response;
use Zend\Diactoros\Response\RedirectResponse;
use Zend\Diactoros\Response\TextResponse;

class PaywalledEntitiesPsrController
{
    public function __construct(
        private EntitiesBuilder $entitiesBuilder,
        private PaywalledEntityService $paywalledEntityService,
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

    /**
     * Redirects to the checkout page
     */
    public function goToCheckout(ServerRequestInterface $request): RedirectResponse
    {
        $guid = $request->getAttribute('parameters')['guid'];
        $redirectPath = $request->getQueryParams()['redirectPath'] ?? '/memberships';

        $activity = $this->entitiesBuilder->single($guid);

        $membershipGuid = $this->paywalledEntityService->getLowestMembershipGuid($activity);

        return new RedirectResponse('/api/v3/payments/site-memberships/' . $membershipGuid . '/checkout?redirectPath=' . $redirectPath);
    }

}
