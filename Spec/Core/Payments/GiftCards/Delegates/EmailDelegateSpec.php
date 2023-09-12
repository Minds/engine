<?php
declare(strict_types=1);

namespace Spec\Minds\Core\Payments\GiftCards\Delegates;

use Minds\Common\SystemUser;
use Minds\Core\Email\V2\Campaigns\Recurring\GiftCard\Emailer;
use Minds\Core\Email\V2\Campaigns\Recurring\GiftCard\Issuer\Emailer as IssuerEmailer;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Payments\GiftCards\Delegates\EmailDelegate;
use Minds\Core\Payments\GiftCards\Models\GiftCard;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;

class EmailDelegateSpec extends ObjectBehavior
{
    private Collaborator $recipientEmailer;
    private Collaborator $issuerEmailer;
    private Collaborator $entitiesBuilder;

    public function let(
        Emailer $recipientEmailer,
        IssuerEmailer $issuerEmailer,
        EntitiesBuilder $entitiesBuilder
    ): void {
        $this->recipientEmailer = $recipientEmailer;
        $this->issuerEmailer = $issuerEmailer;
        $this->entitiesBuilder = $entitiesBuilder;

        $this->beConstructedWith(
            $this->recipientEmailer,
            $this->issuerEmailer,
            $this->entitiesBuilder,
        );
    }

    public function it_is_initializable(): void
    {
        $this->shouldHaveType(EmailDelegate::class);
    }

    public function it_should_send_email_on_issuer_email_requested(
        GiftCard $giftCard,
        User $issuer
    ): void {
        $paymentTxId = 'paymentTxId';
        $issuerEmail = 'noreply@minds.com';

        $issuer->getEmail()
            ->shouldBeCalled()
            ->willReturn($issuerEmail);

        $this->issuerEmailer->setGiftCard($giftCard)
            ->shouldBeCalled()
            ->willReturn($this->issuerEmailer);

        $this->issuerEmailer->setSender(new SystemUser())
            ->shouldBeCalled()
            ->willReturn($this->issuerEmailer);

        $this->issuerEmailer->setUser($issuer)
            ->shouldBeCalled()
            ->willReturn($this->issuerEmailer);

        $this->issuerEmailer->setTargetEmail($issuerEmail)
            ->shouldBeCalled()
            ->willReturn($this->issuerEmailer);

        $this->issuerEmailer->setTopic('gift-card-issuer-email')
            ->shouldBeCalled()
            ->willReturn($this->issuerEmailer);

        $this->issuerEmailer->setPaymentTxId($paymentTxId)
            ->shouldBeCalled()
            ->willReturn($this->issuerEmailer);

        $this->issuerEmailer->send()
           ->shouldBeCalled()
           ->willReturn($this->issuerEmailer);

        $this->onIssuerEmailRequested(
            giftCard: $giftCard,
            issuer: $issuer,
            paymentTxId: $paymentTxId
        );
    }
}
