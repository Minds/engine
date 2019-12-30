<?php
/**
 * FeedCollectionParameters
 *
 * @author edgebal
 */

namespace Minds\Core\Feeds;

use Minds\Traits\MagicAttributes;

/**
 * Class FeedCollectionParameters
 * @package Minds\Core\Feeds
 * @method array getOpts()
 * @method FeedCollectionParameters setOpts(array $opts)
 * @method int getSoftLimit()
 * @method FeedCollectionParameters setSoftLimit(int $softLimit)
 */
class FeedCollectionParameters
{
    use MagicAttributes;

    /** @var array */
    protected $opts;

    /** @var int */
    protected $softLimit;
}
