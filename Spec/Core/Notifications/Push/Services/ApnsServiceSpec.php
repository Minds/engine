<?php

namespace Spec\Minds\Core\Notifications\Push\Services;

use GuzzleHttp;
use Minds\Core\Config\Config;
use Minds\Core\Notifications\Push\Config\PushNotificationConfig;
use Minds\Core\Notifications\Push\Config\PushNotificationsConfigService;
use Minds\Core\Notifications\Push\DeviceSubscriptions\DeviceSubscription;
use Minds\Core\Notifications\Push\PushNotificationInterface;
use Minds\Core\Notifications\Push\Services\ApnsService;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;
use Zend\Diactoros\Response\JsonResponse;

class ApnsServiceSpec extends ObjectBehavior
{
    /** @var GuzzleHttp\Client */
    protected $client;
 
    /** @var Config */
    protected $config;

    protected Collaborator $pushNotificationsConfigServiceMock;

    public function let(GuzzleHttp\Client $client, Config $config, PushNotificationsConfigService $pushNotificationsConfigServiceMock)
    {
        $this->beConstructedWith($client, $config, $pushNotificationsConfigServiceMock);
        $this->client = $client;
        $this->config = $config;
        $this->pushNotificationsConfigServiceMock = $pushNotificationsConfigServiceMock;
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
        $pushNotification->getMetadata()
            ->willReturn([]);
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
            ]);

        $this->config->get('tenant_id')
            ->willReturn(null);
        
        $this->pushNotificationsConfigServiceMock->get(-1)
            ->willReturn(
                new PushNotificationConfig(
                    apnsTeamId: 'AAAAAAAAAA',
                    apnsKey: "-----BEGIN PRIVATE KEY-----
MIGTAgEAMBMGByqGSM49AgEGCCqGSM49AwEHBHkwdwIBAQQg/T6lLb143EDZgJN0
w2v0NkkNSnk6H3RH9qmbG2AoCQugCgYIKoZIzj0DAQehRANCAATOqY0vcF4ovskJ
ZZWlF2twsmzQG5AOYDBgVKRoCnQQ3Hm5XqbMM323q0NNFz6GeUOUHsM6HEzo/agb
askPiChu
-----END PRIVATE KEY-----",
                    apnsKeyId: 'BBBBBBBBBB',
                    apnsTopic: 'phpspec.app',
                )
            );
        
        $this->client->request('POST', 'https://api.push.apple.com/3/device/apple-device-token', Argument::that(function ($payload) {
            return $payload['headers']['apns-collapse-id'] === 'merge-key-will-be-here'
                && $payload['headers']['apns-topic'] = 'phpspec.app'
                && $payload['json']['aps']['alert']['body'] === 'This is the body line'
                && $payload['json']['uri'] === 'uri-here'
                && $payload['json']['largeIcon'] === 'icon-here'
                && $payload['json']['aps']['badge'] === 2;
        }))
            ->willReturn(new JsonResponse([], 200));

        $this->send($pushNotification);
    }
}
