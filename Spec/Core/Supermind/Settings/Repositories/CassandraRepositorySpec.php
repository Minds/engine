<?php

namespace Spec\Minds\Core\Supermind\Settings\Repositories;

use Minds\Core\Data\Cassandra\Client;
use Minds\Core\Log\Logger;
use Minds\Core\Supermind\Settings\Exceptions\SettingsNotFoundException;
use Minds\Core\Supermind\Settings\Models\Settings;
use Minds\Core\Supermind\Settings\Repositories\CassandraRepository;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Spec\Minds\Mocks\Cassandra\Rows;

class CassandraRepositorySpec extends ObjectBehavior
{
    /** @var Client */
    private $cql;

    /** @var Logger */
    private $logger;

    public function let(Client $cql, Logger $logger)
    {
        $this->beConstructedWith($cql, $logger);
        $this->cql = $cql;
        $this->logger = $logger;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(CassandraRepository::class);
    }

    public function it_should_get_from_cassandra(
        User $user,
        Rows $rows
    ) {
        $rows->first()->shouldBeCalled()->willReturn([
            'value' => json_encode([
                'min_cash' => 10,
                'min_offchain_tokens' => 1
            ])
        ]);

        $this->cql->request(Argument::that(function ($arg) {
            return $arg->getTemplate() === "SELECT * FROM entities WHERE key = ? AND column1 = 'supermind_settings'";
        }))
            ->shouldBeCalled()
            ->willReturn($rows);

        $this->get($user);
    }

    public function it_should_get_from_cassandra_and_throw_exception_when_not_found(
        User $user,
        Rows $rows
    ) {
        $rows->first()->shouldBeCalled()->willReturn([]);

        $this->cql->request(Argument::that(function ($arg) {
            return $arg->getTemplate() === "SELECT * FROM entities WHERE key = ? AND column1 = 'supermind_settings'";
        }))
            ->shouldBeCalled()
            ->willReturn($rows);
        
        $this->shouldThrow(SettingsNotFoundException::class)->during('get', [$user]);
    }

    public function it_should_update_settings(
        User $user,
        Settings $settings
    ) {
        $user->getGuid()
            ->shouldBeCalled()
            ->willReturn('123');

        $settings->jsonSerialize()
            ->shouldBeCalled()
            ->willReturn([
                'min_cash' => 10,
                'min_offchain_tokens' => 1
            ]);

        $this->cql->request(Argument::that(function ($arg) {
            return $arg->getTemplate() === "INSERT INTO entities (key, column1, value) VALUES (?, 'supermind_settings', ?)";
        }))
            ->shouldBeCalled()
            ->willReturn(true);

        $this->update($user, $settings);
    }
}
