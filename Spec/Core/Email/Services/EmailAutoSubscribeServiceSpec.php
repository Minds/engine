<?php
declare(strict_types=1);

namespace Spec\Minds\Core\Email\Services;

use Minds\Core\Config\Config;
use Minds\Core\Email\EmailSubscription;
use Minds\Core\Email\Repository;
use Minds\Core\Email\Services\EmailAutoSubscribeService;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;

class EmailAutoSubscribeServiceSpec extends ObjectBehavior
{
    private Collaborator $repositoryMock;
    private Collaborator $configMock;

    public function let(
        Repository $repository,
        Config     $config
    ): void {
        $this->repositoryMock = $repository;
        $this->configMock = $config;

        $this->beConstructedWith(
            $this->repositoryMock,
            $this->configMock
        );
    }

    public function it_is_initializable(): void
    {
        $this->shouldBeAnInstanceOf(EmailAutoSubscribeService::class);
    }

    public function it_should_subscribe_to_default_emails(): void
    {
        $defaultEmailSubscription = [
            'campaign' => 'when',
            'topic' => 'unread_notifications',
            'value' => true,
        ];
        $this->configMock->get('default_email_subscriptions')
            ->shouldBeCalledOnce()
            ->willReturn([
                $defaultEmailSubscription
            ]);

        $this->repositoryMock->add(new EmailSubscription([
            ...$defaultEmailSubscription,
            'userGuid' => 1,
        ]));

        $this->subscribeToDefaultEmails(1);
    }
}
