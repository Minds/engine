<?php

namespace Spec\Minds\Core\SMS\Services;

use Minds\Common\IpAddress;
use Minds\Core\Config;
use Minds\Core\Security\RateLimits\KeyValueLimiter;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Twilio\Rest\Client as TwilioClient;

class TwilioSpec extends ObjectBehavior
{
    /** @var TwilioClient */
    private $client;

    /** @var Config */
    private $config;

    /** @var KeyValueLimiter */
    private $kvLimiter;

    /** @var IpAddress */
    private $ipAddress;

    public function let(
        TwilioClient $client,
        Config $config,
        KeyValueLimiter $kvLimiter,
        IpAddress $ipAddress
    ) {
        $this->client = $client;
        $this->config = $config;
        $this->kvLimiter = $kvLimiter;
        $this->ipAddress = $ipAddress;

        $this->beConstructedWith($client, $config, $kvLimiter, $ipAddress);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType('Minds\Core\SMS\Services\Twilio');
    }

    public function it_should_check_kv_and_config_before_send()
    {
        $number = '+15005550006'; // Twilio magic valid number
        $message = 'Hello';
        $ipAddress = md5('127.0.0.1');

        $this->config->get('twilio')
            ->willReturn([]);

        $this->config->get('phone_number_hash_salt')
            ->shouldBeCalled()
            ->willReturn('123');

        $this->ipAddress->get()
            ->shouldBeCalled()
            ->willReturn($ipAddress);

        $this->kvLimiter->setKey('sms-sender-twilio')
            ->shouldBeCalled()
            ->willReturn($this->kvLimiter);

        $this->kvLimiter->setKey('sms-sender-twilio-ip')
            ->shouldBeCalled()
            ->willReturn($this->kvLimiter);

        $this->kvLimiter->setValue(Argument::type('string'))
            ->shouldBeCalledTimes(2)
            ->willReturn($this->kvLimiter);

        $this->kvLimiter->setSeconds(86400)
            ->shouldBeCalledTimes(2)
            ->willReturn($this->kvLimiter);

        $this->kvLimiter->setMax(5)
            ->shouldBeCalled()
            ->willReturn($this->kvLimiter);

        $this->kvLimiter->setMax(10)
            ->shouldBeCalled()
            ->willReturn($this->kvLimiter);

        $this->kvLimiter->checkAndIncrement()
            ->shouldBeCalledTimes(2)
            ->willReturn(true);

        $this->send($number, $message);
    }
}
