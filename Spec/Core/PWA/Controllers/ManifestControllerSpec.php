<?php
declare(strict_types=1);

namespace Spec\Minds\Core\PWA\Controllers;

use Minds\Core\PWA\Controllers\ManifestController;
use Minds\Core\PWA\Models\AbstractPWAManifest;
use Minds\Core\PWA\Services\ManifestService;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Psr\Http\Message\ServerRequestInterface;
use Spec\Minds\Common\Traits\CommonMatchers;
use Zend\Diactoros\Response\JsonResponse;

class ManifestControllerSpec extends ObjectBehavior
{
    use CommonMatchers;

    protected Collaborator $service;

    public function let(
        ManifestService $service
    ): void {
        $this->service = $service;
        $this->beConstructedWith($this->service);
    }

    public function it_is_initializable(): void
    {
        $this->shouldHaveType(ManifestController::class);
    }

    public function it_should_return_exported_manifest_from_service(
        ServerRequestInterface $request,
        AbstractPWAManifest $manifest
    ): void {
        $exportedManifest = ['field1' => 'value1'];

        $this->service->getManifest()->willReturn($manifest);

        $manifest->export()->willReturn($exportedManifest);
        
        $this->getManifest($request)->shouldBeSameAs(
            new JsonResponse($exportedManifest)
        );
    }
}
