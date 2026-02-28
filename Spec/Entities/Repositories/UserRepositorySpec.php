<?php

namespace Spec\Minds\Entities\Repositories;

use Cassandra\Varint;
use Minds\Core\Data\Cassandra\Client;
use Minds\Entities\Repositories\UserRepository;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Subject;
use Prophecy\Argument;
use Spec\Minds\Mocks\Cassandra\Rows;
use Minds\Core\Data\Cassandra\Prepared\Custom;

/**
 * Class UserRepositorySpec
 * @package Spec\Minds\Core\Trending
 * @mixin UserRepository
 */
class UserRepositorySpec extends ObjectBehavior
{
    protected $_client;

    public function let(Client $client)
    {
        $this->_client = $client;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType('Minds\Entities\Repositories\UserRepository');
    }

    public function it_should_get_the_users_friend_list(Client $client)
    {
        $rows = new Rows([
            [ 'username' => 'steve' ],
            [ 'username' => 'tom' ],
            [ 'username' => 'berry' ],
        ], '');

        $client->request(Argument::type(Custom::class))
            ->shouldBeCalled()
            ->willReturn($rows);

        /** @var Subject $return */
        $return = $this::getUsersList($this->_client);

        $return->shouldBeArray();
        $return->shouldHaveCount(3);
        $return->shouldContain('steve');
        $return->shouldContain('tom');
    }
}
