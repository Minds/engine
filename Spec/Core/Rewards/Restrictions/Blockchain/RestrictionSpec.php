<?php

namespace Spec\Minds\Core\Rewards\Restrictions\Blockchain;

use PhpSpec\ObjectBehavior;
use Minds\Core\Rewards\Restrictions\Blockchain\Restriction;
use Minds\Exceptions\UserErrorException;

class RestrictionSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(Restriction::class);
    }

    public function it_should_get_and_set_an_address()
    {
        $address = '0x00';
        $this->setAddress($address);
        $this->getAddress()->shouldBe($address);
    }

    public function it_should_get_and_set_a_reason()
    {
        $reason = 'custom';
        $this->setReason($reason);
        $this->getReason()->shouldBe($reason);
    }

    public function it_should_NOT_get_and_set_an_unsupported_reason()
    {
        $reason = 'unsupported_reason';
        $this->shouldThrow(UserErrorException::class)->during('setReason', [$reason]);
    }

    public function it_should_get_and_set_a_network()
    {
        $network = 'ethereum';
        $this->setNetwork($network);
        $this->getNetwork()->shouldBe($network);
    }

    public function it_should_NOT_get_and_set_an_unsupported_network()
    {
        $network = 'unsupported_network';
        $this->shouldThrow(UserErrorException::class)->during('setNetwork', [$network]);
    }

    public function it_should_get_and_set_time_added()
    {
        $timeAdded = '1000';
        $this->setTimeAdded($timeAdded);
        $this->getTimeAdded()->shouldBe($timeAdded);
    }


    public function it_should_convert_class_to_string()
    {
        $address = '0x00';
        $reason = 'custom';
        $network = 'ethereum';
        $timeAdded = '1000';

        $this->setAddress($address)
            ->setReason($reason)
            ->setNetwork($network)
            ->setTimeAdded($timeAdded);

        $this->__toString()->shouldBe("Found: address: $address,\tnetwork: $network,\treason: $reason\t time_added: $timeAdded");
    }
}
