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
use Minds\Core\Analytics\Snowplow\Manager as SnowplowManager;
use Minds\Core\Features\Services\Cypress;

class ManagerSpec extends ObjectBehavior
{
    protected $growthbook;
    protected $cookieManager;
    protected $httpClient;
    protected $config;
    protected $psrCache;
    protected $snowplowManager;
    protected $cypress;

    public function let(
        Growthbook\Growthbook $growthbook,
        CookieManager $cookieManager,
        GuzzleHttp\Client $httpClient,
        Config $config,
        PsrWrapper $psrCache,
        SnowplowManager $snowplowManager,
        Cypress $cypress
    ) {
        $this->beConstructedWith(
            $growthbook,
            $cookieManager,
            $httpClient,
            $config,
            $psrCache,
            $snowplowManager,
            $cypress
        );

        $this->growthbook = $growthbook;
        $this->cookieManager = $cookieManager;
        $this->httpClient = $httpClient;
        $this->config = $config;
        $this->psrCache = $psrCache;
        $this->snowplowManager = $snowplowManager;
        $this->cypress = $cypress;

        $_SERVER['REQUEST_URI'] = '/';
        $_SERVER['HTTP_REFERER'] = '/newsfeed/subscriptions';

        $this->cypress->fetch([])
            ->shouldBeCalled()
            ->willReturn([]);

        $this->growthbook->withForcedVariations(Argument::any())
            ->shouldBeCalled()
            ->willReturn($this->growthbook);

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

        $this->growthbook->getForcedVariations(Argument::any())
            ->shouldBeCalled()
            ->willReturn([]);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }
}
