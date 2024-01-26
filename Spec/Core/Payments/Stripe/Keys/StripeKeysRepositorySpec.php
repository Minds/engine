<?php

namespace Spec\Minds\Core\Payments\Stripe\Keys;

use Minds\Core\Config\Config;
use Minds\Core\Data\MySQL\MySQLConnectionEnum;
use Minds\Core\Di\Di;
use Minds\Core\Payments\Stripe\Keys\StripeKeysRepository;
use Minds\Core\Data\MySQL\Client as MySQLClient;
use PDO;
use PDOStatement;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;

class StripeKeysRepositorySpec extends ObjectBehavior
{
    private Collaborator $mysqlClientMock;
    private Collaborator $mysqlMasterMock;
    private Collaborator $mysqlReplicaMock;

    public function let(
        Config $configMock,
        MySQLClient $mysqlClientMock,
        PDO $mysqlMasterMock,
        PDO $mysqlReplicaMock
    ) {
        $this->beConstructedWith($mysqlClientMock, $configMock, Di::_()->get('Logger'));

        $this->mysqlClientMock = $mysqlClientMock;

        $this->mysqlClientMock->getConnection(MySQLConnectionEnum::MASTER)
            ->willReturn($mysqlMasterMock);
        $this->mysqlMasterMock = $mysqlMasterMock;

        $this->mysqlClientMock->getConnection(MySQLConnectionEnum::REPLICA)
            ->willReturn($mysqlReplicaMock);
        $this->mysqlReplicaMock = $mysqlReplicaMock;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(StripeKeysRepository::class);
    }

    public function it_should_set_keys(PDOStatement $stmtMock)
    {
        $this->mysqlMasterMock->quote(Argument::any())->willReturn("");
        $this->mysqlMasterMock->prepare(Argument::any())->willReturn($stmtMock);

        $stmtMock->execute([
            'pub_key' => 'pub',
            'sec_key_cipher_text' => 'sec',
        ])->willReturn(true);

        $this->setKeys('pub', 'sec')->shouldBe(true);
    }

    public function it_should_get_keys(PDOStatement $stmtMock)
    {
        $this->mysqlReplicaMock->quote(Argument::any())->willReturn("");
        $this->mysqlReplicaMock->prepare(Argument::any())->willReturn($stmtMock);

        $stmtMock->execute()->willReturn(true);

        $stmtMock->rowCount()->willReturn(1);

        $stmtMock->fetch(PDO::FETCH_ASSOC)
            ->willReturn([
                'pub', 'sec'
            ]);

        $this->getKeys()->shouldBe([
            'pub', 'sec'
        ]);
    }

    public function it_should_return_null_if_no_keys(PDOStatement $stmtMock)
    {
        $this->mysqlReplicaMock->quote(Argument::any())->willReturn("");
        $this->mysqlReplicaMock->prepare(Argument::any())->willReturn($stmtMock);

        $stmtMock->execute()->willReturn(true);

        $stmtMock->rowCount()->willReturn(0);

        $stmtMock->fetch(PDO::FETCH_ASSOC)
            ->shouldNotBeCalled();
    
        $this->getKeys()->shouldBe(null);
    }
}
