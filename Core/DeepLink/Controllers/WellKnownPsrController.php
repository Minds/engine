<?php
declare(strict_types=1);

namespace Minds\Core\DeepLink\Controllers;

use Minds\Core\DeepLink\Services\AndroidAssetLinksService;
use Minds\Core\DeepLink\Services\AppleAppSiteAssociationService;
use Zend\Diactoros\Response\JsonResponse;

/**
 * Controller for the well-known files relating to deep linking.
 */
class WellKnownPsrController
{
    public function __construct(
        private AndroidAssetLinksService $androidAssetLinksService,
        private AppleAppSiteAssociationService $appleAppSiteAssociationService,
    ) {
    }

    /**
     * Get the asset links file for Android.
     * @return JsonResponse The asset links file.
     */
    public function getAndroidAppLinks(): JsonResponse
    {
        return new JsonResponse($this->androidAssetLinksService->get());
    }

    /**
     * Get the apple app site association file.
     * @return JsonResponse The apple app site association file.
     */
    public function getAppleAppSiteAssosciations(): JsonResponse
    {
        return new JsonResponse($this->appleAppSiteAssociationService->get());
    }
}
