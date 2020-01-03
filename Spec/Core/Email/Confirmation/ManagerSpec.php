<?php

namespace Spec\Minds\Core\Email\Confirmation;

use Exception;
use Minds\Common\Jwt;
use Minds\Core\Config;
use Minds\Core\Email\Confirmation\Manager;
use Minds\Core\Events\EventsDispatcher;
use Minds\Core\Queue\Interfaces\QueueClient;
use Minds\Entities\User;
use Minds\Entities\UserFactory;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ManagerSpec extends ObjectBehavior
{
    /** @var Config */
    protected $config;

    /** @var Jwt */
    protected $jwt;

    /** @var QueueClient */
    protected $queue;

    /** @var UserFactory */
    protected $userFactory;

    /** @var EventsDispatcher */
    protected $eventsDispatcher;

    public function let(
        Config $config,
        Jwt $jwt,
        QueueClient $queue,
        UserFactory $userFactory,
        EventsDispatcher $eventsDispatcher
    ) {
        $this->config = $config;
        $this->jwt = $jwt;
        $this->queue = $queue;
        $this->userFactory = $userFactory;
        $this->eventsDispatcher = $eventsDispatcher;

        $this->config->get('email_confirmation')
            ->willReturn([
                'expiration' => 30,
                'signing_key' => '~key~'
            ]);

        $this->beConstructedWith($config, $jwt, $queue, $userFactory, $eventsDispatcher);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    public function it_should_send_email(
        User $user
    ) {
        $user->isEmailConfirmed()
            ->shouldBeCalled()
            ->willReturn(false);

        $this->jwt->setKey('~key~')
            ->shouldBeCalled()
            ->willReturn($this->jwt);

        $user->get('guid')
            ->shouldBeCalled()
            ->willReturn(1000);

        $this->jwt->randomString()
            ->shouldBeCalled()
            ->willReturn('~random~');

        $this->jwt->encode([
            'user_guid' => '1000',
            'code' => '~random~',
        ], Argument::type('int'), Argument::type('int'))
            ->shouldBeCalled()
            ->willReturn('~token~');

        $user->setEmailConfirmationToken('~token~')
            ->shouldBeCalled()
            ->willReturn($user);

        $user->save()
            ->shouldBeCalled()
            ->willReturn(true);

        $this->eventsDispatcher->trigger('confirmation_email', 'all', [
            'user_guid' => '1000',
            'cache' => false
        ])
            ->shouldBeCalled()
            ->willReturn(true);

        $this
            ->setUser($user)
            ->shouldNotThrow(Exception::class)
            ->duringSendEmail();
    }

    public function it_should_throw_if_no_user_during_send_email()
    {
        $this
            ->shouldThrow(new Exception('User not set'))
            ->duringSendEmail();
    }

    public function it_should_throw_if_email_is_confirmed_during_send_email(
        User $user
    ) {
        $user->isEmailConfirmed()
            ->shouldBeCalled()
            ->willReturn(true);

        $user->save()
            ->shouldNotBeCalled();

        $this
            ->setUser($user)
            ->shouldThrow(new Exception('User email was already confirmed'))
            ->duringSendEmail();
    }

    public function it_should_reset(
        User $user
    ) {
        $user->setEmailConfirmationToken('')
            ->shouldBeCalled()
            ->willReturn($user);

        $user->setEmailConfirmedAt(0)
            ->shouldBeCalled()
            ->willReturn($user);

        $user->save()
            ->shouldBeCalled()
            ->willReturn(true);

        $this
            ->setUser($user)
            ->reset()
            ->shouldReturn(true);
    }

    public function it_should_throw_if_no_user_during_reset()
    {
        $this
            ->shouldThrow(new Exception('User not set'))
            ->duringReset();
    }

    public function it_should_confirm(
        User $user
    ) {
        $this->jwt->setKey('~key~')
            ->shouldBeCalledTimes(2)
            ->willReturn($this->jwt);

        $this->jwt->decode('~token~')
            ->shouldBeCalledTimes(2)
            ->willReturn([
                'user_guid' => '1000',
                'code' => 'phpspec',
            ]);

        $this->userFactory->build('1000', false)
            ->shouldBeCalled()
            ->willReturn($user);

        $user->get('guid')
            ->shouldBeCalled()
            ->willReturn(1000);

        $user->isEmailConfirmed()
            ->shouldBeCalled()
            ->willReturn(false);

        $user->getEmailConfirmationToken()
            ->shouldBeCalled()
            ->willReturn('~token~');

        $user->setEmailConfirmationToken('')
            ->shouldBeCalled()
            ->willReturn($user);

        $user->setEmailConfirmedAt(Argument::type('int'))
            ->shouldBeCalled()
            ->willReturn($user);

        $user->save()
            ->shouldBeCalled()
            ->willReturn(true);

        $this->queue->setQueue('WelcomeEmail')
            ->shouldBeCalled()
            ->willReturn($this->queue);

        $this->queue->send([
            'user_guid' => '1000'
        ])
            ->shouldBeCalled()
            ->willReturn(true);

        $this
            ->confirm('~token~')
            ->shouldReturn(true);
    }

    public function it_should_throw_if_user_is_set_during_confirm(
        User $user
    ) {
        $this
            ->setUser($user)
            ->shouldThrow(new Exception('Confirmation user is inferred from JWT'))
            ->duringConfirm('~token~');
    }

    public function it_should_throw_if_jwt_is_invalid_during_confirm()
    {
        $this->jwt->setKey('~key~')
            ->shouldBeCalled()
            ->willReturn($this->jwt);

        $this->jwt->decode('~token~')
            ->shouldBeCalled()
            ->willReturn([
            ]);

        $this
            ->shouldThrow(new Exception('Invalid JWT'))
            ->duringConfirm('~token~');
    }

    public function it_should_throw_if_invalid_user_during_confirm(
        User $user
    ) {
        $this->jwt->setKey('~key~')
            ->shouldBeCalled()
            ->willReturn($this->jwt);

        $this->jwt->decode('~token~')
            ->shouldBeCalled()
            ->willReturn([
                'user_guid' => '1000',
                'code' => 'phpspec',
            ]);

        $this->userFactory->build('1000', false)
            ->shouldBeCalled()
            ->willReturn($user);

        $user->get('guid')
            ->shouldBeCalled()
            ->willReturn(null);

        $this
            ->shouldThrow(new Exception('Invalid user'))
            ->duringConfirm('~token~');
    }

    public function it_should_throw_if_email_is_confirmed_during_confirm(
        User $user
    ) {
        $this->jwt->setKey('~key~')
            ->shouldBeCalled()
            ->willReturn($this->jwt);

        $this->jwt->decode('~token~')
            ->shouldBeCalled()
            ->willReturn([
                'user_guid' => '1000',
                'code' => 'phpspec',
            ]);

        $this->userFactory->build('1000', false)
            ->shouldBeCalled()
            ->willReturn($user);

        $user->get('guid')
            ->shouldBeCalled()
            ->willReturn($user);

        $user->isEmailConfirmed()
            ->shouldBeCalled()
            ->willReturn(true);

        $this
            ->shouldThrow(new Exception('User email was already confirmed'))
            ->duringConfirm('~token~');
    }

    public function it_should_throw_if_invalid_token_data_during_confirm(
        User $user
    ) {
        $this->jwt->setKey('~key~')
            ->shouldBeCalledTimes(2)
            ->willReturn($this->jwt);

        $this->jwt->decode('~token~')
            ->shouldBeCalled()
            ->willReturn([
                'user_guid' => '1000',
                'code' => 'phpspec',
            ]);

        $this->userFactory->build('1000', false)
            ->shouldBeCalled()
            ->willReturn($user);

        $user->get('guid')
            ->shouldBeCalled()
            ->willReturn($user);

        $user->isEmailConfirmed()
            ->shouldBeCalled()
            ->willReturn(false);

        $user->getEmailConfirmationToken()
            ->shouldBeCalled()
            ->willReturn('~token.2~');

        $this->jwt->decode('~token.2~')
            ->shouldBeCalled()
            ->willReturn([
                'user_guid' => '1001',
                'code' => 'phpspec_fail',
            ]);

        $this
            ->shouldThrow(new Exception('Invalid confirmation token data'))
            ->duringConfirm('~token~');
    }
}
