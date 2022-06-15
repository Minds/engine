<?php

namespace Spec\Minds\Core\FeedNotices\Notices;

use Minds\Common\Repository\Response;
use Minds\Core\FeedNotices\Notices\SetupChannelNotice;
use Minds\Core\FeedNotices\Notices\UpdateTagsNotice;
use Minds\Core\FeedNotices\Notices\VerifyUniquenessNotice;
use Minds\Core\Feeds\Elastic\Manager as FeedsManager;
use Minds\Core\Experiments\Manager as ExperimentsManager;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;

class VerifyUniquenessNoticeSpec extends ObjectBehavior
{
    /** @var FeedsManager */
    protected $feedsManager;

    /** @var UpdateTagsNotice */
    protected $updateTagsNotice;

    /** @var SetupChannelNotice */
    protected $setupChannelNotice;

    /** @var ExperimentsManager */
    protected $experimentsManager;

    public function let(
        FeedsManager $feedsManager,
        UpdateTagsNotice $updateTagsNotice,
        SetupChannelNotice $setupChannelNotice,
        ExperimentsManager $experimentsManager
    ) {
        $this->feedsManager = $feedsManager;
        $this->updateTagsNotice = $updateTagsNotice;
        $this->setupChannelNotice = $setupChannelNotice;
        $this->experimentsManager = $experimentsManager;

        $this->beConstructedWith(
            $feedsManager,
            $updateTagsNotice,
            $setupChannelNotice,
            $experimentsManager
        );
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(VerifyUniquenessNotice::class);
    }

    public function it_should_get_location()
    {
        $this->getLocation()->shouldBe('inline');
    }

    public function it_should_get_key()
    {
        $this->getKey()->shouldBe('verify-uniqueness');
    }

    public function it_should_determine_if_notice_should_show(
        User $user,
        Response $response
    ) {
        $user->isTrusted()
            ->shouldBeCalled()
            ->willReturn(true);

        $user->getAge()
            ->shouldBeCalled()
            ->willReturn(259201);
    
        $user->getGuid()
            ->shouldBeCalled()
            ->willReturn(123);

        $user->getPhoneNumberHash()
            ->shouldBeCalled()
            ->willReturn(null);

        $response->count()
            ->shouldBeCalled()
            ->willReturn(1);

        $this->experimentsManager->isOn('minds-3131-onboarding-notices')
            ->shouldBeCalled()
            ->willReturn(true);

        $this->feedsManager->getList([
            'container_guid' => 123,
            'limit' => 1,
            'algorithm' => 'latest',
            'period' => '1y',
            'type' => 'activity'
        ])
            ->shouldBeCalled()
            ->willReturn($response);

        $this->setupChannelNotice->shouldShow($user)
            ->shouldBeCalled()
            ->willReturn(false);

        $this->updateTagsNotice->shouldShow($user)
            ->shouldBeCalled()
            ->willReturn(false);

        $this->callOnWrappedObject('shouldShow', [$user])
            ->shouldBe(true);
    }

    public function it_should_determine_if_notice_should_NOT_show_when_experiment_is_off(
        User $user,
    ) {
        $this->experimentsManager->isOn('minds-3131-onboarding-notices')
            ->shouldBeCalled()
            ->willReturn(false);

        $this->callOnWrappedObject('shouldShow', [$user])
            ->shouldBe(false);
    }

    public function it_should_determine_if_notice_should_NOT_show_when_no_user_phone_hash(
        User $user,
    ) {
        $user->getPhoneNumberHash()
            ->shouldBeCalled()
            ->willReturn('123');

        $this->experimentsManager->isOn('minds-3131-onboarding-notices')
            ->shouldBeCalled()
            ->willReturn(true);

        $this->callOnWrappedObject('shouldShow', [$user])
            ->shouldBe(false);
    }

    public function it_should_determine_if_notice_should_NOT_show_because_user_not_trusted(
        User $user
    ) {
        $user->isTrusted()
            ->shouldBeCalled()
            ->willReturn(false);

        $user->getPhoneNumberHash()
            ->shouldBeCalled()
            ->willReturn(null);

        $this->experimentsManager->isOn('minds-3131-onboarding-notices')
            ->shouldBeCalled()
            ->willReturn(true);

        $this->callOnWrappedObject('shouldShow', [$user])
            ->shouldBe(false);
    }

    public function it_should_determine_if_notice_should_NOT_show_because_user_not_3_days_old(
        User $user
    ) {
        $user->isTrusted()
            ->shouldBeCalled()
            ->willReturn(true);

        $user->getAge()
            ->shouldBeCalled()
            ->willReturn(259199);

        $user->getPhoneNumberHash()
            ->shouldBeCalled()
            ->willReturn(null);

        $this->experimentsManager->isOn('minds-3131-onboarding-notices')
                ->shouldBeCalled()
                ->willReturn(true);

        $this->callOnWrappedObject('shouldShow', [$user])
            ->shouldBe(false);
    }

    public function it_should_determine_if_notice_should_NOT_show_because_user_has_not_made_posts(
        User $user,
        Response $response
    ) {
        $user->isTrusted()
            ->shouldBeCalled()
            ->willReturn(true);

        $user->getAge()
            ->shouldBeCalled()
            ->willReturn(259201);
    
        $user->getGuid()
            ->shouldBeCalled()
            ->willReturn(123);

        $user->getPhoneNumberHash()
            ->shouldBeCalled()
            ->willReturn(null);

        $response->count()
            ->shouldBeCalled()
            ->willReturn(0);

        $this->experimentsManager->isOn('minds-3131-onboarding-notices')
            ->shouldBeCalled()
            ->willReturn(true);

        $this->feedsManager->getList([
            'container_guid' => 123,
            'limit' => 1,
            'algorithm' => 'latest',
            'period' => '1y',
            'type' => 'activity'
        ])
            ->shouldBeCalled()
            ->willReturn($response);

        $this->callOnWrappedObject('shouldShow', [$user])
            ->shouldBe(false);
    }

    public function it_should_determine_if_notice_should_NOT_show_because_setup_channel_notice_should_show(
        User $user,
        Response $response
    ) {
        $user->isTrusted()
            ->shouldBeCalled()
            ->willReturn(true);

        $user->getAge()
            ->shouldBeCalled()
            ->willReturn(259201);
    
        $user->getGuid()
            ->shouldBeCalled()
            ->willReturn(123);

        $user->getPhoneNumberHash()
            ->shouldBeCalled()
            ->willReturn(null);

        $response->count()
            ->shouldBeCalled()
            ->willReturn(1);

        $this->experimentsManager->isOn('minds-3131-onboarding-notices')
            ->shouldBeCalled()
            ->willReturn(true);

        $this->feedsManager->getList([
            'container_guid' => 123,
            'limit' => 1,
            'algorithm' => 'latest',
            'period' => '1y',
            'type' => 'activity'
        ])
            ->shouldBeCalled()
            ->willReturn($response);

        $this->setupChannelNotice->shouldShow($user)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->callOnWrappedObject('shouldShow', [$user])
            ->shouldBe(false);
    }

    public function it_should_determine_if_notice_should_NOT_show_because_update_tags_notice_should_show(
        User $user,
        Response $response
    ) {
        $user->isTrusted()
            ->shouldBeCalled()
            ->willReturn(true);

        $user->getAge()
            ->shouldBeCalled()
            ->willReturn(259201);
    
        $user->getGuid()
            ->shouldBeCalled()
            ->willReturn(123);

        $user->getPhoneNumberHash()
            ->shouldBeCalled()
            ->willReturn(null);

        $response->count()
            ->shouldBeCalled()
            ->willReturn(1);

        $this->experimentsManager->isOn('minds-3131-onboarding-notices')
            ->shouldBeCalled()
            ->willReturn(true);

        $this->feedsManager->getList([
            'container_guid' => 123,
            'limit' => 1,
            'algorithm' => 'latest',
            'period' => '1y',
            'type' => 'activity'
        ])
            ->shouldBeCalled()
            ->willReturn($response);

        $this->setupChannelNotice->shouldShow($user)
            ->shouldBeCalled()
            ->willReturn(false);

        $this->updateTagsNotice->shouldShow($user)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->callOnWrappedObject('shouldShow', [$user])
            ->shouldBe(false);
    }

    public function it_should_return_instance_after_setting_user(User $user)
    {
        $this->setUser($user)
            ->shouldBe($this);
    }

    public function it_should_export(User $user)
    {
        $this->setUser($user);

        $this->experimentsManager->isOn('minds-3131-onboarding-notices')
            ->shouldBeCalled()
            ->willReturn(false);

        $this->export()->shouldBe([
            'key' => 'verify-uniqueness',
            'location' => 'inline',
            'should_show' => false
        ]);
    }
}
