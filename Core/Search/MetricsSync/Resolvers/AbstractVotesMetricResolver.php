<?php
namespace Minds\Core\Search\MetricsSync\Resolvers;

use Minds\Core\Di\Di;
use Minds\Core\Votes\Enums\VoteEnum;
use Minds\Core\Votes\MySqlRepository;

abstract class AbstractVotesMetricResolver extends AbstractMetricResolver
{
    /** @var MySqlRepository */
    protected $votesRepository;

    /** @var string */
    protected $counterMetricId;

    public function __construct(
        $votesRepository = null,
        $db = null,
    ) {
        parent::__construct($db);
        $this->votesRepository = $votesRepository ?? Di::_()->get(MySqlRepository::class);
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
            return $this->votesRepository->getCount((int) $guid, $this->counterMetricId === 'thumbs:up' ? VoteEnum::UP : VoteEnum::DOWN);
        } catch (\Exception $e) {
            return 0;
        }
    }
}
