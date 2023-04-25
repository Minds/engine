<?php

namespace Spec\Minds\Core\Reports\Verdict\Delegates;

use Minds\Core\Reports\Verdict\Delegates\EmailDelegate;
use Minds\Core\Di\Di;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Minds\Core\Config;
use Minds\Core\Reports\Report;
use Minds\Common\Urn;
use Minds\Core\Email\V2\Campaigns\Custom\Custom;
use Minds\Core\Email\V2\Campaigns\Recurring\BoostPolicyViolationEmailer\BoostPolicyViolationEmailer;
use Minds\Core\EntitiesBuilder;
use Minds\Entities\Activity;
use Minds\Entities\User;
use Minds\Exceptions\ServerErrorException;
use PhpSpec\Wrapper\Collaborator;

class EmailDelegateSpec extends ObjectBehavior
{
    protected $banReasons = [
        [
            'value' => 1,
            'label' => 'Illegal',
            'hasMore' => true,
            'reasons' => [
                ['value' => 1, 'label' => 'Terrorism'],
                ['value' => 2, 'label' => 'Paedophilia'],
                ['value' => 3, 'label' => 'Extortion'],
                ['value' => 4, 'label' => 'Fraud'],
                ['value' => 5, 'label' => 'Revenge Porn'],
                ['value' => 6, 'label' => 'Sex trafficking'],
            ],
        ],
        [
            'value' => 2,
            'label' => 'NSFW (not safe for work)',
            'hasMore' => true,
            'reasons' => [ // Explicit reasons
                ['value' => 1, 'label' => 'Nudity'],
                ['value' => 2, 'label' => 'Pornography'],
                ['value' => 3, 'label' => 'Profanity'],
                ['value' => 4, 'label' => 'Violance and Gore'],
                ['value' => 5, 'label' => 'Race, Religion, Gender'],
            ],
        ],
        [
            'value' => 3,
            'label' => 'Encourages or incites violence',
            'hasMore' => false,
        ],
        [
            'value' => 4,
            'label' => 'Harassment',
            'hasMore' => false,
        ],
        [
            'value' => 5,
            'label' => 'Personal and confidential information',
            'hasMore' => false,
        ],
        [
            'value' => 7,
            'label' => 'Impersonates',
            'hasMore' => false,
        ],
        [
            'value' => 8,
            'label' => 'Spam',
            'hasMore' => false,
        ],
        [
            'value' => 10,
            'label' => 'Intellectual property violation',
            'hasMore' => true,
        ],
        [
            'value' => 12,
            'label' => 'Incorrect use of hashtags',
            'hasMore' => false,
        ],
        [
            'value' => 13,
            'label' => 'Malware',
            'hasMore' => false,
        ],
        [
            'value' => 15,
            'label' => 'Trademark infringement',
            'hasMore' => false,
        ],
        [
            'value' => 16,
            'label' => 'Token manipulation',
            'hasMore' => false,
        ],
        [
            'value' => 11,
            'label' => 'Another reason',
            'hasMore' => true,
        ],
    ];

    protected Collaborator $entitiesBuilder;
    protected Collaborator $urn;
    protected Collaborator $config;
    protected Collaborator $boostPolicyViolationEmailer;

    public function let(
        Custom $customCampaign,
        EntitiesBuilder $entitiesBuilder,
        Urn $urn,
        Config $config,
        BoostPolicyViolationEmailer $boostPolicyViolationEmailer
    ) {
        $this->entitiesBuilder = $entitiesBuilder;
        $this->urn = $urn;
        $this->config = $config;
        $this->boostPolicyViolationEmailer = $boostPolicyViolationEmailer;

        $this->beConstructedWith(
            $customCampaign,
            $entitiesBuilder,
            $urn,
            $config,
            $boostPolicyViolationEmailer
        );
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(EmailDelegate::class);
    }

    public function it_should_discern_ban_reason_text()
    {
        Di::_()->get('Config')->set('report_reasons', $this->banReasons);

        $this->getBanReasons(1)
            ->shouldReturn("Illegal");

        $this->getBanReasons("1.3")
            ->shouldReturn("Illegal - Extortion");

        $this->getBanReasons("2.3")
            ->shouldReturn("NSFW (not safe for work) - Profanity");

        $this->getBanReasons("3")
            ->shouldReturn("Encourages or incites violence");

        $this->getBanReasons("8")
            ->shouldReturn("Spam");

        $this->getBanReasons("14.10")
            ->shouldReturn("Intellectual property violation");

        $this->getBanReasons("14.999")
            ->shouldReturn("Strikes");

        $this->getBanReasons("because reasons")
            ->shouldReturn("because reasons");
    }

    public function it_should_send_email_on_boost_policy_violation_for_an_activity(
        Report $report,
        Activity $entity,
        User $owner
    ): void {
        $entityUrn = 'urn:activity:123';
        $entityGuid = '123';
        $entityOwnerGuid = '234';

        $report->getEntityUrn()
            ->shouldBeCalled()
            ->willReturn($entityUrn);

        $this->urn->setUrn($entityUrn)
            ->shouldBeCalled()
            ->willReturn($this->urn);

        $this->urn->getNss()
            ->shouldBeCalled()
            ->willReturn($entityGuid);

        $this->entitiesBuilder->single($entityGuid)
            ->shouldBeCalled()
            ->willReturn($entity);

        $entity->getOwnerGuid()
            ->shouldBeCalled()
            ->willReturn($entityOwnerGuid);

        $this->entitiesBuilder->single($entityOwnerGuid)
            ->shouldBeCalled()
            ->willReturn($owner);

        $this->boostPolicyViolationEmailer->setEntity($entity)
            ->shouldBeCalled()
            ->willReturn($this->boostPolicyViolationEmailer);

        $this->boostPolicyViolationEmailer->setUser($owner)
            ->shouldBeCalled()
            ->willReturn($this->boostPolicyViolationEmailer);

        $this->boostPolicyViolationEmailer->queue()
            ->shouldBeCalled();

        $this->onBoostPolicyViolation($report);
    }

    public function it_should_throw_exception_on_send_email_on_boost_policy_violation_when_user_not_found(
        Report $report,
        Activity $entity,
    ): void {
        $entityUrn = 'urn:activity:123';
        $entityGuid = '123';
        $entityOwnerGuid = '234';

        $report->getEntityUrn()
            ->shouldBeCalled()
            ->willReturn($entityUrn);

        $this->urn->setUrn($entityUrn)
            ->shouldBeCalled()
            ->willReturn($this->urn);

        $this->urn->getNss()
            ->shouldBeCalled()
            ->willReturn($entityGuid);

        $this->entitiesBuilder->single($entityGuid)
            ->shouldBeCalled()
            ->willReturn($entity);

        $entity->getOwnerGuid()
            ->shouldBeCalled()
            ->willReturn($entityOwnerGuid);

        $this->entitiesBuilder->single($entityOwnerGuid)
            ->shouldBeCalled()
            ->willReturn(null);

        $this->boostPolicyViolationEmailer->setEntity($entity)
            ->shouldNotBeCalled();

        $this->shouldThrow(ServerErrorException::class)->during('onBoostPolicyViolation', [$report]);
    }

    public function it_should_throw_exception_on_send_email_on_boost_policy_violation_when_user_is_not_a_user(
        Report $report,
        Activity $entity,
        Activity $owner
    ): void {
        $entityUrn = 'urn:activity:123';
        $entityGuid = '123';
        $entityOwnerGuid = '234';

        $report->getEntityUrn()
            ->shouldBeCalled()
            ->willReturn($entityUrn);

        $this->urn->setUrn($entityUrn)
            ->shouldBeCalled()
            ->willReturn($this->urn);

        $this->urn->getNss()
            ->shouldBeCalled()
            ->willReturn($entityGuid);

        $this->entitiesBuilder->single($entityGuid)
            ->shouldBeCalled()
            ->willReturn($entity);

        $entity->getOwnerGuid()
            ->shouldBeCalled()
            ->willReturn($entityOwnerGuid);

        $this->entitiesBuilder->single($entityOwnerGuid)
            ->shouldBeCalled()
            ->willReturn($owner);

        $this->boostPolicyViolationEmailer->setEntity($entity)
            ->shouldNotBeCalled();

        $this->shouldThrow(ServerErrorException::class)->during('onBoostPolicyViolation', [$report]);
    }
}
