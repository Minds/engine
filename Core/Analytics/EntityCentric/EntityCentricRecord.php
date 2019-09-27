<?php
/**
 * EntityCentricRecord
 * @author Mark
 */

namespace Minds\Core\Analytics\EntityCentric;

use Minds\Traits\MagicAttributes;

/**
 * Class EntityCentricRecord
 * @package Minds\Core\Analytics\Views
 * @method DownsampledView setResolution(int $year)
 * @method string getResolution()
 * @method DownsampledView setEntityUrn(string $entityUrn)
 * @method string getEntityUrn()
 * @method DownsampledView setOwnerGuid(string $ownerGuid)
 * @method string getOwnerGuid()
 * @method DownsampledView setTimestampMs(int $timestampMs)
 * @method int getTimestampMs()
 * @method DownsampledView setViews(int $views)
 * @method int getViews()
 */
class EntityCentricRecord
{
    use MagicAttributes;

    /** @var string */
    private $resolution;

    /** @var int */
    protected $timestampMs;

    /** @var string */
    protected $entityUrn;

    /** @var string */
    protected $ownerGuid;

    /** @var array */
    private $sums;

    /**
     * Increment views
     * @param string $metric
     * @param int $value
     * @return DownsampledView
     */
    public function incrementSum($metric, $value = 1): EntityCentricRecord
    {
        if (!isset($this->sums[$metric])) {
            $this->sums[$metric] = 0;
        }
        $this->sums[$metric] = $this->sums[$metric] + $value;
        return $this;
    }
}
