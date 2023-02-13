<?php
declare(strict_types=1);

namespace Spec\Minds\Core\Monetization\Demonetization;

use Minds\Core\Monetization\Demonetization\DemonetizationContext;
use Minds\Core\Monetization\Demonetization\Strategies\Interfaces\DemonetizableEntityInterface;
use Minds\Core\Monetization\Demonetization\Strategies\Interfaces\DemonetizationStrategyInterface;
use PhpSpec\ObjectBehavior;

class DemonetizationContextSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(DemonetizationContext::class);
    }

    public function it_should_execute_a_given_strategy(
        DemonetizationStrategyInterface $strategy,
        DemonetizableEntityInterface $entity
    ) {
        $strategy->execute($entity)
            ->shouldBeCalled();

        $this->beConstructedWith($strategy);
        
        $this->execute($entity);
    }
}
