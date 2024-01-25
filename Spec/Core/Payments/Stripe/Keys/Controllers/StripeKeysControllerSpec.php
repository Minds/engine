<?php

namespace Spec\Minds\Core\Payments\Stripe\Keys\Controllers;

use Minds\Core\Payments\Stripe\Keys\Controllers\StripeKeysController;
use Minds\Core\Payments\Stripe\Keys\StripeKeysService;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;

class StripeKeysControllerSpec extends ObjectBehavior
{
    private Collaborator $serviceMock;

    function let(StripeKeysService $serviceMock)
    {
        $this->beConstructedWith($serviceMock);
        $this->serviceMock = $serviceMock;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(StripeKeysController::class);
    }

    public function it_should_set_stripe_keys()
    {
        $this->serviceMock->setKeys('pub', 'sec')
            ->willReturn(true);

        $this->setStripeKeys('pub', 'sec', new User())
            ->shouldBe(true);
    }

    public function it_should_return_stripe_keys()
    {
        $this->serviceMock->getPubKey()
            ->willReturn('pub');

        $keys = $this->getStripeKeys(new User());
        $keys->pubKey->shouldBe('pub');
        $keys->secKey->shouldBe('REDACTED');
    }
}
