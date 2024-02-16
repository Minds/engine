<?php

namespace Spec\Minds\Core\Payments\SiteMemberships\PaywalledEntities\Services;

use Minds\Core\Payments\SiteMemberships\PaywalledEntities\Services\PaywalledEntityGatekeeperService;
use Minds\Core\Payments\SiteMemberships\PaywalledEntities\Services\PaywalledEntityService;
use Minds\Core\Payments\SiteMemberships\Services\SiteMembershipSubscriptionsService;
use Minds\Core\Payments\SiteMemberships\Types\SiteMembershipSubscription;
use Minds\Entities\Activity;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Psr\SimpleCache\CacheInterface;

class PaywalledEntityGatekeeperServiceSpec extends ObjectBehavior
{
    private Collaborator $paywalledEntityServiceMock;
    private Collaborator $siteMembershipSubscriptionsServiceMock;
    private Collaborator $cacheMock;

    public function let(
        PaywalledEntityService $paywalledEntityServiceMock,
        SiteMembershipSubscriptionsService $siteMembershipSubscriptionsServiceMock,
        CacheInterface $cacheMock,
    ) {
        $this->beConstructedWith($paywalledEntityServiceMock, $siteMembershipSubscriptionsServiceMock, $cacheMock);
        $this->paywalledEntityServiceMock = $paywalledEntityServiceMock;
        $this->siteMembershipSubscriptionsServiceMock = $siteMembershipSubscriptionsServiceMock;
        $this->cacheMock = $cacheMock;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(PaywalledEntityGatekeeperService::class);
    }

    public function it_should_always_grant_access_to_post_owner(Activity $activityMock, User $userMock)
    {
        $activityMock->getOwnerGuid()->willReturn('123');
        $userMock->getGuid()->willReturn('123');

        $this->canAccess($activityMock, $userMock)
            ->shouldBe(true);
    }

    public function it_should_always_grant_access_to_admin(Activity $activityMock, User $userMock)
    {
        $activityMock->getOwnerGuid()->willReturn('123');
        $userMock->getGuid()->willReturn('456');
        $userMock->isAdmin()->willReturn(true);

        $this->canAccess($activityMock, $userMock)
            ->shouldBe(true);
    }

    public function it_should_not_give_access_if_user_has_memberships()
    {
        $user = new User();

        $this->siteMembershipSubscriptionsServiceMock->getSiteMembershipSubscriptions($user)
            ->willReturn([]);

        $this->canAccess(new Activity(), $user)
            ->shouldBe(false);
    }

    public function it_should_not_give_access_if_the_user_has_no_membership(Activity $activityMock, User $userMock)
    {
        $activityMock->getGuid()->willReturn('1');
        $activityMock->getOwnerGuid()->willReturn('123');
        $userMock->getGuid()->willReturn('456');
        $userMock->isAdmin()->willReturn(false);

        $this->siteMembershipSubscriptionsServiceMock->getSiteMembershipSubscriptions($userMock)
            ->willReturn([
                new SiteMembershipSubscription(
                    membershipSubscriptionId: 1,
                    membershipGuid: 2,
                    stripeSubscriptionId: 'stripe-id',
                    autoRenew: true,
                    validFromTimestamp: time(),
                )
            ]);

        $this->paywalledEntityServiceMock->getMembershipGuidsForActivity($activityMock)
            ->willReturn([1]);

        $this->canAccess($activityMock, $userMock)
            ->shouldBe(false);
    }

    public function it_should_give_access_if_the_user_has_membership(Activity $activityMock, User $userMock)
    {
        $activityMock->getGuid()->willReturn('1');
        $activityMock->getOwnerGuid()->willReturn('123');
        $userMock->getGuid()->willReturn('456');
        $userMock->isAdmin()->willReturn(false);

        $this->siteMembershipSubscriptionsServiceMock->getSiteMembershipSubscriptions($userMock)
            ->willReturn([
                new SiteMembershipSubscription(
                    membershipSubscriptionId: 1,
                    membershipGuid: 2,
                    stripeSubscriptionId: 'stripe-id',
                    autoRenew: true,
                    validFromTimestamp: time(),
                    validToTimestamp: time() + 3600,
                )
            ]);

        $this->paywalledEntityServiceMock->getMembershipGuidsForActivity($activityMock)
            ->willReturn([2]);

        $this->canAccess($activityMock, $userMock)
            ->shouldBe(true);
    }

    public function it_should_give_access_if_the_user_has_membership_and_the_entity_is_for_all_memberships(Activity $activityMock, User $userMock)
    {
        $activityMock->getGuid()->willReturn('1');
        $activityMock->getOwnerGuid()->willReturn('123');
        $userMock->getGuid()->willReturn('456');
        $userMock->isAdmin()->willReturn(false);

        $this->siteMembershipSubscriptionsServiceMock->getSiteMembershipSubscriptions($userMock)
            ->willReturn([
                new SiteMembershipSubscription(
                    membershipSubscriptionId: 1,
                    membershipGuid: 2,
                    stripeSubscriptionId: 'stripe-id',
                    autoRenew: true,
                    validFromTimestamp: time(),
                    validToTimestamp: time() + 3600,
                )
            ]);

        $this->paywalledEntityServiceMock->getMembershipGuidsForActivity($activityMock)
            ->willReturn([-1]);

        $this->canAccess($activityMock, $userMock)
            ->shouldBe(true);
    }

    public function it_should_not_give_access_if_the_user_has_an_expired_membership(Activity $activityMock, User $userMock)
    {
        $activityMock->getGuid()->willReturn('1');
        $activityMock->getOwnerGuid()->willReturn('123');
        $userMock->getGuid()->willReturn('456');
        $userMock->isAdmin()->willReturn(false);

        $this->siteMembershipSubscriptionsServiceMock->getSiteMembershipSubscriptions($userMock)
            ->willReturn([
                new SiteMembershipSubscription(
                    membershipSubscriptionId: 1,
                    membershipGuid: 2,
                    stripeSubscriptionId: 'stripe-id',
                    autoRenew: true,
                    validFromTimestamp: time(),
                    validToTimestamp: time() - 3600, // expired an hour ago
                )
            ]);

        $this->paywalledEntityServiceMock->getMembershipGuidsForActivity(1)
            ->willReturn([-1]);

        $this->canAccess($activityMock, $userMock)
            ->shouldBe(false);
    }
}
