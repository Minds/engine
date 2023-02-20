<?php
declare(strict_types=1);

namespace Minds\Core\Monetization\Demonetization\Strategies\Interfaces;

interface DemonetizationStrategyInterface
{
    /**
     * Execute strategy.
     * @param DemonetizableEntityInterface $entity - entity to execute strategy upon.
     * @return boolean true on success.
     */
    public function execute(DemonetizableEntityInterface $entity): bool;
}
