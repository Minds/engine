<?php

namespace Spec\Minds\Core\Experiments;

use Minds\Core\Config\Config;
use Minds\Core\Experiments\Manager;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Growthbook;
use Zend\Diactoros\Response\JsonResponse;
use Minds\Core\Experiments\Cookie\Manager as CookieManager;
use GuzzleHttp;
use Minds\Core\Data\cache\PsrWrapper;
use Minds\Core\Data\cache\WorkerCache;

class ManagerSpec extends ObjectBehavior
{
    protected $growthbook;
    protected $cookieManager;
    protected $httpClient;
    protected $config;
    protected $cacheMock;

    public function let(
        Growthbook\Growthbook $growthbook,
        CookieManager $cookieManager,
        GuzzleHttp\Client $httpClient,
        Config $config,
        WorkerCache $cacheMock,
    ) {
        $this->beConstructedWith(
            $growthbook,
            $cookieManager,
            $httpClient,
            $config,
            $cacheMock
        );

        $this->growthbook = $growthbook;
        $this->cookieManager = $cookieManager;
        $this->httpClient = $httpClient;
        $this->config = $config;
        $this->cacheMock = $cacheMock;

        $_SERVER['REQUEST_URI'] = '/';
        $_SERVER['HTTP_REFERER'] = '/newsfeed/subscriptions';

        $this->growthbook->withFeatures(Argument::any())
            ->shouldBeCalled()
            ->willReturn($this->growthbook);

        $this->cookieManager->get(Argument::any())
            ->shouldBeCalled()
            ->willReturn('123');

        $this->growthbook->withAttributes(Argument::any())
            ->shouldBeCalled()
            ->willReturn($this->growthbook);

        $this->growthbook->withTrackingCallback(Argument::any())
            ->willReturn($this->growthbook);

        $this->config->get('growthbook')
            ->shouldBeCalled()
            ->willReturn([
                'features_endpoint' => 'https://growthbook-api.phpspec.test/api/features/key_stub',
            ]);

        $this->httpClient->request('GET', 'https://growthbook-api.phpspec.test/api/features/key_stub', Argument::any())
            ->shouldBeCalled()
            ->willReturn(new JsonResponse([
                'features' => [
                    'discovery-homepage' => [
                        'defaultValue' => false,
                    ]
                ],
            ]));

        $this->cacheMock->get('growthbook-features')->willReturn(null);
        $this->cacheMock->set('growthbook-features', Argument::type('array'))->willReturn(true);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }
}
