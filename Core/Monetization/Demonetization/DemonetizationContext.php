<?php
declare(strict_types=1);

namespace Minds\Core\Monetization\Demonetization;

use Minds\Core\Monetization\Demonetization\Strategies\Interfaces\DemonetizableEntityInterface;
use Minds\Core\Monetization\Demonetization\Strategies\Interfaces\DemonetizationStrategyInterface;

/**
 * Context for demonetization - pass a strategy and execute with a valid entity type.
 */
class DemonetizationContext
{
    public function __construct(private ?DemonetizationStrategyInterface $strategy = null)
    {
        $this->strategy = $strategy;
    }

    /**
     * Get cloned instance with strategy set.
     * @param DemonetizationStrategyInterface $strategy - strategy to be executed.
     * @return self cloned instance.
     */
    public function withStrategy(DemonetizationStrategyInterface $strategy): self
    {
        $instance = clone $this;
        $instance->strategy = $strategy;
        return $instance;
    }

    /**
     * Execute given strategy.
     * @param DemonetizableEntityInterface $entity - entity to demonetize.
     * @return bool true if successful.
     */
    public function execute(DemonetizableEntityInterface $entity): bool
    {
        return $this->strategy->execute($entity);
    }
}
