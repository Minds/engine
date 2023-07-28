<?php

namespace Spec\Minds\Core\FeedNotices\Notices;

use Minds\Core\FeedNotices\Notices\BoostPartnersNotice;
use Minds\Core\Experiments\Manager as ExperimentsManager;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;

class BoostPartnersNoticeSpec extends ObjectBehavior
{
    /** @var ExperimentsManager */
    protected $experimentsManager;

    public function let(
        ExperimentsManager $experimentsManager
    ) {
        $this->experimentsManager = $experimentsManager;

        $this->beConstructedWith(
            $experimentsManager
        );
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(BoostPartnersNotice::class);
    }

    public function it_should_get_location()
    {
        $this->getLocation()->shouldBe('inline');
    }

    public function it_should_get_key()
    {
        $this->getKey()->shouldBe('boost-partners');
    }

    public function it_should_get_whether_notice_is_dismissible()
    {
        $this->isDismissible()->shouldBe(true);
    }

    public function it_should_determine_if_notice_should_show(
        User $user,
    ) {
        $this->experimentsManager->setUser($user)
            ->shouldBeCalled()
            ->willReturn($this->experimentsManager);

        $this->experimentsManager->isOn('epic-303-boost-partners')
            ->shouldBeCalled()
            ->willReturn(true);

        $user->getPhoneNumberHash()
            ->shouldBeCalled()
            ->willReturn('abc');

        $this->callOnWrappedObject('shouldShow', [$user])
            ->shouldBe(true);
    }

    public function it_should_determine_if_notice_should_NOT_show_because_experiment_is_off(
        User $user,
    ) {
        $this->experimentsManager->setUser($user)
            ->shouldBeCalled()
            ->willReturn($this->experimentsManager);

        $this->experimentsManager->isOn('epic-303-boost-partners')
            ->shouldBeCalled()
            ->willReturn(false);

        $this->callOnWrappedObject('shouldShow', [$user])
            ->shouldBe(false);
    }

    public function it_should_determine_if_notice_should_NOT_show_because_user_has_not_verified_phone_number(
        User $user,
    ) {
        $this->experimentsManager->setUser($user)
            ->shouldBeCalled()
            ->willReturn($this->experimentsManager);

        $this->experimentsManager->isOn('epic-303-boost-partners')
            ->shouldBeCalled()
            ->willReturn(true);

        $user->getPhoneNumberHash()
            ->shouldBeCalled()
            ->willReturn(null);

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
        $this->experimentsManager->setUser($user)
            ->shouldBeCalled()
            ->willReturn($this->experimentsManager);

        $this->experimentsManager->isOn('epic-303-boost-partners')
            ->shouldBeCalled()
            ->willReturn(true);

        $user->getPhoneNumberHash()
            ->shouldBeCalled()
            ->willReturn('abc');

        $this->setUser($user);

        $this->export()->shouldBe([
            'key' => 'boost-partners',
            'location' => 'inline',
            'should_show' => true,
            'is_dismissible' => true
        ]);
    }
}
