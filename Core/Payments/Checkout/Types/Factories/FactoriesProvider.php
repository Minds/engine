<?php
declare(strict_types=1);

namespace Minds\Core\Payments\Checkout\Types\Factories;

use Minds\Core\Di\Di;
use Minds\Core\Di\ImmutableException;
use Minds\Core\Di\Provider;

class FactoriesProvider extends Provider
{
    /**
     * @return void
     * @throws ImmutableException
     */
    public function register(): void
    {
        $this->di->bind(
            PlanSummaryInputFactory::class,
            fn (Di $di): PlanSummaryInputFactory => new PlanSummaryInputFactory(),
        );
        $this->di->bind(
            AddOnSummaryInputFactory::class,
            fn (Di $di): AddOnSummaryInputFactory => new AddOnSummaryInputFactory(),
        );
        $this->di->bind(
            SummaryInputFactory::class,
            fn (Di $di): SummaryInputFactory => new SummaryInputFactory(),
        );
    }
}
