<?php
declare(strict_types=1);

namespace Spec\Minds\Core\Demonetization\Strategies;

use Minds\Core\Demonetization\Strategies\DemonetizePostStrategy;
use Minds\Core\Demonetization\Strategies\Interfaces\DemonetizableEntityInterface;
use Minds\Core\Demonetization\Strategies\Interfaces\DemonetizationStrategyInterface;
use Minds\Core\Entities\Actions\Save as SaveAction;
use Minds\Core\Wire\Paywall\PaywallEntityInterface;
use Minds\Entities\User;
use Minds\Exceptions\ServerErrorException;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;

class DemonetizePostStrategySpec extends ObjectBehavior
{
    protected Collaborator $saveAction;

    public function let(SaveAction $saveAction)
    {
        $this->beConstructedWith($saveAction);
        $this->saveAction = $saveAction;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(DemonetizePostStrategy::class);
    }

    public function it_should_execute(
        PaywallEntityInterface $entity
    ) {
        $entity->setWireThreshold([])
            ->shouldBeCalled();
        
        $entity->setPaywall(false)
            ->shouldBeCalled();

        $this->saveAction->setEntity($entity)
            ->shouldBeCalled()
            ->willReturn($this->saveAction);

        $this->saveAction->save(true)
            ->shouldBeCalled();

        $this->execute($entity);
    }

    public function it_should_not_execute_if_entity_is_not_paywall_entity_interface_instance(
        User $entity
    ) {
        $this->shouldThrow(ServerErrorException::class)->during('execute', [$entity]);
    }
}
