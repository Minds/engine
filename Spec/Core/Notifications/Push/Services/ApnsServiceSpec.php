<?php

namespace Spec\Minds\Core\Notifications\Push\Services;

use GuzzleHttp;
use Minds\Core\Config\Config;
use Minds\Core\Notifications\Push\DeviceSubscriptions\DeviceSubscription;
use Minds\Core\Notifications\Push\PushNotificationInterface;
use Minds\Core\Notifications\Push\Services\ApnsService;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Zend\Diactoros\Response\JsonResponse;

class ApnsServiceSpec extends ObjectBehavior
{
    /** @var GuzzleHttp\Client */
    protected $client;
 
    /** @var Config */
    protected $config;

    public function let(GuzzleHttp\Client $client, Config $config)
    {
        $this->beConstructedWith($client, $config);
        $this->client = $client;
        $this->config = $config;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(ApnsService::class);
    }

    public function it_should_send_an_apns_push_notification(PushNotificationInterface $pushNotification, DeviceSubscription $deviceSubscription)
    {
        $pushNotification->getTitle()
            ->willReturn('This is the title line');
        $pushNotification->getBody()
            ->willReturn('This is the body line');
        $pushNotification->getUnreadCount()
            ->willReturn(2);
        $pushNotification->getUri()
            ->willReturn('uri-here');
        $pushNotification->getIcon()
            ->willReturn('icon-here');
        $pushNotification->getMergeKey()
            ->willReturn('merge-key-will-be-here');
        $pushNotification->getDeviceSubscription()
            ->willReturn($deviceSubscription);
        $pushNotification->getUserGuid()
            ->willReturn('123');

        $deviceSubscription->getToken()
            ->willReturn('apple-device-token');

        $deviceSubscription->getUserGuid()
            ->willReturn('123');

        $this->config->get('apple')
            ->willReturn([
                'sandbox' => false,
                'cert' => '/path/to/cert'
            ]);

        $this->client->request('POST', 'https://api.push.apple.com/3/device/apple-device-token', Argument::that(function ($payload) {
            return $payload['headers']['apns-collapse-id'] === 'merge-key-will-be-here'
                && $payload['cert'] === '/path/to/cert'
                && $payload['json']['aps']['alert']['body'] === 'This is the title line: This is the body line'
                && $payload['json']['uri'] === 'uri-here'
                && $payload['json']['largeIcon'] === 'icon-here'
                && $payload['json']['aps']['badge'] === 2;
        }))
            ->willReturn(new JsonResponse([], 200));

        $this->send($pushNotification);
    }
}
