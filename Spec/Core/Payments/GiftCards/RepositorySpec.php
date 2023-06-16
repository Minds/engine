<?php

namespace Spec\Minds\Core\Payments\GiftCards;

use Minds\Core\Payments\GiftCards\Repository;
use Minds\Core\Data\MySQL\Client as MySQLClient;
use Minds\Core\Data\MySQL\MySQLConnectionEnum;
use Minds\Core\Di\Di;
use Minds\Core\Payments\GiftCards\Enums\GiftCardProductIdEnum;
use Minds\Core\Payments\GiftCards\Models\GiftCard;
use Minds\Core\Payments\GiftCards\Models\GiftCardTransaction;
use PDO;
use PDOStatement;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class RepositorySpec extends ObjectBehavior
{
    private $mysqlClientMock;
    private $mysqlMasterMock;
    private $mysqlReplicaMock;

    function let(MySQLClient $mysqlClient, PDO $mysqlMasterMock, PDO $mysqlReplicaMock) {
        $this->beConstructedWith($mysqlClient, Di::_()->get('Logger'));
        $this->mysqlClientMock = $mysqlClient;

        $this->mysqlClientMock->getConnection(MySQLConnectionEnum::MASTER)
            ->willReturn($mysqlMasterMock);
        $this->mysqlMasterMock = $mysqlMasterMock;

        $this->mysqlClientMock->getConnection(MySQLConnectionEnum::REPLICA)
            ->wilLReturn($mysqlReplicaMock);
        $this->mysqlReplicaMock = $mysqlReplicaMock;
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(Repository::class);
    }

    function it_should_add_gift_card_to_database(PDOStatement $pdoStatementMock)
    {
        $refTime = time();
        $giftCard = new GiftCard(1244987032468459522, GiftCardProductIdEnum::BOOST, 10, 1244987032468459522, $refTime, 'change-me', strtotime('+1 year', $refTime));

        // Confirm our values are correctly cast
        $this->mysqlMasterMock->quote(Argument::that(function ($value) use ($refTime) {
            return match($value) {
                "1244987032468459522" => true,
                (string) GiftCardProductIdEnum::BOOST->value => true,
                "10" => true,
                "1244987032468459522" => true,
                date('c', $refTime) => true,
                'change-me' => true,
                date('c', strtotime('+1 year', $refTime)) => true,
            };
        }))->shouldBeCalled();;

        $this->mysqlMasterMock->prepare(Argument::type('string'))->shouldBeCalled()->willReturn($pdoStatementMock);
        $pdoStatementMock->execute()->shouldBeCalled()->willReturn(true);

        $this->addGiftCard($giftCard)
            ->shouldBe(true);
    }

    function it_should_return_an_unclaimed_gift_card(PDOStatement $pdoStatementMock)
    {
        $refTime = time();

        $this->mysqlReplicaMock->quote("1244987032468459522")->shouldBeCalled()->willReturn("1244987032468459522");
        $this->mysqlReplicaMock->query(Argument::type('string'))->willReturn($pdoStatementMock);
        
        $pdoStatementMock->fetchAll(PDO::FETCH_ASSOC)
            ->willReturn(
                [
                    [
                        'guid' => 1244987032468459522,
                        'product_id' => 1,
                        'amount' => 9.99,
                        'issued_by_guid' => 1244987032468459522,
                        'issued_at' => date('c', $refTime),
                        'claim_code' => 'change-me',
                        'expires_at' => date('c', strtotime('+1 year', $refTime)),
                        'claimed_by_guid' => null,
                        'claimed_at' => null,
                    ]
                ]
            );
    
        $giftCard = $this->getGiftCard(1244987032468459522);
        $giftCard->guid->shouldBe(1244987032468459522);
        $giftCard->productId->shouldBe(GiftCardProductIdEnum::PLUS);
        $giftCard->amount->shouldBe(9.99);
        $giftCard->issuedByGuid->shouldBe(1244987032468459522);
        $giftCard->issuedAt->shouldBe($refTime);
        $giftCard->claimCode->shouldBe('change-me');
        $giftCard->expiresAt->shouldBe(strtotime('+1 year', $refTime));
        $giftCard->claimedByGuid->shouldBe(null);
        $giftCard->claimedAt->shouldBe(null);
    }

    function it_should_return_an_claimed_gift_card(PDOStatement $pdoStatementMock)
    {
        $refTime = time();

        $this->mysqlReplicaMock->quote("1244987032468459522")->shouldBeCalled()->willReturn("1244987032468459522");
        $this->mysqlReplicaMock->query(Argument::type('string'))->willReturn($pdoStatementMock);
        
        $pdoStatementMock->fetchAll(PDO::FETCH_ASSOC)
            ->willReturn(
                [
                    [
                        'guid' => 1244987032468459522,
                        'product_id' => 1,
                        'amount' => 9.99,
                        'issued_by_guid' => 1244987032468459522,
                        'issued_at' => date('c', $refTime),
                        'claim_code' => 'change-me',
                        'expires_at' => date('c', strtotime('+1 year', $refTime)),
                        'claimed_by_guid' => 1244987032468459523,
                        'claimed_at' => date('c', $refTime),
                    ]
                ]
            );
    
        $giftCard = $this->getGiftCard(1244987032468459522);
        $giftCard->guid->shouldBe(1244987032468459522);
        $giftCard->productId->shouldBe(GiftCardProductIdEnum::PLUS);
        $giftCard->amount->shouldBe(9.99);
        $giftCard->issuedByGuid->shouldBe(1244987032468459522);
        $giftCard->issuedAt->shouldBe($refTime);
        $giftCard->claimCode->shouldBe('change-me');
        $giftCard->expiresAt->shouldBe(strtotime('+1 year', $refTime));
        $giftCard->claimedByGuid->shouldBe(1244987032468459523);
        $giftCard->claimedAt->shouldBe($refTime);
    }

    function it_should_update_claimed_giftCard(PDOStatement $pdoStatementMock)
    {
        $refTime = time();
        $giftCard = new GiftCard(1244987032468459522, GiftCardProductIdEnum::BOOST, 10, 1244987032468459522, $refTime, 'change-me', strtotime('+1 year', $refTime), 1244987032468459523, $refTime);

        // Confirm our values are correctly cast
        $this->mysqlMasterMock->quote(Argument::that(function ($value) use ($refTime) {
            return match($value) {
                "1244987032468459522" => true,
                "1244987032468459523" => true,
                date('c', $refTime) => true,
            };
        }))->shouldBeCalled();;

        $this->mysqlMasterMock->prepare(Argument::type('string'))->shouldBeCalled()->willReturn($pdoStatementMock);
        $pdoStatementMock->execute()->shouldBeCalled()->willReturn(true);

        $this->updateGiftCardClaim($giftCard)
            ->shouldBe(true);
    }

    function it_should_add_gift_card_transaction(PDOStatement $pdoStatementMock)
    {
        $refTime = time();
        $giftCardTx = new GiftCardTransaction(1244987032468459524, 1244987032468459522, 9.99, $refTime);

        // Confirm our values are correctly cast
        $this->mysqlMasterMock->quote(Argument::that(function ($value) use ($refTime) {
            return match($value) {
                "1244987032468459524" => true,
                "1244987032468459522" => true,
                "9.99" => true,
                date('c', $refTime) => true,
            };
        }))->shouldBeCalled();;

        $this->mysqlMasterMock->prepare(Argument::type('string'))->shouldBeCalled()->willReturn($pdoStatementMock);
        $pdoStatementMock->execute()->shouldBeCalled()->willReturn(true);

        $this->addGiftCardTransaction($giftCardTx)
            ->shouldBe(true);
    }
}
