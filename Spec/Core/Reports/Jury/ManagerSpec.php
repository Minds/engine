<?php

namespace Spec\Minds\Core\Reports\Jury;

use Minds\Common\Repository\Response;
use Minds\Core\Reports\Jury\Manager;
use Minds\Core\Reports\Verdict\Manager as VerdictManager;
use Minds\Core\Reports\Jury\Repository;
use Minds\Core\Reports\Jury\Decision;
use Minds\Core\Reports\Summons\Manager as SummonsManager;
use Minds\Core\Reports\Report;
use Minds\Core\Entities\Resolver as EntitiesResolver;
use Minds\Entities\Activity;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ManagerSpec extends ObjectBehavior
{
    private $repository;
    private $entitiesResolver;
    private $verdictManager;
    private $summonsManager;

    public function let(
        Repository $repository,
        EntitiesResolver $entitiesResolver,
        VerdictManager $verdictManager,
        SummonsManager $summonsManager
    ) {
        $this->beConstructedWith($repository, $entitiesResolver, $verdictManager, $summonsManager);
        $this->repository = $repository;
        $this->entitiesResolver = $entitiesResolver;
        $this->verdictManager = $verdictManager;
        $this->summonsManager = $summonsManager;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    public function it_should_return_an_undmoderated_list_to_jury_on()
    {
        $this->repository->getList(Argument::type('array'))
            ->shouldBeCalled()
            ->willReturn(new Response([
                (new Report)
                    ->setEntityUrn('urn:activity:123'),
                (new Report)
                    ->setEntityUrn('urn:activity:456'),
            ], '', true));
        
        $this->entitiesResolver->single(Argument::that(function ($urn) {
            return $urn->getNss() == 123;
        }))
            ->shouldBeCalled()
            ->willReturn(new Activity());
        $this->entitiesResolver->single(Argument::that(function ($urn) {
            return $urn->getNss() == 456;
        }))
            ->shouldBeCalled()
            ->willReturn(new Activity());

        
        $response = $this->getUnmoderatedList([ 'hydrate' => true ]);
        $response->shouldHaveCount(2);
    }

    public function it_should_cleanup_oprhaned_reports_when_no_entity()
    {
        $this->repository->getList(Argument::type('array'))
        ->shouldBeCalled()
            ->willReturn(
                // 1st call
                new Response([
                    (new Report)
                        ->setEntityUrn('urn:activity:123'), // Does not exist
                    (new Report)
                        ->setEntityUrn('urn:activity:456'),
                ], '', true),
                // 2nd call
                new Response([
                    (new Report)
                        ->setEntityUrn('urn:activity:456'),
                ], '', true),
            );
    
        $this->entitiesResolver->single(Argument::that(function ($urn) {
            return $urn->getNss() == 123;
        }))
            ->shouldBeCalled()
            ->willReturn(null);
        $this->entitiesResolver->single(Argument::that(function ($urn) {
            return $urn->getNss() == 456;
        }))
            ->shouldBeCalled()
            ->willReturn(new Activity());

        $this->repository->delete(Argument::type('string'))
            ->shouldBeCalled();
        
        $response = $this->getUnmoderatedList([ 'hydrate' => true ]);
        $response->shouldHaveCount(1);
    }

    public function it_should_cast_a_jury_decision(Decision $decision)
    {
        $report = new Report();

        $decision->getReport()
            ->shouldBeCalled()
            ->willReturn($report);

        $decision->isAppeal()
            ->shouldBeCalled()
            ->willReturn(false);

        $this->repository->add($decision)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->verdictManager->decideFromReport(Argument::type(Report::class))
            ->shouldBeCalled();

        $this->cast($decision)
            ->shouldBe(true);
    }
}
