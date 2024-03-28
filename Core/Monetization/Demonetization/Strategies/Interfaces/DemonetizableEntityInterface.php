<?php
declare(strict_types=1);

namespace Minds\Core\Monetization\Demonetization\Strategies\Interfaces;

/**
 * Entity that can be demonetized.
 */
interface DemonetizableEntityInterface
{
    public function getType();
}
