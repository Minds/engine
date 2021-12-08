<?php

namespace Spec\Minds\Core\SEO\Sitemaps\Resolvers;

use Minds\Core\Data\ElasticSearch\Scroll;
use Minds\Core\SEO\Sitemaps\SitemapUrl;
use Minds\Core\SEO\Sitemaps\Resolvers\ActivityResolver;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ActivityResolverSpec extends ObjectBehavior
{
    protected $scroll;

    public function let(Scroll $scroll)
    {
        $this->beConstructedWith($scroll);
        $this->scroll = $scroll;
    }
    public function it_is_initializable()
    {
        $this->shouldHaveType(ActivityResolver::class);
    }

    // public function it_should_return_iterable_of_users()
    // {
    //     $this->scroll->request(Argument::any())
    //         ->shouldBeCalled()
    //         ->willReturn([
    //             [
    //                 '_source' => [
    //                     'guid' => '123',
    //                     'time_created' => 1
    //                 ]
    //             ],
    //             [
    //                 '_source' => [
    //                     'guid' => '456',
    //                     'time_created' => 2
    //                 ]
    //             ]
    //         ]);

    //     $this->getUrls()->shouldHaveCount(2);
    // }
}
