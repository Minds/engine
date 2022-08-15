<?php

namespace Spec\Minds\Core\Feeds\Activity\RichEmbed\Metascraper;

use Minds\Core\Feeds\Activity\RichEmbed\Metascraper\Controller;
use Minds\Core\Feeds\Activity\RichEmbed\Metascraper\Cache\Manager as CacheManager;
use Minds\Exceptions\UserErrorException;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\ServerRequest;

class ControllerSpec extends ObjectBehavior
{
    /** @var CacheManager */
    protected $cacheManager;

    public function let(
        CacheManager $cacheManager
    ) {
        $this->cacheManager = $cacheManager;
        $this->beConstructedWith($cacheManager);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Controller::class);
    }

    public function it_should_throw_exception_when_no_url_is_provided(ServerRequest $request)
    {
        $request->getQueryParams()
            ->shouldBeCalled()
            ->willReturn(null);
        $this->shouldThrow(UserErrorException::class)->duringPurge($request);
    }

    public function it_should_call_to_delete_a_url_from_cache(ServerRequest $request)
    {
        $url = 'https://www.minds.com/';

        $request->getQueryParams()
            ->shouldBeCalled()
            ->willReturn(['url' => $url]);
            
        $this->cacheManager->delete($url)
            ->shouldBeCalled();

        $jsonResponse = $this->purge($request);

        $json = $jsonResponse->getBody()->getContents();
        $json->shouldBe(json_encode([
            'status' => 200
        ]));
    }

    public function it_should_escape_a_url_before_calling_to_delete_it_from_cache(ServerRequest $request)
    {
        $url = 'https://www.minds.com/';
        $encodedUrl = urlencode($url);

        $request->getQueryParams()
            ->shouldBeCalled()
            ->willReturn(['url' => $encodedUrl]);
            
        $this->cacheManager->delete($url)
            ->shouldBeCalled();

        $jsonResponse = $this->purge($request);

        $json = $jsonResponse->getBody()->getContents();
        $json->shouldBe(json_encode([
            'status' => 200
        ]));
    }
}
