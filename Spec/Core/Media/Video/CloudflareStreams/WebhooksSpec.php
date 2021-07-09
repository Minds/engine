<?php

namespace Spec\Minds\Core\Media\Video\CloudflareStreams;

use Minds\Core\Config\Config;
use Minds\Core\Entities\Actions\Save;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Media\Video\CloudflareStreams\Client;
use Minds\Core\Media\Video\CloudflareStreams\Webhooks;
use Minds\Entities\Video;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\ServerRequest;

class WebhooksSpec extends ObjectBehavior
{
    /** @var Client */
    protected $client;

    /** @var Config */
    protected $config;

    /** @var EntitiesBuilder */
    protected $entitiesBuilder;

    /** @var Save */
    protected $save;

    public function let(Client $client, Config $config, EntitiesBuilder $entitiesBuilder, Save $save)
    {
        $this->beConstructedWith($client, $config, $entitiesBuilder, $save);
        $this->client = $client;
        $this->config = $config;
        $this->entitiesBuilder = $entitiesBuilder;
        $this->save = $save;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Webhooks::class);
    }

    public function it_should_register_the_webhook()
    {
        $this->config->get('site_url')
            ->willReturn('https://minds.local/');
    
        $this->client->request('PUT', 'stream/webhook', [
            'notificationUrl' => 'https://minds.local/api/v3/media/cloudflare/webhooks'
        ])
            ->willReturn(new JsonResponse([
                'result' => [
                    'secret' => 'cf-secret'
                    ]
            ]));

        $this->registerWebhook()
            ->shouldBe('cf-secret');
    }

    public function it_should_list_to_webhook_hit(ServerRequest $request)
    {
        $this->config->get('cloudflare')
            ->willReturn(['webhook_secret' => 'cf-signature']);

        $requestBody = json_encode([
                'meta' => [
                    'guid' => '123'
                ],
                'input' => [
                    'width' => 1280,
                    'height' => 1960
                ]
            ]);

        $request->getBody()->willReturn($requestBody);

        $request->getParsedBody()->willReturn(json_decode($requestBody, true));

        $ts = time();
        $sig1 = hash_hmac('sha256', $ts . '.' . $requestBody, 'cf-signature');
        $request->getHeader('Webhook-Signature')
            ->willReturn(["time=$ts,sig1=$sig1"]);

        $this->entitiesBuilder->single('123')
            ->willReturn(new Video());

        $this->save->setEntity(Argument::type(Video::class))
            ->willReturn($this->save);

        $this->save->save()->shouldBeCalled();


        $this->onWebhook($request);
    }
}
