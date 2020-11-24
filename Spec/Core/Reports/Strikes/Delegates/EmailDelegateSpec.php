<?php

namespace Spec\Minds\Core\Reports\Strikes\Delegates;

use Minds\Core\Reports\Strikes\Delegates\EmailDelegate;
use Minds\Core\Reports\Strikes\Strike;
use Minds\Core\Reports\Report;
use Minds\Core\Email\V2\Campaigns\Custom\Custom;
use Minds\Core\EntitiesBuilder;
use Minds\Entities\Activity;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class EmailDelegateSpec extends ObjectBehavior
{
    /** @var Custom */
    private $campaign;

    /** @var EntitiesBuilder */
    private $entitiesBuilder;

    public function let(Custom $campaign, EntitiesBuilder $entitiesBuilder)
    {
        $this->beConstructedWith($campaign, $entitiesBuilder);
        $this->campaign = $campaign;
        $this->entitiesBuilder = $entitiesBuilder;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(EmailDelegate::class);
    }

    public function it_should_send_nsfw_strike()
    {
        $report = new Report();
        $report->setEntityUrn('urn:entity:123');
        $strike = new Strike();
        $strike->setReport($report);
        $strike->setReasonCode(2);

        $entity = new Activity();
        $entity->owner_guid = 456;
        $user = new User();

        $this->entitiesBuilder->single(123)
            ->willReturn($entity);
        $this->entitiesBuilder->single(456)
            ->willReturn($user);

        //

        $this->campaign->setUser($user)
            ->shouldBeCalled();

        $this->campaign->setTemplate('moderation-strike')
            ->shouldBeCalled();

        $this->campaign->setSubject('Strike received')
            ->shouldBeCalled();

        $this->campaign->setTitle('Strike received')
            ->shouldBeCalled();

        $this->campaign->setPreheader('You have received a strike')
            ->shouldBeCalled();

        $this->campaign->setVars([
            'type' => 'activity',
            'action' => 'marked as nsfw',
        ])
            ->shouldBeCalled();

        $this->campaign->send()
            ->shouldBeCalled();

        //

        $this->onStrike($strike);
    }

    public function it_should_send_minds_plis_nsfw_strike_variant()
    {
        $report = new Report();
        $report->setEntityUrn('urn:entity:123');
        $strike = new Strike();
        $strike->setReport($report);
        $strike->setReasonCode(2);

        $entity = new Activity();
        $entity->owner_guid = 456;
        $entity->paywall = 1;
        $entity->wire_threshold = [
            'support_tier' => [
                'urn' => 'plus_support_tier_urn',
            ],
        ];
        $user = new User();

        $this->entitiesBuilder->single(123)
            ->willReturn($entity);
        $this->entitiesBuilder->single(456)
            ->willReturn($user);

        //

        $this->campaign->setUser($user)
            ->shouldBeCalled();

        $this->campaign->setTemplate('moderation-strike-plus')
            ->shouldBeCalled();

        $this->campaign->setSubject('Strike received')
            ->shouldBeCalled();

        $this->campaign->setTitle('Strike received')
            ->shouldBeCalled();

        $this->campaign->setPreheader('You have received a strike')
            ->shouldBeCalled();

        $this->campaign->setVars([
            'type' => 'activity',
            'action' => 'marked as nsfw',
        ])
            ->shouldBeCalled();

        $this->campaign->send()
            ->shouldBeCalled();

        //

        $this->onStrike($strike);
    }
}
