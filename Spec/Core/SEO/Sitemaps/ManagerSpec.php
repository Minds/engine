<?php

namespace Spec\Minds\Core\SEO\Sitemaps;

use Minds\Core\SEO\Sitemaps\Manager;
use Minds\Core\SEO\Sitemaps\Resolvers\UsersResolver;
use Minds\Core\SEO\Sitemaps\SitemapUrl;
use Aws\S3\S3Client;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ManagerSpec extends ObjectBehavior
{
    protected $s3;
    protected $usersResolver;


    public function let(UsersResolver $usersResolver, S3Client $s3Client)
    {
        $this->beConstructedWith(null, null, $s3Client);
        $this->s3 = $s3Client;
        $this->setResolvers([
            $usersResolver
        ]);
        $this->usersResolver = $usersResolver;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    public function it_should_build_sitemaps()
    {
        $this->usersResolver->getUrls()
            ->shouldBeCalled()
            ->willReturn([
                (new SitemapUrl)
                    ->setLoc('newsfeed/123')
            ]);

        $this->s3->putObject(Argument::any())
                ->shouldBeCalled();

        $this->build();
    }
}
