<?php
/**
 */
namespace Minds\Core\SEO\Sitemaps\Resolvers;

use Minds\Core\Di\Di;
use Minds\Core\Log\Logger;
use Minds\Core\Discovery\Manager;
use Minds\Core\SEO\Sitemaps\SitemapUrl;

class DiscoveryResolver
{
    /** @var Manager */
    protected $discoveryManager;

    /** @var Logger */
    protected $logger;

    public function __construct($discoveryManager = null, $logger = null)
    {
        $this->discoveryManager = $discoveryManager ?? Di::_()->get('Discovery\Manager');
        $this->logger = $logger ?? Di::_()->get('Logger');
    }

    public function getUrls(): iterable
    {
        $tags = $this->discoveryManager->getTags([ 'limit' => 500, 'from' => strtotime('3 days ago') ]);
        $i = 0;
        foreach ($tags as $type => $list) {
            foreach ($list as $tag) {
                ++$i;
                $q = urlencode('#' . $tag['value']);
                $url = "/discovery/search?q=$q&f=top&t=all";
                $sitemapUrl = new SitemapUrl();
                $sitemapUrl->setLoc($url);
                $sitemapUrl->setPriority(1);
                $sitemapUrl->setChangeFreq('always');
                $sitemapUrl->setLastModified(new \DateTime());
                $this->logger->info("$i: {$sitemapUrl->getLoc()}");
                yield $sitemapUrl;
            }
        }
    }
}
