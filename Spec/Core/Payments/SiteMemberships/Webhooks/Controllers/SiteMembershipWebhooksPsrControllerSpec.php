<?php

namespace Spec\Minds\Core\Payments\SiteMemberships\Webhooks\Controllers;

use Minds\Core\Payments\SiteMemberships\Services\SiteMembershipsRenewalsService;
use Minds\Core\Payments\SiteMemberships\Webhooks\Controllers\SiteMembershipWebhooksPsrController;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;

class SiteMembershipWebhooksPsrControllerSpec extends ObjectBehavior
{
    private Collaborator $siteMembershipsRenewalsServiceMock;

    public function let(SiteMembershipsRenewalsService $siteMembershipsRenewalsServiceMock)
    {
        $this->siteMembershipsRenewalsServiceMock = $siteMembershipsRenewalsServiceMock;
        $this->beConstructedWith($siteMembershipsRenewalsServiceMock);
    }

    public function it_is_initializable()
    {
        $this->shouldBeAnInstanceOf(SiteMembershipWebhooksPsrController::class);
    }

    public function it_should_process_subcription_renewal(
        ServerRequestInterface $request,
        StreamInterface $stream
    ): void {
        $payload = 'payload';
        $signature = 'signature';
        
        $stream->getContents()->willReturn($payload);
        $request->getBody()->willReturn($stream);
        $request->getHeader("STRIPE-SIGNATURE")->willReturn([$signature]);

        $this->siteMembershipsRenewalsServiceMock->processSubscriptionRenewalEvent(
            $payload,
            $signature
        )->shouldBeCalled();

        $this->processSubscriptionRenewal($request);
    }
}
