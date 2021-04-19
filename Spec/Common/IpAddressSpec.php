<?php

namespace Spec\Minds\Common;

use Minds\Common\IpAddress;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Zend\Diactoros\ServerRequest;

class IpAddressSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(IpAddress::class);
    }

    public function it_should_get_ip_from_request(ServerRequest $request)
    {
        $request->getHeader('X-FORWARDED-FOR')
            ->willReturn(['10.0.10.27']);
        $this->setServerRequest($request)->get()->shouldBe('10.0.10.27');
    }

    public function it_should_get_ip_from_request_with_multiple_values(ServerRequest $request)
    {
        $request->getHeader('X-FORWARDED-FOR')
            ->willReturn(['10.0.10.27, 127.0.0.1']);
        $this->setServerRequest($request)->get()->shouldBe('10.0.10.27');
    }
}
