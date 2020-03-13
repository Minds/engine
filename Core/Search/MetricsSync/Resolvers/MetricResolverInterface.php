<?php
namespace Minds\Core\Search\MetricsSync\Resolvers;

use Minds\Core\Search\MetricsSync;

interface MetricResolverInterface
{
    /**
     * Set the type
     * @param string $type
     * @return self
     */
    public function setType(string $type): self;

    /**
     * Set the subtype
     * @param string $subtype
     * @return self
     */
    public function setSubtype(string $subtype): self;
    
    /**
     * Set min timestamp
     * @param int $from
     * @return self
     */
    public function setFrom(int $from): self;

    /**
     * Set max timestamp
     * @param int $to
     * @return self
     */
    public function setTo(int $to): self;

    /**
     * Return metrics
     * @return MetricsSync[]
     */
    public function get(): iterable;
}
