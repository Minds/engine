<?php
/**
 * @author edgebal
 */
namespace Minds\Core;

use Minds\Common\StaticToInstance;
use Minds\Helpers\Counters as CountersHelper;
use ReflectionException;

/**
 * Class Counters
 * @package Minds\Core
 * @method increment($entity, $metric, $value = 1, $client = null)
 * @method decrement($entity, $metric, $value = 1, $client = null)
 * @method incrementBatch($entities, $metric, $value = 1, $client = null)
 * @method get($entity, $metric, $cache = true, $client = null)
 * @method clear($entity, $metric, $value = 0, $client = null)
 */
class Counters extends StaticToInstance
{
    /**
     * Counters constructor.
     * @throws ReflectionException
     */
    public function __construct()
    {
        parent::__construct(new CountersHelper());
    }
}
