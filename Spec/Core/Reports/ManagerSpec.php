<?php

namespace Spec\Minds\Core\Reports;

use Minds\Core\Reports\Manager;
use Minds\Core\Reports\Repository;
use Minds\Core\Reports\Report;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ManagerSpec extends ObjectBehavior
{
    private $repository;

    public function let(Repository $repo)
    {
        $this->beConstructedWith($repo);
        $this->repository = $repo;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    public function it_should_add_a_report_to_the_repository()
    {
        $this->repository->add(Argument::type(Report::class))
            ->shouldBeCalled()
            ->willReturn(true);

        $this->add(new Report)
            ->shouldBe(true);
    }

    public function it_should_return_false_if_repository_add_failed()
    {
        $this->repository->add(Argument::type(Report::class))
            ->shouldBeCalled()
            ->willReturn(false);

        $this->add(new Report)
            ->shouldBe(false);
    }
}
