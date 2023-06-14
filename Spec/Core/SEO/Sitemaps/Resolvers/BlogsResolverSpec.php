<?php

namespace Spec\Minds\Core\SEO\Sitemaps\Resolvers;

use Minds\Core\Blogs\Blog;
use Minds\Core\Data\ElasticSearch\Scroll;
use Minds\Core\EntitiesBuilder;
use Minds\Core\SEO\Sitemaps\Resolvers\BlogsResolver;
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

    public function it_should_return_iterable_of_users(): void
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
            ->willReturn((new Blog)->setTimeCreated(1));

        $this->entitiesBuilder->single('456')
            ->shouldBeCalled()
            ->willReturn((new Blog)->setTimeCreated(2));

        $this->getUrls()->shouldHaveCount(2);
    }
}
