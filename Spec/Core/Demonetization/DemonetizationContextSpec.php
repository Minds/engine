<?php
declare(strict_types=1);

namespace Spec\Minds\Core\Demonetization;

use Minds\Core\Demonetization\DemonetizationContext;
use Minds\Core\Demonetization\Strategies\Interfaces\DemonetizableEntityInterface;
use Minds\Core\Demonetization\Strategies\Interfaces\DemonetizationStrategyInterface;
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
