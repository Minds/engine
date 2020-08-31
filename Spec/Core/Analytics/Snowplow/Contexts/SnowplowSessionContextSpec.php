<?php

namespace Spec\Minds\Core\Analytics\Snowplow\Contexts;

use Minds\Core\Analytics\Snowplow\Contexts\SnowplowSessionContext;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class SnowplowSessionContextSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(SnowplowSessionContext::class);
    }

    public function it_should_return_data()
    {
        $this->getData()
            ->shouldBe([
                'logged_in' => true,
            ]);
    }

    public function it_should_return_phone_numnber_hash()
    {
        $this->setUserPhoneNumberHash('hash');

        $this->getData()
            ->shouldBe([
                'logged_in' => true,
                'user_phone_number_hash' => 'hash',
            ]);
    }
}
