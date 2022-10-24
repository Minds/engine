<?php

namespace Spec\Minds\Core\Payments\Models;

use Minds\Core\Payments\Models\Payment;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;

class PaymentSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(Payment::class);
    }

    public function it_should_export(User $recipient)
    {
        $status = 'succeeded';
        $paymentId = 'pay_123';
        $currency = 'usd';
        $minorUnitAmount = 1000;
        $statementDescriptor = 'Minds: Payment';
        $receiptUrl = 'https://www.minds.com/';
        $createdTimestamp = 1666285605;
        $exportedRecipient = ['user_guid' => 123];

        $recipient->export()
            ->shouldBeCalled()
            ->willReturn($exportedRecipient);

        $this->setStatus($status)
            ->setPaymentId($paymentId)
            ->setCurrency($currency)
            ->setMinorUnitAmount($minorUnitAmount)
            ->setStatementDescriptor($statementDescriptor)
            ->setReceiptUrl($receiptUrl)
            ->setCreatedTimestamp($createdTimestamp)
            ->setRecipient($recipient);
        
        $this->export()->shouldReturn([
            'status' => $status,
            'payment_id' => $paymentId,
            'currency' => $currency,
            'minor_unit_amount' => $minorUnitAmount,
            'statement_descriptor' => $statementDescriptor,
            'receipt_url' => $receiptUrl,
            'created_timestamp' => $createdTimestamp,
            'recipient' => $exportedRecipient
        ]);
    }
}
