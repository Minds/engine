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
        $this->setStatementDescriptor($defaultDescriptor, false);
        $this->setStatementDescriptor($descriptor);
        $this->getStatementDescriptor()->shouldBe("Minds: $descriptor");
    }

    public function it_should_set_a_valid_descriptor_without_prefix()
    {
        $defaultDescriptor = 'Default';
        $descriptor = '22 character string xy';
        $this->setStatementDescriptor($defaultDescriptor, false);
        $this->setStatementDescriptor($descriptor, false);
        $this->getStatementDescriptor()->shouldBe($descriptor);
    }

    public function it_should_NOT_set_an_invalid_descriptor_without_prefix()
    {
        $defaultDescriptor = 'Default';
        $descriptor = '23 character string xyz';
        $this->setStatementDescriptor($defaultDescriptor, false);
        $this->setStatementDescriptor($descriptor, false);
        $this->logger->error("PaymentIntent statement descriptor must be less than 22 characters: '$descriptor'")
            ->shouldBeCalled();
        $this->getStatementDescriptor()->shouldBe($defaultDescriptor);
    }

    public function it_should_get_description()
    {
        $description = 'Minds Payment';
        $this->setStatementDescriptor($description, false)
            ->getStatementDescriptor()
            ->shouldBe($description);
    }
}
