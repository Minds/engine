<?php

namespace Spec\Minds\Core\Email;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

use PHPMailer;

use Minds\Core\Queue\Client as Queue;
use Minds\Core\Email\SpamFilter;
use Minds\Core\Email\Message;
use Minds\Core\Log\Logger;
use Minds\Core\Config\Config;
use Minds\Core\MultiTenant\Configs\Manager as TenantConfigsManager;
use Minds\Core\MultiTenant\Configs\Models\MultiTenantConfig;

class MailerSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType('Minds\Core\Email\Mailer');
    }

    public function it_should_not_send_a_blacklist_domain(PHPMailer $mailer, Queue $queue, SpamFilter $filter, Message $message)
    {
        $this->beConstructedWith($mailer, $queue, $filter);

        $message->to = [[ 'email' => 'you@yomail.com', 'name' => 'Spam' ]];
        $message->from = [ 'email' => 'me@minds.com', 'name' => 'Sender' ];
        $this->send($message);

        $this->getStats()->shouldHaveKeyWithValue('failed', 1);
    }


    public function it_should_update_replyTo_when_tenant_has_replyEmail(
        PHPMailer $mailer,
        Queue $queue,
        SpamFilter $filter,
        Logger $logger,
        Config $config,
        TenantConfigsManager $tenantConfigsManager,
        Message $message
    ) {
        $multiTenantConfig = new MultiTenantConfig(
            replyEmail: 'tenant@example.com'
        );

        $tenantConfigsManager->getConfigs()->willReturn($multiTenantConfig);
        $config->get('tenant_id')->willReturn('123');

        $this->beConstructedWith($mailer, $queue, $filter, $logger, $config, $tenantConfigsManager);

        $message->to = [['email' => 'recipient@example.com', 'name' => 'Recipient']];
        $message->from = ['email' => 'me@minds.com', 'name' => 'Sender'];

        $mailer->isSMTP()->shouldBeCalled();
        $mailer->ClearAllRecipients()->shouldBeCalled();
        $mailer->clearAttachments()->shouldBeCalled();
        $mailer->ClearReplyTos()->shouldBeCalled();
        $mailer->addReplyTo('tenant@example.com', Argument::any())->shouldBeCalled();
        $mailer->setFrom('me@minds.com', 'Sender')->shouldBeCalled();
        $mailer->AddAddress(
            'recipient@example.com',
            'Recipient'
        )->shouldBeCalled();
        $mailer->isHTML(true)->shouldBeCalled();
        $mailer->Send()->shouldBeCalled();

        $this->send($message);
    }

public function it_should_set_replyTo_to_default_when_tenant_replyEmail_is_empty(
    PHPMailer $mailer,
    Queue $queue,
    SpamFilter $filter,
    Logger $logger,
    Config $config,
    TenantConfigsManager $tenantConfigsManager,
    Message $message
) {
    $multiTenantConfig = new MultiTenantConfig(
        replyEmail: ''
    );

    $tenantConfigsManager->getConfigs()->willReturn($multiTenantConfig);
    $config->get('tenant_id')->willReturn('123');

    $this->beConstructedWith($mailer, $queue, $filter, $logger, $config, $tenantConfigsManager);

    $message->to = [['email' => 'recipient@example.com', 'name' => 'Recipient']];
    $message->from = ['email' => 'me@minds.com', 'name' => 'Sender'];

    $mailer->isSMTP()->shouldBeCalled();
    $mailer->ClearAllRecipients()->shouldBeCalled();
    $mailer->clearAttachments()->shouldBeCalled();
    $mailer->ClearReplyTos()->shouldBeCalled();
    $mailer->addReplyTo('no-reply@minds.com', 'Minds')->shouldBeCalled();
    $mailer->setFrom('me@minds.com', 'Sender')->shouldBeCalled();
    $mailer->AddAddress('recipient@example.com', 'Recipient')->shouldBeCalled();
    $mailer->isHTML(true)->shouldBeCalled();
    $mailer->Send()->shouldBeCalled();

    $this->send($message);
}



    public function it_should_not_change_replyTo_when_not_a_tenant_and_no_replyTo_set(
        PHPMailer $mailer,
        Queue $queue,
        SpamFilter $filter,
        Logger $logger,
        Config $config,
        TenantConfigsManager $tenantConfigsManager,
        Message $message
    ) {
        $config->get('tenant_id')->willReturn(null);

        $this->beConstructedWith($mailer, $queue, $filter, $logger, $config, $tenantConfigsManager);

        $message->to = [['email' => 'recipient@example.com', 'name' => 'Recipient']];
        $message->from = ['email' => 'me@minds.com', 'name' => 'Sender'];


        $mailer->isSMTP()->shouldBeCalled();
        $mailer->ClearAllRecipients()->shouldBeCalled();
        $mailer->clearAttachments()->shouldBeCalled();
        $mailer->ClearReplyTos()->shouldNotBeCalled();
        $message->getReplyTo()->willReturn([])->shouldBeCalled();
        $mailer->addReplyTo(Argument::any(), Argument::any())->shouldNotBeCalled();
        $mailer->setFrom('me@minds.com', 'Sender')->shouldBeCalled();
        $mailer->AddAddress(
            'recipient@example.com',
            'Recipient'
        )->shouldBeCalled();
        $mailer->isHTML(true)->shouldBeCalled();
        $message->buildHtml()->shouldBeCalled();
        $mailer->Send()->shouldBeCalled();

        $this->send($message);
    }

    public function it_should_change_replyTo_when_not_a_tenant_and_replyTo_is_set(
        PHPMailer $mailer,
        Queue $queue,
        SpamFilter $filter,
        Logger $logger,
        Config $config,
        TenantConfigsManager $tenantConfigsManager,
        Message $message
    ) {
        $config->get('tenant_id')->willReturn(null);

        $replyToEmail = 'replyto@example.com';
        $replyToName = 'ReplyTo Name';
        $message->setReplyTo($replyToEmail, $replyToName);
        $message->getReplyTo()->willReturn(['email' => $replyToEmail, 'name' => $replyToName]);

        $this->beConstructedWith($mailer, $queue, $filter, $logger, $config, $tenantConfigsManager);

        $message->to = [['email' => 'recipient@example.com', 'name' => 'Recipient']];
        $message->from = ['email' => 'me@minds.com', 'name' => 'Sender'];

        $mailer->isSMTP()->shouldBeCalled();
        $mailer->ClearAllRecipients()->shouldBeCalled();
        $mailer->clearAttachments()->shouldBeCalled();
        $mailer->ClearReplyTos()->shouldBeCalled();
        $mailer->addReplyTo($replyToEmail, $replyToName)->shouldBeCalled();
        $mailer->setFrom('me@minds.com', 'Sender')->shouldBeCalled();
        $mailer->AddAddress('recipient@example.com', 'Recipient')->shouldBeCalled();
        $mailer->isHTML(true)->shouldBeCalled();
        $message->buildHtml()->shouldBeCalled();
        $mailer->Send()->shouldBeCalled();

        $this->send($message);
    }

}
