<?php

namespace Spec\Minds\Core\SEO\Sitemaps\Resolvers;

use Minds\Core\SEO\Sitemaps\SitemapUrl;
use Minds\Core\SEO\Sitemaps\Resolvers\MarketingResolver;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class MarketingResolverSpec extends ObjectBehavior
{
    protected $scroll;

    public function it_is_initializable()
    {
        $this->shouldHaveType(MarketingResolver::class);
    }

    public function it_should_return_iterable_of_marketing_pages()
    {
        $this->getUrls()->shouldHaveCount(10);
    }
}
