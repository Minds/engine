<?php

namespace Spec\Minds\Core\Boost\Campaigns;

use Minds\Core\Boost\Campaigns\Campaign;
use Minds\Core\Boost\Campaigns\Payments\Payment;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;

class CampaignSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(Campaign::class);
    }

    public function it_should_get_guid()
    {
        $this
            ->setUrn('urn:campaign:1000')
            ->getGuid()
            ->shouldReturn('1000');

        $this
            ->setUrn(null)
            ->getGuid()
            ->shouldReturn('');

        $this
            ->setUrn(new \stdClass())
            ->getGuid()
            ->shouldReturn('');
    }

    public function it_should_set_owner(
        User $user
    ) {
        $user->get('guid')
            ->shouldBeCalled()
            ->willReturn('5000');

        $this
            ->setOwner($user)
            ->getOwnerGuid()
            ->shouldReturn('5000');
    }

    public function it_should_push_payment(
        Payment $payment1,
        Payment $payment2,
        Payment $payment3
    ) {
        $this
            ->setPayments([ $payment1 ])
            ->pushPayment($payment2)
            ->pushPayment($payment3)
            ->getPayments()
            ->shouldReturn([
                $payment1,
                $payment2,
                $payment3
            ]);
    }

    public function it_should_get_delivery_status()
    {
        $this
            ->getDeliveryStatus()
            ->shouldReturn(Campaign::STATUS_PENDING);

        $this
            ->setCreatedTimestamp(1564537097)
            ->getDeliveryStatus()
            ->shouldReturn(Campaign::STATUS_CREATED);

        $this
            ->setReviewedTimestamp(1564537097)
            ->getDeliveryStatus()
            ->shouldReturn(Campaign::STATUS_APPROVED);

        $this
            ->setReviewedTimestamp(null)
            ->setRevokedTimestamp(1564537097)
            ->getDeliveryStatus()
            ->shouldReturn(Campaign::STATUS_REVOKED);

        $this
            ->setRevokedTimestamp(null)
            ->setRejectedTimestamp(1564537097)
            ->getDeliveryStatus()
            ->shouldReturn(Campaign::STATUS_REJECTED);

        $this
            ->setRejectedTimestamp(null)
            ->setCompletedTimestamp(1564537097)
            ->getDeliveryStatus()
            ->shouldReturn(Campaign::STATUS_COMPLETED);
    }

    public function it_should_set_nsfw_along_with_rating()
    {
        $this
            ->setNsfw([]);

        $this
            ->getNsfw()
            ->shouldReturn([]);

        $this->getRating()
            ->shouldReturn(Campaign::RATING_SAFE);

        $this
            ->setNsfw([1, 3]);

        $this
            ->getNsfw()
            ->shouldReturn([1, 3]);

        $this->getRating()
            ->shouldReturn(Campaign::RATING_OPEN);
    }

    public function it_should_calculate_cpm()
    {
        $this
            ->setImpressions(0)
            ->setBudget(0)
            ->cpm()
            ->shouldReturn(0.0);

        $this
            ->setImpressions(5000)
            ->setBudget(5)
            ->cpm()
            ->shouldReturn(1.0);

        $this
            ->setImpressions(4000)
            ->setBudget(10)
            ->cpm()
            ->shouldReturn(2.5);
    }

    public function it_should_calculate_if_is_delivering()
    {
        $this
            ->setReviewedTimestamp(null)
            ->isDelivering()
            ->shouldReturn(false);

        $this
            ->setReviewedTimestamp(1564537097)
            ->isDelivering()
            ->shouldReturn(true);
    }

    public function it_should_return_should_be_started()
    {
        $this
            ->setCreatedTimestamp(1564537097)
            ->setStart(1564600000)
            ->setEnd(1564700000)
            ->callOnWrappedObject('shouldBeStarted', [ 1564500000 ])
            ->shouldReturn(false);

        $this
            ->setCreatedTimestamp(1564537097)
            ->setStart(1564600000)
            ->setEnd(1564700000)
            ->callOnWrappedObject('shouldBeStarted', [ 1564611111 ])
            ->shouldReturn(true);
    }

    public function it_should_not_return_should_be_completed()
    {
        $this
            ->setCreatedTimestamp(1564537097)
            ->setStart(1564600000)
            ->setEnd(1564700000)
            ->callOnWrappedObject('shouldBeCompleted', [1564611111])
            ->shouldReturn(false);
    }

    public function it_should_return_should_be_completed()
    {
        $this
            ->setCreatedTimestamp(1564537097)
            ->setReviewedTimestamp(1564537097)
            ->setStart(1564600000)
            ->setEnd(1564700000)
            ->setImpressions(1000)
            ->setImpressionsMet(1000)
            ->callOnWrappedObject('shouldBeCompleted', [1564711100])
            ->shouldReturn(true);
    }

    public function it_should_not_return_has_started()
    {
        $this->setCreatedTimestamp(1564537097);
        $this->hasStarted()->shouldReturn(false);
    }

    public function it_should_return_has_started()
    {
        $this->setCreatedTimestamp(1564537097)
            ->setCompletedTimestamp(1564537097)
            ->hasStarted()->shouldReturn(true);
    }

    public function it_should_not_return_has_finished()
    {
        $this->setCreatedTimestamp(1564537097)
            ->setReviewedTimestamp(1564537097)
            ->hasFinished()->shouldReturn(false);
    }

    public function it_should_return_has_finished()
    {
        $this->setCreatedTimestamp(1564537097)
            ->setCompletedTimestamp(1564537097)
            ->hasFinished()->shouldReturn(true);
    }
}
