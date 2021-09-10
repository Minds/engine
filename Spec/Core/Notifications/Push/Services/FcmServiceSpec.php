<?php

namespace Spec\Minds\Core\Notifications\Push\Services;

use Google_Client;
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
    /** @var Google_Client */
    protected $googleClient;

    /** @var GuzzleHttp\Client */
    protected $client;
 
    /** @var Config */
    protected $config;
    
    public function let(Google_Client $googleClient, GuzzleHttp\Client $client, Config $config)
    {
        $this->beConstructedWith($googleClient, $client, $config);
        $this->googleClient = $googleClient;
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
        $pushNotification->getUserGuid()
            ->willReturn('123');

        $pushNotification->getIcon()
            ->willReturn('icon');

        $pushNotification->getMedia()
            ->willReturn('media');

        $deviceSubscription->getToken()
            ->willReturn('apple-device-token');

        $this->config->get('google')
            ->willReturn([
                'firebase' => [
                    'key_path' => 'firebase-api-key',
                    'project_id' => 'project_id',
                ],
            ]);

        $this->googleClient->setAuthConfig('firebase-api-key')
            ->shouldBeCalled();

        $this->googleClient->addScope(Argument::type('string'))
            ->shouldBeCalled();

        $this->googleClient->authorize()
            ->willReturn($this->client);

        $this->client->request('POST', Argument::any(), Argument::that(function ($payload) {
            return $payload['json']['message']['data']['title'] === 'This is the title line'
                && $payload['json']['message']['data']['body'] === 'This is the body line'
                && $payload['json']['message']['data']['uri'] === 'uri-here'
                && $payload['json']['message']['data']['largeIcon'] === 'icon';
        }))
            ->willReturn(new JsonResponse([], 200));

        $this->send($pushNotification);
    }
}
