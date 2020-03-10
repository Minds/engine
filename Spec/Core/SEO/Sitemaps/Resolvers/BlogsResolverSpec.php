<?php

namespace Spec\Minds\Core\SEO\Sitemaps\Resolvers;

use Minds\Core\Data\ElasticSearch\Scroll;
use Minds\Core\EntitiesBuilder;
use Minds\Core\SEO\Sitemaps\SitemapUrl;
use Minds\Core\SEO\Sitemaps\Resolvers\BlogsResolver;
use Minds\Core\Blogs\Blog;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class BlogsResolverSpec extends ObjectBehavior
{
    protected $scroll;
    protected $entitiesBuilder;

    public function let(Scroll $scroll, EntitiesBuilder $entitiesBuilder)
    {
        $this->beConstructedWith($scroll, $entitiesBuilder);
        $this->scroll = $scroll;
        $this->entitiesBuilder = $entitiesBuilder;
    }
    public function it_is_initializable()
    {
        $this->shouldHaveType(BlogsResolver::class);
    }

    public function it_should_return_iterable_of_users()
    {
        $this->scroll->request(Argument::any())
            ->shouldBeCalled()
            ->willReturn([
                [
                    '_source' => [
                        'guid' => '123',
                        'time_created' => 1
                    ]
                ],
                [
                    '_source' => [
                        'guid' => '456',
                        'time_created' => 2
                    ]
                ]
            ]);

        $this->entitiesBuilder->single('123')
            ->shouldBeCalled()
            ->willReturn(new Blog);
            
        $this->entitiesBuilder->single('456')
            ->shouldBeCalled()
            ->willReturn(new Blog);
        
        $this->getUrls()->shouldHaveCount(2);
    }
}
