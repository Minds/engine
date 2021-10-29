<?php

namespace Spec\Minds\Core\Reports\Jury;

use Minds\Core\Reports\Jury\Manager;
use Minds\Core\Reports\Verdict\Manager as VerdictManager;
use Minds\Core\Reports\Jury\Repository;
use Minds\Core\Reports\Jury\Decision;
use Minds\Core\Reports\Summons\Manager as SummonsManager;
use Minds\Core\Reports\Report;
use Minds\Core\Entities\Resolver as EntitiesResolver;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Minds\Core\Security\ACL;
use Minds\Core\Analytics\Metrics\Event as AnalyticsEvent;
use Minds\Entities\Activity;
use Minds\Core\Log\Logger;

class ManagerSpec extends ObjectBehavior
{
    private $repository;
    private $entitiesResolver;
    private $verdictManager;
    private $summonsManager;
    private $acl;
    private $analyticsEvent;
    private $logger;

    public function let(
        Repository $repository,
        EntitiesResolver $entitiesResolver,
        VerdictManager $verdictManager,
        SummonsManager $summonsManager,
        ACL $acl,
        AnalyticsEvent $analyticsEvent,
        Logger $logger,
    ) {
        $this->beConstructedWith($repository, $entitiesResolver, $verdictManager, $summonsManager, $acl, $analyticsEvent, $logger);
        $this->repository = $repository;
        $this->entitiesResolver = $entitiesResolver;
        $this->verdictManager = $verdictManager;
        $this->summonsManager = $summonsManager;
        $this->acl = $acl;
        $this->analyticsEvent = $analyticsEvent;
        $this->logger = $logger;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    public function it_should_return_an_undmoderated_list_to_jury_on()
    {
        $this->repository->getList(Argument::type('array'))
            ->shouldBeCalled()
            ->willReturn([
                (new Report)
                    ->setEntityUrn('urn:activity:123'),
                (new Report)
                    ->setEntityUrn('urn:activity:456'),
            ]);
        
        $this->entitiesResolver->single(Argument::that(function ($urn) {
            return $urn->getNss() == 123;
        }))
            ->shouldBeCalled();
        $this->entitiesResolver->single(Argument::that(function ($urn) {
            return $urn->getNss() == 456;
        }))
            ->shouldBeCalled();
        
        $response = $this->getUnmoderatedList([ 'hydrate' => true ]);
        $response->shouldHaveCount(2);
    }

    public function it_should_cast_a_jury_decision(Decision $decision)
    {
        $report = new Report();
        $reportEntity = new Activity();
        $user = (new User());

        $user->guid = 123;
        $user->phone_number_hash = 'hash';
        
        $reportEntity->guid = 321;

        $report->setState('appealed');
        $report->setEntity($reportEntity);

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

        $this->setUser($user);

        $this->analyticsEvent
            ->setUserGuid(123)
            ->shouldBeCalled()
            ->willReturn($this->analyticsEvent);
        
        $this->analyticsEvent
            ->setType('action')
            ->shouldBeCalled()
            ->willReturn($this->analyticsEvent);
                 
        $this->analyticsEvent
            ->setAction('jury_vote_overturned')
            ->shouldBeCalled()
            ->willReturn($this->analyticsEvent);

        $this->analyticsEvent
            ->setEntityGuid(321)
            ->shouldBeCalled()
            ->willReturn($this->analyticsEvent);
        
        $this->analyticsEvent
            ->setUserPhoneNumberHash('hash')
            ->shouldBeCalled()
            ->willReturn($this->analyticsEvent);
        
        $this->analyticsEvent
            ->push()
            ->shouldBeCalled();

        $this->cast($decision)
            ->shouldBe(true);
    }
}
