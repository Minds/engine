<?php

namespace Spec\Minds\Core\Feeds\Activity\RichEmbed\Metascraper;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Stream\StreamInterface;
use Minds\Core\Config\Config;
use Minds\Core\Feeds\Activity\RichEmbed\Metascraper\Service;
use Minds\Core\Feeds\Activity\RichEmbed\Metascraper\Cache\Manager as CacheManager;
use Minds\Core\Log\Logger;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Psr\Http\Message\ResponseInterface;

class ServiceSpec extends ObjectBehavior
{
    /** @var ClientInterface */
    protected $httpClient;

    /** @var Logger */
    protected $logger;

    /** @var Config */
    protected $config;

    /** @var CacheManager */
    protected $cacheManager;

    public function let(
        ClientInterface $httpClient,
        Logger $logger,
        Config $config,
        CacheManager $cacheManager
    ) {
        $this->beConstructedWith(
            $httpClient,
            $logger,
            $config,
            $cacheManager
        );

        $this->httpClient = $httpClient;
        $this->logger = $logger;
        $this->config = $config;
        $this->cacheManager = $cacheManager;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Service::class);
    }

    public function it_should_return_scraped_data(
        ResponseInterface $response,
        StreamInterface $streamInterface
    ) {
        $url = 'https://www.minds.com/';

        $this->config->get('metascraper')
            ->shouldBeCalledTimes(3)
            ->willReturn([
                'base_url' => 'localhost:3333/',
                'bypass_cache' => false,
            ]);

        $this->cacheManager->getExported($url)
            ->shouldBeCalled()
            ->willReturn(null);

        $streamInterface->getContents()
            ->shouldBeCalled()
            ->willReturn('{"status":200,"data": {
                "url": "url",
                "description": "description",
                "title": "title",
                "author": "author",
                "image": "image",
                "logo": "logo",
                "iframe": "iframe"
            }}');

        $response->getBody()
            ->shouldBeCalled()
            ->willReturn($streamInterface);

        $this->httpClient->request(
            'GET',
            'localhost:3333/scrape',
            Argument::any()
        )
            ->shouldBeCalled()
            ->willReturn($response);
    
        $this->cacheManager->set($url, Argument::any())
            ->shouldBeCalled();

        $this->scrape($url);
    }

    public function it_should_return_scraped_data_from_cache_if_present()
    {
        $url = 'https://www.minds.com/';

        $this->config->get('metascraper')
            ->shouldBeCalledTimes(3)
            ->willReturn([
                'base_url' => 'localhost:3333/',
                'bypass_cache' => false
            ]);

        $cachedData = ['cachedData' => 'cachedData'];

        $this->cacheManager->getExported($url)
            ->shouldBeCalled()
            ->willReturn($cachedData);

        $this->httpClient->request(
            'GET',
            'localhost:3333/scrape',
            Argument::any()
        )
            ->shouldNotBeCalled();

        $this->scrape($url)->shouldBe($cachedData);
    }

    public function it_should_allow_cache_bypass_in_config(
        ResponseInterface $response,
        StreamInterface $streamInterface
    ) {
        $url = 'https://www.minds.com/';

        $this->config->get('metascraper')
            ->shouldBeCalledTimes(3)
            ->willReturn([
                'base_url' => 'localhost:3333/',
                'bypass_cache' => true
            ]);

        $this->cacheManager->getExported($url)
            ->shouldNotBeCalled();

        $streamInterface->getContents()
            ->shouldBeCalled()
            ->willReturn('{"status":200,"data": {
                "url": "url",
                "description": "description",
                "title": "title",
                "author": "author",
                "image": "image",
                "logo": "logo",
                "iframe": "iframe"
            }}');

        $response->getBody()
            ->shouldBeCalled()
            ->willReturn($streamInterface);

        $this->httpClient->request(
            'GET',
            'localhost:3333/scrape',
            Argument::any()
        )
            ->shouldBeCalled()
            ->willReturn($response);
    
        $this->cacheManager->set($url, Argument::any())
            ->shouldBeCalled();

        $this->scrape($url);
    }

    public function it_should_check_if_service_is_healthy(
        ResponseInterface $response,
        StreamInterface $streamInterface
    ) {
        $this->config->get('metascraper')
        ->shouldBeCalledTimes(2)
        ->willReturn([
            'base_url' => 'localhost:3333/',
        ]);

        $streamInterface->getContents()
            ->shouldBeCalled()
            ->willReturn('{"status":200,"readyState": 1}');

        $response->getBody()
            ->shouldBeCalled()
            ->willReturn($streamInterface);

        $this->httpClient->request(
            'GET',
            'localhost:3333/',
            Argument::any()
        )
            ->shouldBeCalled()
            ->willReturn($response);

        $this->isHealthy()->shouldBe(true);
    }
}
