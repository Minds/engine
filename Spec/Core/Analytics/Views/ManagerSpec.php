<?php

namespace Spec\Minds\Core\Analytics\Views;

use Minds\Core\Analytics\Views\Delegates\ViewsDelegate;
use Minds\Core\Analytics\Views\Manager;
use Minds\Core\Analytics\Views\Repository;
use Minds\Core\Analytics\Views\View;
use Minds\Core\Feeds\Seen\Manager as SeenManager;
use Minds\Entities\EntityInterface;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;

class ManagerSpec extends ObjectBehavior
{
    /** @var Repository */
    protected $repository;

    /** @var SeenManager */
    protected $seenManager;

    private Collaborator $viewsDelegate;

    public function let(
        Repository $repository,
        SeenManager $seenManager,
        ViewsDelegate $viewsDelegate
    ) {
        $this->repository = $repository;
        $this->seenManager = $seenManager;
        $this->viewsDelegate = $viewsDelegate;

        $this->beConstructedWith(
            $this->repository,
            null,
            $seenManager,
            $this->viewsDelegate
        );
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    public function it_should_record(
        View $view,
        EntityInterface $entity
    ) {
        $view->setYear(null)
            ->shouldBeCalled()
            ->willReturn($view);

        $view->setMonth(null)
            ->shouldBeCalled()
            ->willReturn($view);

        $view->setDay(null)
            ->shouldBeCalled()
            ->willReturn($view);

        $view->setUuid(Argument::any())
            ->shouldBeCalled()
            ->willReturn($view);

        $view->setTimestamp(Argument::type('int'))
            ->shouldBeCalled()
            ->willReturn($view);

        $view->getEntityUrn()
            ->shouldBeCalled()
            ->willReturn("urn:activity:fakeguid");

        $this->viewsDelegate->onRecordView($view, $entity)
            ->shouldBeCalledOnce();

        $this->seenManager->seeEntities(["fakeguid"])
            ->shouldBeCalled();

        $this->repository->add($view)
            ->shouldBeCalled()
            ->willReturn(true);

        $this
            ->record($view, $entity)
            ->shouldReturn(true);
    }
}
