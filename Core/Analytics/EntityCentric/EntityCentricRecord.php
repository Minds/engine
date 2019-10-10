<?php
/**
 * EntityCentricRecord
 * @author Mark
 */

namespace Minds\Core\Analytics\EntityCentric;

use Minds\Traits\MagicAttributes;

/**
 * Class EntityCentricRecord
 * @package Minds\Core\Analytics\EntityCentric
 * @method EntityCentricRecord setResolution(int $year)
 * @method string getResolution()
 * @method EntityCentricRecord setEntityUrn(string $entityUrn)
 * @method string getEntityUrn()
 * @method EntityCentricRecord setOwnerGuid(string $ownerGuid)
 * @method string getOwnerGuid()
 * @method EntityCentricRecord setTimestampMs(int $timestampMs)
 * @method int getTimestampMs()
 * @method EntityCentricRecord setTimestamp(int $timestamp)
 * @method int getTimestamp()
 * @method EntityCentricRecord setSums(array $sums)
 * @method int getSums()
 */
class EntityCentricRecord
{
    use MagicAttributes;

    /** @var string */
    private $resolution;

    /** @var int */
    protected $timestamp;

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
     * @return EntityCentricRecord
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
