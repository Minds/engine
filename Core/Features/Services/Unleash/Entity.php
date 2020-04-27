<?php
/**
 * Entity
 *
 * @author edgebal
 */

namespace Minds\Core\Features\Services\Unleash;

use Minds\Traits\MagicAttributes;

/**
 * Entity for cached feature flags
 * @package Minds\Core\Features\Services\Unleash
 * @method string getEnvironment()
 * @method Entity setEnvironment(string $environment)
 * @method string getFeatureName()
 * @method Entity setFeatureName(string $featureName)
 * @method array getData()
 * @method Entity setData(array $data)
 * @method int getCreatedAt()
 * @method Entity setCreatedAt(int $createdAt)
 * @method int getStaleAt()
 * @method Entity setStaleAt(int $staleAt)
 */
class Entity
{
    use MagicAttributes;

    /** @var string */
    protected $environment;

    /** @var string */
    protected $featureName;

    /** @var array */
    protected $data;

    /** @var int */
    protected $createdAt;

    /** @var int */
    protected $staleAt;
}
