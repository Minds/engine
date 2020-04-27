<?php
namespace Minds\Core\Search\MetricsSync\Resolvers;

use Minds\Core\Di\Di;
use Minds\Core\Counters;

abstract class AbstractVotesMetricResolver extends AbstractMetricResolver
{
    /** @var Counters */
    protected $counters;

    /** @var string */
    protected $counterMetricId;

    public function __construct($counters = null, $db = null)
    {
        parent::__construct($db);
        $this->counters = $counters ?? Di::_()->get('Entities\Counters');
    }

    /**
     * Set the type
     * @param string $type
     * @return MetricResolverInterface
     */
    public function setType(string $type): MetricResolverInterface
    {
        if ($type === 'user') {
            throw new \Exception('Can not perform votes sync on a user');
        }
        return parent::setType($type);
    }

    /**
     * Return the total count
     * @param string $guid
     * @return int
     */
    protected function getTotalCount(string $guid): int
    {
        try {
            return $this->counters->get($guid, $this->counterMetricId);
        } catch (Exception $e) {
            return 0;
        }
    }
}
