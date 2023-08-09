<?php
declare(strict_types=1);

namespace Spec\Minds\Core\Monetization\Partners;

use Minds\Core\Data\MySQL\Client;
use Minds\Core\Log\Logger;
use Minds\Core\Monetization\Partners\EarningsBalance;
use Minds\Core\Monetization\Partners\EarningsDeposit;
use Minds\Core\Monetization\Partners\RelationalRepository;
use PDO;
use PDOStatement;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;
use Selective\Database\Connection;
use Selective\Database\InsertQuery;
use Selective\Database\Operator;
use Selective\Database\RawExp;
use Selective\Database\SelectQuery;
use Spec\Minds\Common\Traits\CommonMatchers;

class RelationalRepositorySpec extends ObjectBehavior
{
    use CommonMatchers;

    private Collaborator $mysqlHandlerMock;

    private Collaborator $mysqlClientReaderMock;
    private Collaborator $mysqlClientWriterMock;

    private Collaborator $mysqlClientReaderHandlerMock;
    private Collaborator $mysqlClientWriterHandlerMock;

    public function let(
        Client $mysqlHandlerMock,
        PDO $mysqlClientReaderMock,
        PDO $mysqlClientWriterMock,
        Connection $mysqlClientReaderHandlerMock,
        Connection $mysqlClientWriterHandlerMock,
        Logger $loggerMock
    ): void {
        $this->mysqlHandlerMock = $mysqlHandlerMock;

        $this->mysqlHandlerMock->getConnection(Client::CONNECTION_REPLICA)
            ->willReturn($this->mysqlClientReaderMock = $mysqlClientReaderMock);
        $this->mysqlHandlerMock->getConnection(Client::CONNECTION_MASTER)
            ->willReturn($this->mysqlClientWriterMock = $mysqlClientWriterMock);

        $mysqlClientReaderHandlerMock->getPdo()->willReturn($this->mysqlClientReaderMock);
        $this->mysqlClientReaderHandlerMock = $mysqlClientReaderHandlerMock;

        $mysqlClientReaderHandlerMock->getPdo()->willReturn($this->mysqlClientWriterMock);
        $this->mysqlClientWriterHandlerMock = $mysqlClientWriterHandlerMock;

        $this->beConstructedWith(
            $this->mysqlHandlerMock,
            $loggerMock,
            $this->mysqlClientReaderHandlerMock,
            $this->mysqlClientWriterHandlerMock
        );
    }

    public function it_is_initializable(): void
    {
        $this->shouldBeAnInstanceOf(RelationalRepository::class);
    }

    public function it_should_get_balances_per_user(
        PDOStatement $statementMock,
        SelectQuery $selectQueryMock
    ): void {
        $toTimestamp = time();

        $statementMock->execute()
            ->shouldBeCalledOnce();
        $statementMock->fetchAll(PDO::FETCH_ASSOC)
            ->shouldBeCalledOnce()
            ->willReturn([
                [
                    'user_guid' => '1234',
                    'total_cents' => '1000',
                ]
            ]);

        $selectQueryMock->from('minds_partner_earnings')
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);
        $selectQueryMock->columns(Argument::type('array'))
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);
        $selectQueryMock->groupBy('user_guid')
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);
        $selectQueryMock->where('timestamp', Operator::LTE, Argument::type(RawExp::class))
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);
        $selectQueryMock->prepare()
            ->shouldBeCalledOnce()
            ->willReturn($statementMock);

        $this->mysqlClientReaderHandlerMock->select()
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);

        $this->mysqlHandlerMock->bindValuesToPreparedStatement($statementMock, [
            'to' => date('c', $toTimestamp)
        ])
            ->shouldBeCalledOnce();

        $this->getBalancesPerUser(null, $toTimestamp)->shouldBeAGeneratorWithValues([
            (new EarningsBalance())
                ->setUserGuid('1234')
                ->setAmountCents(1000)
        ]);
    }

    public function it_should_add_a_deposit(
        EarningsDeposit $depositMock,
        InsertQuery $insertQueryMock,
        PDOStatement $statementMock
    ): void {
        $depositTimestamp = time();
        $depositMock = (new EarningsDeposit())
            ->setUserGuid('1234')
            ->setAmountCents(1000)
            ->setItem('item')
            ->setTimestamp($depositTimestamp);

        $statementMock->execute()
            ->shouldBeCalledOnce();
        $statementMock->rowCount()
            ->shouldBeCalledOnce()
            ->willReturn(1);

        $insertQueryMock->into('minds_partner_earnings')
            ->shouldBeCalledOnce()
            ->willReturn($insertQueryMock);
        $insertQueryMock->set(Argument::that(function (array $values) use ($depositTimestamp): bool {
            return $values['user_guid'] === 1234
                && $values['amount_cents'] === 1000
                && $values['amount_tokens'] === null
                && $values['item'] === 'item'
                && $values['timestamp'] === date('c', $depositTimestamp);
        }))
            ->shouldBeCalledOnce()
            ->willReturn($insertQueryMock);
        $insertQueryMock->prepare()
            ->shouldBeCalledOnce()
            ->willReturn($statementMock);

        $this->mysqlClientWriterHandlerMock->insert()
            ->shouldBeCalledOnce()
            ->willReturn($insertQueryMock);

        $this->add($depositMock)->shouldBe(true);
    }

    public function it_should_get_balance(
        SelectQuery $selectQueryMock,
        PDOStatement $statementMock
    ): void {
        $toTimestamp = time();

        $statementMock->execute()
            ->shouldBeCalledOnce();
        $statementMock->fetch(PDO::FETCH_ASSOC)
            ->shouldBeCalledOnce()
            ->willReturn([
                'user_guid' => '1234',
                'cents' => '1000',
                'tokens' => '1000',
            ]);

        $selectQueryMock->from('minds_partner_earnings')
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);
        $selectQueryMock->columns(Argument::that(function ($items): bool {
            /**
             * @var RawExp $amountCents
             */
            $amountCents = $items[0];
            if ($amountCents->getValue() !== "SUM(amount_cents) AS cents") {
                return false;
            }
            /**
             * @var RawExp $amountTokens
             */
            $amountTokens = $items[1];
            if ($amountTokens->getValue() !== "SUM(amount_tokens) AS tokens") {
                return false;
            }
            return true;
        }))
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);

        $selectQueryMock->where('user_guid', Operator::EQ, Argument::that(fn (RawExp $expression): bool => $expression->getValue() === ':user_guid'))
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);

        $selectQueryMock->where('timestamp', Operator::LTE, Argument::that(fn (RawExp $expression): bool => $expression->getValue() === ':timestamp'))
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);

        $selectQueryMock->prepare()
            ->shouldBeCalledOnce()
            ->willReturn($statementMock);

        $this->mysqlClientReaderHandlerMock->select()
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);

        $this->mysqlHandlerMock->bindValuesToPreparedStatement($statementMock, [
            'user_guid' => 1234,
            'timestamp' => date('c', $toTimestamp)
        ])
            ->shouldBeCalledOnce();

        $this->getBalance(1234, $toTimestamp)->shouldBeLike(
            (new EarningsBalance())
                ->setUserGuid('1234')
                ->setAmountCents(1000)
                ->setAmountTokens(1000)
        );
    }

    public function it_should_get_balance_by_item(
        SelectQuery $selectQueryMock,
        PDOStatement $statementMock
    ): void {
        $toTimestamp = time();
        $items = [
            'item1',
            'item2',
        ];

        $statementMock->execute()
            ->shouldBeCalledOnce();
        $statementMock->fetch(PDO::FETCH_ASSOC)
            ->shouldBeCalledOnce()
            ->willReturn([
                'user_guid' => '1234',
                'cents' => '1000',
                'tokens' => '1000',
            ]);

        $selectQueryMock->from('minds_partner_earnings')
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);
        $selectQueryMock->columns(Argument::that(function ($items): bool {
            /**
             * @var RawExp $amountCents
             */
            $amountCents = $items[0];
            if ($amountCents->getValue() !== "SUM(amount_cents) AS cents") {
                return false;
            }
            /**
             * @var RawExp $amountTokens
             */
            $amountTokens = $items[1];
            if ($amountTokens->getValue() !== "SUM(amount_tokens) AS tokens") {
                return false;
            }
            return true;
        }))
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);

        $selectQueryMock->where('user_guid', Operator::EQ, Argument::that(fn (RawExp $expression): bool => $expression->getValue() === ':user_guid'))
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);

        $selectQueryMock->where('timestamp', Operator::LTE, Argument::that(fn (RawExp $expression): bool => $expression->getValue() === ':timestamp'))
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);

        $selectQueryMock->where('item', Operator::IN, $items)
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);

        $selectQueryMock->prepare()
            ->shouldBeCalledOnce()
            ->willReturn($statementMock);

        $this->mysqlClientReaderHandlerMock->select()
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);

        $this->mysqlHandlerMock->bindValuesToPreparedStatement($statementMock, [
            'user_guid' => 1234,
            'timestamp' => date('c', $toTimestamp)
        ])
            ->shouldBeCalledOnce();

        $this->getBalanceByItem(1234, $items, $toTimestamp)->shouldBeLike(
            (new EarningsBalance())
                ->setUserGuid('1234')
                ->setAmountCents(1000)
                ->setAmountTokens(1000)
        );
    }
}
