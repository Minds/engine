<?php

namespace Spec\Minds\Core\DeepLink\Controllers;

use Minds\Core\DeepLink\Controllers\WellKnownPsrController;
use Minds\Core\DeepLink\Services\AndroidAssetLinksService;
use Minds\Core\DeepLink\Services\AppleAppSiteAssociationService;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;

class WellKnownPsrControllerSpec extends ObjectBehavior
{
    private Collaborator $androidAssetLinksServiceMock;
    private Collaborator $appleAppSiteAssociationServiceMock;
 
    public function let(
        AndroidAssetLinksService $androidAssetLinksServiceMock,
        AppleAppSiteAssociationService $appleAppSiteAssociationServiceMock
    ) {
        $this->beConstructedWith(
            $androidAssetLinksServiceMock,
            $appleAppSiteAssociationServiceMock,
        );
        $this->androidAssetLinksServiceMock = $androidAssetLinksServiceMock;
        $this->appleAppSiteAssociationServiceMock = $appleAppSiteAssociationServiceMock;
    }

    public function it_is_initializable(): void
    {
        $this->shouldHaveType(WellKnownPsrController::class);
    }

    public function it_should_return_json_response_for_android_app_links(): void
    {
        $this->androidAssetLinksServiceMock->get()->willReturn(['assetlinks' => 'json']);
        $this->getAndroidAppLinks()
            ->getBody()
            ->getContents()
            ->shouldBeLike(json_encode(['assetlinks' => 'json']));
    }

    public function it_should_return_json_response_for_apple_app_site_associations(): void
    {
        $this->appleAppSiteAssociationServiceMock->get()->willReturn(['apple-app-site-association' => 'json']);
        $this->getAppleAppSiteAssosciations()
            ->getBody()
            ->getContents()
            ->shouldBeLike(json_encode(['apple-app-site-association' => 'json']));
    }
}
