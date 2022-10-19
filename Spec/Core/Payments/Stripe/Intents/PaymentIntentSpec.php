<?php

namespace Spec\Minds\Core\Payments\Stripe\Intents;

use Minds\Core\Log\Logger;
use Minds\Core\Payments\Stripe\Intents\PaymentIntent;
use PhpSpec\ObjectBehavior;

class PaymentIntentSpec extends ObjectBehavior
{
    /** @var Logger */
    protected $logger;

    public function let(
        Logger $logger
    ) {
        $this->logger = $logger;
        $this->beConstructedWith($logger);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(PaymentIntent::class);
    }

    public function it_should_set_a_valid_descriptor_with_prefix()
    {
        $defaultDescriptor = 'Default';
        $descriptor = 'Payment';
        $this->setDescriptor($defaultDescriptor, false);
        $this->setDescriptor($descriptor);
        $this->getDescriptor()->shouldBe("Minds: $descriptor");
    }

    public function it_should_set_a_valid_descriptor_without_prefix()
    {
        $defaultDescriptor = 'Default';
        $descriptor = '22 character string xy';
        $this->setDescriptor($defaultDescriptor, false);
        $this->setDescriptor($descriptor, false);
        $this->getDescriptor()->shouldBe($descriptor);
    }

    public function it_should_NOT_set_an_invalid_descriptor_without_prefix()
    {
        $defaultDescriptor = 'Default';
        $descriptor = '23 character string xyz';
        $this->setDescriptor($defaultDescriptor, false);
        $this->setDescriptor($descriptor, false);
        $this->logger->error("PaymentIntent descriptor must be less than 22 characters: '$descriptor'")
            ->shouldBeCalled();
        $this->getDescriptor()->shouldBe($defaultDescriptor);
    }
}
