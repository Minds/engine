<?php

namespace Spec\Minds\Core\Supermind\Models;

use Minds\Common\Access;
use Minds\Core\Supermind\Models\SupermindRequest;
use Minds\Entities\Activity;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;

class SupermindRequestSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(SupermindRequest::class);
    }

    public function it_should_export(Activity $entity, User $receiver)
    {
        $guid = '123';
        $activityGuid = '234';
        $replyActivityGuid = '345';
        $senderGuid = '456';
        $receiverGuid = '567';
        $status = 1;
        $paymentAmount = 1.00;
        $paymentMethod = 1;
        $paymentReference = 1;
        $createdTimestamp = '1666790366';
        $updatedTimestamp = '1666790390';
        $twitterRequired = 1;
        $replyType = 1;

        $entityExport = ['guid' => 123];
        $receiverExport = ['guid' => 234];

        $entity->export()
            ->shouldBeCalled()
            ->willReturn($entityExport);

        $receiver->export()
            ->shouldBeCalled()
            ->willReturn($receiverExport);

        $this->setGuid($guid)
            ->setActivityGuid($activityGuid)
            ->setReplyActivityGuid($replyActivityGuid)
            ->setSenderGuid($senderGuid)
            ->setReceiverGuid($receiverGuid)
            ->setStatus($status)
            ->setPaymentAmount($paymentAmount)
            ->setPaymentMethod($paymentMethod)
            ->setPaymentTxID($paymentReference)
            ->setCreatedAt($createdTimestamp)
            ->setUpdatedAt($updatedTimestamp)
            ->setTwitterRequired($twitterRequired)
            ->setReplyType($replyType)
            ->setEntity($entity)
            ->setReceiverEntity($receiver);

        $this->export()->shouldBe([
            'guid' => $guid,
            'activity_guid' => $activityGuid,
            'reply_activity_guid' => $replyActivityGuid,
            'sender_guid' => $senderGuid,
            'receiver_guid' => $receiverGuid,
            'status' => $status,
            'payment_amount' => $paymentAmount,
            'payment_method' => $paymentMethod,
            'payment_txid' => (string) $paymentReference,
            'created_timestamp' => (int) $createdTimestamp,
            'expiry_threshold' => 604800,
            'updated_timestamp' => (int) $updatedTimestamp,
            'twitter_required' => (bool) $twitterRequired,
            'reply_type' => $replyType,
            'entity' => $entityExport,
            'receiver_entity' => $receiverExport
        ]);
    }

    public function it_should_check_whether_supermind_request_is_expired()
    {
        $this->setCreatedAt(1)
            ->isExpired()
            ->shouldBe(true);
    }

    public function it_should_check_whether_supermind_request_is_NOT_expired()
    {
        $this->setCreatedAt(time() - (86400 * 6))
            ->isExpired()
            ->shouldBe(false);
    }

    public function it_should_construct_a_urn()
    {
        $this->setGuid('123')
            ->getUrn()
            ->shouldBe('urn:supermind:123');
    }

    public function it_should_get_fixed_type()
    {
        $this->getType()->shouldBe('supermind');
    }

    public function it_should_get_fixed_null_subtype()
    {
        $this->getSubtype()->shouldBe(null);
    }

    public function it_should_get_fixed_access_id()
    {
        $this->getAccessId()->shouldBe((string) Access::PUBLIC);
    }
}
