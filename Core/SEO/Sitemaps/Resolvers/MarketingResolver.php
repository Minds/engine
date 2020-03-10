<?php
/**
 */
namespace Minds\Core\SEO\Sitemaps\Resolvers;

use Minds\Core\SEO\Sitemaps\SitemapUrl;

class MarketingResolver
{
    public function getUrls(): array
    {
        $pages = [
            (new SitemapUrl())
                ->setLoc("/"),
            (new SitemapUrl())
                ->setLoc("/help"),
            (new SitemapUrl())
                ->setLoc("/mobile"),
            (new SitemapUrl())
                ->setLoc("/canary"),
            (new SitemapUrl())
                ->setLoc("/pro"),
            (new SitemapUrl())
                ->setLoc("/plus"),
            (new SitemapUrl())
                ->setLoc("/upgrades"),
            (new SitemapUrl())
                ->setLoc("/jobs"),
            (new SitemapUrl())
                ->setLoc("/minds/blog"),
            (new SitemapUrl())
                ->setLoc("/content-policy")
        ];
        return $pages;
    }
}
