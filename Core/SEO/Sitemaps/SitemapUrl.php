<?php
/**
 * Sitemap url
 */
namespace Minds\Core\SEO\Sitemaps;

use DateTime;
use Minds\Traits\MagicAttributes;

/**
 * @method SitemapUrl setLoc(string $loc)
 * @method string getLoc()
 * @method SitemapUrl setLastModified(DateTime $lastModified)
 * @method DateTime getLastModified()
 * @method SitemapUrl setChangeFreq(string $changeFreq)
 * @method string getChangeFreq()
 * @method SitemapUrl setPriority(int $priority)
 * @method int getPriority()
 * @method SitemapUrl setAlternates(array $alternates)
 * @method array getAlternates()
 */
class SitemapUrl
{
    use MagicAttributes;

    /** @var string */
    private $loc;

    /** @var DateTime */
    private $lastModified;

    /** @var string */
    private $changeFreq;

    /** @var int */
    private $priority;

    /** @var array */
    private $alternates = [];
}
