<?php

namespace Spec\Minds\Core\Features;

use Minds\Core\Features\Exceptions\FeatureNotImplementedException;
use Minds\Core\Features\Manager;
use Minds\Core\Features\Services\ServiceInterface;
use Minds\Core\Sessions\ActiveSession;
use Minds\Entities\User;
use PhpSpec\Exception\Example\FailureException;
use PhpSpec\ObjectBehavior;

class ManagerSpec extends ObjectBehavior
{
    /** @var ServiceInterface */
    protected $service1;

    /** @var ServiceInterface */
    protected $service2;

    /** @var ActiveSession */
    protected $activeSession;

    public function let(
        ServiceInterface $service1,
        ServiceInterface $service2,
        ActiveSession $activeSession
    ) {
        $this->service1 = $service1;
        $this->service2 = $service2;
        $this->activeSession = $activeSession;

        $this->beConstructedWith(
            [ $service1, $service2 ],
            $activeSession,
            ['feature1', 'feature2', 'feature3']
        );
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    public function it_should_sync()
    {
        $this->service1->sync(30)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->service2->sync(30)
            ->shouldBeCalled()
            ->willReturn(false);

        $this
            ->sync(30)
            ->shouldBeAnIterator([
                get_class($this->service1->getWrappedObject()) => 'OK',
                get_class($this->service2->getWrappedObject()) => 'NOT SYNC\'D',
            ]);
    }

    public function it_should_throw_during_has_if_a_feature_does_not_exist(
        User $user
    ) {
        $this->activeSession->getUser()
            ->shouldBeCalled()
            ->willReturn($user);

        $this->service1->setUser($user)
            ->shouldBeCalled()
            ->willReturn($this->service1);

        $this->service1->fetch(['feature1', 'feature2', 'feature3'])
            ->shouldBeCalled()
            ->willReturn([
                'feature1' => true,
                'feature2' => false,
            ]);

        $this->service2->setUser($user)
            ->shouldBeCalled()
            ->willReturn($this->service2);

        $this->service2->fetch(['feature1', 'feature2', 'feature3'])
            ->shouldBeCalled()
            ->willReturn([
                'feature2' => true,
            ]);

        $this
            ->shouldThrow(FeatureNotImplementedException::class)
            ->duringHas('feature99-non-existant');
    }

    public function it_should_return_false_if_a_feature_exists_and_it_is_deactivated(
        User $user
    ) {
        $this->activeSession->getUser()
            ->shouldBeCalled()
            ->willReturn($user);

        $this->service1->setUser($user)
            ->shouldBeCalled()
            ->willReturn($this->service1);

        $this->service1->fetch(['feature1', 'feature2', 'feature3'])
            ->shouldBeCalled()
            ->willReturn([
                'feature1' => true,
                'feature2' => false,
            ]);

        $this->service2->setUser($user)
            ->shouldBeCalled()
            ->willReturn($this->service2);

        $this->service2->fetch(['feature1', 'feature2', 'feature3'])
            ->shouldBeCalled()
            ->willReturn([
                'feature2' => true,
                'feature3' => false,
            ]);

        $this
            ->has('feature3')
            ->shouldReturn(false);
    }

    public function it_should_return_true_if_a_feature_exists_and_it_is_activated(
        User $user
    ) {
        $this->activeSession->getUser()
            ->shouldBeCalled()
            ->willReturn($user);

        $this->service1->setUser($user)
            ->shouldBeCalled()
            ->willReturn($this->service1);

        $this->service1->fetch(['feature1', 'feature2', 'feature3'])
            ->shouldBeCalled()
            ->willReturn([
                'feature1' => true,
                'feature2' => false,
            ]);

        $this->service2->setUser($user)
            ->shouldBeCalled()
            ->willReturn($this->service2);

        $this->service2->fetch(['feature1', 'feature2', 'feature3'])
            ->shouldBeCalled()
            ->willReturn([
                'feature2' => true,
                'feature3' => false,
            ]);

        $this
            ->has('feature2')
            ->shouldReturn(true);
    }

    public function it_should_export_a_merge_of_all_features(
        User $user
    ) {
        $this->activeSession->getUser()
            ->shouldBeCalled()
            ->willReturn($user);

        $this->service1->setUser($user)
            ->shouldBeCalled()
            ->willReturn($this->service1);

        $this->service1->fetch(['feature1', 'feature2', 'feature3'])
            ->shouldBeCalled()
            ->willReturn([
                'feature1' => true,
                'feature2' => false,
            ]);

        $this->service2->setUser($user)
            ->shouldBeCalled()
            ->willReturn($this->service2);

        $this->service2->fetch(['feature1', 'feature2', 'feature3'])
            ->shouldBeCalled()
            ->willReturn([
                'feature2' => true,
                'feature3' => false,
            ]);

        $this
            ->export()
            ->shouldReturn([
                'feature1' => true,
                'feature2' => true,
                'feature3' => false,
            ]);
    }

    public function getMatchers(): array
    {
        $matchers = [];

        $matchers['beAnIterator'] = function ($subject, $elements = null) {
            if (!is_iterable($subject)) {
                throw new FailureException("Subject should be an iterable");
            }

            $resolvedSubject = iterator_to_array($subject);

            if ($elements !== null && $elements !== $resolvedSubject) {
                throw new FailureException("Subject elements don't match");
            }

            return true;
        };

        return $matchers;
    }
}
