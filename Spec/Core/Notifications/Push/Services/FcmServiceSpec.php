<?php

namespace Spec\Minds\Core\Notifications\Push\Services;

use GuzzleHttp;
use Minds\Core\Config\Config;
use Minds\Core\Notifications\Push\DeviceSubscriptions\DeviceSubscription;
use Minds\Core\Notifications\Push\PushNotification;
use Minds\Core\Notifications\Push\Services\FcmService;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Zend\Diactoros\Response\JsonResponse;

class FcmServiceSpec extends ObjectBehavior
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
        $this->shouldHaveType(FcmService::class);
    }

    public function it_should_send_a_firebase_push_notification(PushNotification $pushNotification, DeviceSubscription $deviceSubscription)
    {
        $pushNotification->getTitle()
            ->willReturn('This is the title line');
        $pushNotification->getBody()
            ->willReturn('This is the body line');
        $pushNotification->getUnreadCount()
            ->willReturn(2);
        $pushNotification->getUri()
            ->willReturn('uri-here');
        $pushNotification->getMergeKey()
            ->willReturn('merge-key-will-be-here');
        $pushNotification->getDeviceSubscription()
            ->willReturn($deviceSubscription);
        $pushNotification->getGroup()
            ->willReturn('group');
        $pushNotification->getIcon()
            ->willReturn('icon');

        $deviceSubscription->getToken()
            ->willReturn('apple-device-token');

        $this->config->get('google')
            ->willReturn([
                'push' => 'firebase-api-key'
            ]);

        $this->client->request('POST', Argument::any(), Argument::that(function ($payload) {
            return $payload['json']['data']['title'] === 'This is the title line'
                && $payload['json']['data']['body'] === 'This is the body line'
                && $payload['json']['data']['group'] === 'group'
                && $payload['json']['data']['uri'] === 'uri-here'
                && $payload['json']['data']['largeIcon'] === 'icon';
        }))
            ->willReturn(new JsonResponse([], 200));

        $this->send($pushNotification);
    }
}
