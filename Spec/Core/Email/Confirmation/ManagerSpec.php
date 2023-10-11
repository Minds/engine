<?php

namespace Spec\Minds\Core\Email\Confirmation;

use DateTime;
use Exception;
use Minds\Common\Jwt;
use Minds\Core\Config;
use Minds\Core\Data\ElasticSearch\Client;
use Minds\Core\Email\Confirmation\Manager;
use Minds\Core\Entities\Actions\Save;
use Minds\Core\Entities\Resolver;
use Minds\Core\Events\EventsDispatcher;
use Minds\Core\Queue\Interfaces\QueueClient;
use Minds\Entities\User;
use Minds\Entities\UserFactory;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;

class ManagerSpec extends ObjectBehavior
{
    /** @var Config */
    protected $config;

    /** @var Jwt */
    protected $jwt;

    /** @var QueueClient */
    protected $queue;

    /** @var Client */
    protected $es;

    /** @var UserFactory */
    protected $userFactory;

    /** @var Resolver */
    protected $resolver;

    protected Collaborator $saveMock;

    public function let(
        Config $config,
        Jwt $jwt,
        QueueClient $queue,
        Client $es,
        UserFactory $userFactory,
        Resolver $resolver,
        Save $saveMock,
    ) {
        $this->config = $config;
        $this->jwt = $jwt;
        $this->queue = $queue;
        $this->es = $es;
        $this->userFactory = $userFactory;
        $this->resolver = $resolver;
        $this->saveMock = $saveMock;

        $this->config->get('email_confirmation')
            ->willReturn([
                'expiration' => 30,
                'signing_key' => '~key~',
            ]);

        $this->config->get('elasticsearch')
            ->willReturn([
                'indexes' => [
                    'search_prefix' => 'minds-search',
                ]
            ]);


        $this->beConstructedWith($config, $jwt, $queue, $es, $userFactory, $resolver, $saveMock);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    public function it_should_generate_a_token(
        User $user
    ) {
        $user->getEmailConfirmationToken()
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

        $this->saveMock->setEntity($user)
            ->willReturn($this->saveMock);

        $this->saveMock->withMutatedAttributes([
            'email_confirmation_token',
        ])->willReturn($this->saveMock);

        $this->saveMock->save()->shouldBeCalled()->willReturn(true);

        $this
            ->setUser($user)
            ->shouldNotThrow(Exception::class)
            ->duringGenerateConfirmationToken();
    }

    public function it_should_return_existing_token_on_generate_token_if_one_exists(
        User $user
    ) {
        $user->getEmailConfirmationToken()
            ->shouldBeCalled()
            ->willReturn('123');

        $expTime = new DateTime('+1 day');

        $this->jwt->decode('123')
            ->shouldBeCalled()
            ->willReturn([
                'user_guid' => '1000',
                'code' => '~random~',
                'exp' => $expTime,
            ]);
        
        $this->jwt->setKey('~key~')
            ->shouldBeCalled()
            ->willReturn($this->jwt);

        $this
            ->setUser($user)
            ->shouldNotThrow(Exception::class)
            ->duringGenerateConfirmationToken();
    }


    public function it_should_return_new_token_during_generate_token_if_a_token_exists_but_is_expired(
        User $user
    ) {
        $user->getEmailConfirmationToken()
            ->shouldBeCalled()
            ->willReturn('123');

        $this->jwt->setKey('~key~')
            ->shouldBeCalled()
            ->willReturn($this->jwt);
        
        $expTime = new DateTime('-1 day');

        $this->jwt->decode('123')
            ->shouldBeCalled()
            ->willReturn([
                'user_guid' => '1000',
                'code' => '~random~',
                'exp' => $expTime,
            ]);
        
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

        $this->saveMock->setEntity($user)
            ->willReturn($this->saveMock);

        $this->saveMock->withMutatedAttributes([
            'email_confirmation_token',
        ])->willReturn($this->saveMock);

        $this->saveMock->save()->shouldBeCalled()->willReturn(true);

        $this
            ->setUser($user)
            ->shouldNotThrow(Exception::class)
            ->duringGenerateConfirmationToken();
    }

    public function it_should_throw_if_no_user_during_generate_token()
    {
        $this
            ->shouldThrow(new Exception('User not set'))
            ->duringGenerateConfirmationToken();
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

        $this->saveMock->setEntity($user)
            ->willReturn($this->saveMock);

        $this->saveMock->withMutatedAttributes([
            'email_confirmation_token',
            'email_confirmed_at'
        ])->willReturn($this->saveMock);

        $this->saveMock->save()->shouldBeCalled()->willReturn(true);

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

    public function it_should_fetch_unverified_users(User $user1, User $user2)
    {
        $this->es->request(Argument::any())
            ->shouldBeCalled()
            ->willReturn([
                'hits' => [
                    'hits' => [
                        [
                            '_source' => [
                                'guid' => '1',
                            ],
                        ],
                        [
                            '_source' => [
                                'guid' => '2',
                            ],
                        ],
                    ],
                ],
            ]);

        $this->resolver->setUrns(Argument::that(function ($urns) {
            return $urns[0]->getUrn() === 'urn:user:1'
                && $urns[1]->getUrn() === 'urn:user:2';
        }))
            ->shouldBeCalled();

        $this->resolver->fetch()
            ->shouldBeCalled()
            ->willReturn([$user1, $user2]);

        $this->fetchNewUnverifiedUsers()
            ->shouldReturn([$user1, $user2]);
    }

    public function it_should_fetch_unverified_users_but_not_find_any(User $user1, User $user2)
    {
        $this->es->request(Argument::any())
            ->shouldBeCalled()
            ->willReturn([
                'hits' => [
                    'hits' => [],
                ],
            ]);

        $this->resolver->setUrns(Argument::that(function ($urns) {
            return count($urns) === 0;
        }))
            ->shouldBeCalled();

        $this->resolver->fetch()
            ->shouldBeCalled()
            ->willReturn([$user1, $user2]);

        $this->fetchNewUnverifiedUsers()
            ->shouldReturn([$user1, $user2]);
    }
}
