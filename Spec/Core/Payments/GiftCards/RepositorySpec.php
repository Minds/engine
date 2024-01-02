<?php

namespace Spec\Minds\Core\Payments\GiftCards;

use Minds\Core\Config\Config;
use Minds\Core\Data\MySQL\Client as MySQLClient;
use Minds\Core\Data\MySQL\MySQLConnectionEnum;
use Minds\Core\Di\Di;
use Minds\Core\Payments\GiftCards\Enums\GiftCardProductIdEnum;
use Minds\Core\Payments\GiftCards\Models\GiftCard;
use Minds\Core\Payments\GiftCards\Models\GiftCardTransaction;
use Minds\Core\Payments\GiftCards\Repository;
use PDO;
use PDOStatement;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class RepositorySpec extends ObjectBehavior
{
    private $mysqlClientMock;
    private $mysqlMasterMock;
    private $mysqlReplicaMock;

    public function let(MySQLClient $mysqlClient, PDO $mysqlMasterMock, PDO $mysqlReplicaMock)
    {
        $this->beConstructedWith($mysqlClient, Di::_()->get(Config::class), Di::_()->get('Logger'));
        $this->mysqlClientMock = $mysqlClient;

        $this->mysqlClientMock->getConnection(MySQLConnectionEnum::MASTER)
            ->willReturn($mysqlMasterMock);
        $this->mysqlMasterMock = $mysqlMasterMock;

        $this->mysqlClientMock->getConnection(MySQLConnectionEnum::REPLICA)
            ->wilLReturn($mysqlReplicaMock);
        $this->mysqlReplicaMock = $mysqlReplicaMock;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Repository::class);
    }

    public function it_should_add_gift_card_to_database(PDOStatement $pdoStatementMock)
    {
        $refTime = time();
        $giftCard = new GiftCard(1244987032468459522, GiftCardProductIdEnum::BOOST, 10, 1244987032468459522, $refTime, 'change-me', strtotime('+1 year', $refTime));

        // Confirm our values are correctly cast
        $this->mysqlMasterMock->quote(Argument::that(function ($value) use ($refTime) {
            return match ($value) {
                "1244987032468459522" => true,
                (string) GiftCardProductIdEnum::BOOST->value => true,
                "10" => true,
                "1244987032468459522" => true,
                date('c', $refTime) => true,
                'change-me' => true,
                date('c', strtotime('+1 year', $refTime)) => true,
                default => throw new \Exception('Unexpected match value'),
            };
        }))->shouldBeCalled();
        ;

        $this->mysqlMasterMock->prepare(Argument::type('string'))->shouldBeCalled()->willReturn($pdoStatementMock);
        $pdoStatementMock->execute()->shouldBeCalled()->willReturn(true);

        $this->addGiftCard($giftCard)
            ->shouldBe(true);
    }

    public function it_should_return_an_unclaimed_gift_card(PDOStatement $pdoStatementMock)
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
                        'balance' => 9.99,
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

    public function it_should_return_an_claimed_gift_card(PDOStatement $pdoStatementMock)
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
                        'balance' => 9.99,
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

    public function it_should_update_claimed_giftCard(PDOStatement $pdoStatementMock)
    {
        $refTime = time();
        $giftCard = new GiftCard(1244987032468459522, GiftCardProductIdEnum::BOOST, 10, 1244987032468459522, $refTime, 'change-me', strtotime('+1 year', $refTime), 1244987032468459523, $refTime);

        // Confirm our values are correctly cast
        $this->mysqlMasterMock->quote(Argument::that(function ($value) use ($refTime) {
            return match ($value) {
                "1244987032468459522" => true,
                "1244987032468459523" => true,
                date('c', $refTime) => true,
                default => throw new \Exception('Unexpected match value'),
            };
        }))->shouldBeCalled();
        ;

        $this->mysqlMasterMock->prepare(Argument::type('string'))->shouldBeCalled()->willReturn($pdoStatementMock);
        $pdoStatementMock->execute()->shouldBeCalled()->willReturn(true);

        $this->updateGiftCardClaim($giftCard)
            ->shouldBe(true);
    }

    public function it_should_add_gift_card_transaction(PDOStatement $pdoStatementMock)
    {
        $refTime = time();
        $giftCardTx = new GiftCardTransaction(1244987032468459524, 1244987032468459522, 9.99, $refTime);

        // Confirm our values are correctly cast
        $this->mysqlMasterMock->quote(Argument::that(function ($value) use ($refTime) {
            return match ($value) {
                "1244987032468459524" => true,
                "1244987032468459522" => true,
                "9.99" => true,
                date('c', $refTime) => true,
                default => throw new \Exception('Unexpected match value'),
            };
        }))->shouldBeCalled();
        ;

        $this->mysqlMasterMock->prepare(Argument::type('string'))->shouldBeCalled()->willReturn($pdoStatementMock);
        $pdoStatementMock->execute()->shouldBeCalled()->willReturn(true);

        $this->addGiftCardTransaction($giftCardTx)
            ->shouldBe(true);
    }

    public function it_should_return_issued_gift_cards_list(PDOStatement $pdoStatementMock)
    {
        $refTime = time();

        $this->mysqlReplicaMock->quote(Argument::that(function ($value) {
            return match ($value) {
                "1244987032468459522" => true,
                default => false,
            };
        }))->shouldBeCalled();
        $this->mysqlReplicaMock->query(Argument::type('string'))->willReturn($pdoStatementMock);
        $this->mysqlReplicaMock->prepare(Argument::any())->willReturn($pdoStatementMock);
        
        $pdoStatementMock->execute([]);

        $pdoStatementMock->fetchAll(PDO::FETCH_ASSOC)
            ->willReturn(
                [
                    [
                        'guid' => 1244987032468459523,
                        'product_id' => 1,
                        'amount' => 9.99,
                        'issued_by_guid' => 1244987032468459522,
                        'issued_at' => date('c', $refTime),
                        'claim_code' => 'change-me',
                        'expires_at' => date('c', strtotime('+1 year', $refTime)),
                        'claimed_by_guid' => 1244987032468459524,
                        'claimed_at' => date('c', $refTime),
                        'balance' => 9.99,
                    ],
                    [
                        'guid' => 1244987032468459526,
                        'product_id' => 1,
                        'amount' => 4.99,
                        'issued_by_guid' => 1244987032468459522,
                        'issued_at' => date('c', $refTime),
                        'claim_code' => 'change-me',
                        'expires_at' => date('c', strtotime('+1 year', $refTime)),
                        'claimed_by_guid' => 1244987032468459524,
                        'claimed_at' => date('c', $refTime),
                        'balance' => 0.99,
                    ]
                ]
            );
    

        $result = $this->getGiftCards(
            issuedByGuid: 1244987032468459522,
            limit: 10,
        );
        $result->shouldYieldLike(new \ArrayIterator([
            new GiftCard(
                guid: 1244987032468459523,
                productId: GiftCardProductIdEnum::PLUS,
                amount: 9.99,
                issuedByGuid: 1244987032468459522,
                issuedAt: $refTime,
                claimCode: 'change-me',
                expiresAt: strtotime('+1 year', $refTime),
                claimedByGuid: 1244987032468459524,
                claimedAt: $refTime,
                balance: 9.99,
            ),
            new GiftCard(
                guid: 1244987032468459526,
                productId: GiftCardProductIdEnum::PLUS,
                amount: 4.99,
                issuedByGuid: 1244987032468459522,
                issuedAt: $refTime,
                claimCode: 'change-me',
                expiresAt: strtotime('+1 year', $refTime),
                claimedByGuid: 1244987032468459524,
                claimedAt: $refTime,
                balance: 0.99,
            ),
        ]));
    }

    public function it_should_return_claimed_gift_cards_list(PDOStatement $pdoStatementMock)
    {
        $refTime = time();

        $this->mysqlReplicaMock->quote(Argument::that(function ($value) {
            return match ($value) {
                "1244987032468459522" => true,
                default => false,
            };
        }))->shouldBeCalled();
        $this->mysqlReplicaMock->query(Argument::type('string'))->willReturn($pdoStatementMock);
        $this->mysqlReplicaMock->prepare(Argument::any())->willReturn($pdoStatementMock);
        
        $pdoStatementMock->execute([]);

        $pdoStatementMock->fetchAll(PDO::FETCH_ASSOC)
            ->willReturn(
                [
                    [
                        'guid' => 1244987032468459523,
                        'product_id' => 1,
                        'amount' => 9.99,
                        'issued_by_guid' => 1244987032468459522,
                        'issued_at' => date('c', $refTime),
                        'claim_code' => 'change-me',
                        'expires_at' => date('c', strtotime('+1 year', $refTime)),
                        'claimed_by_guid' => 1244987032468459524,
                        'claimed_at' => date('c', $refTime),
                        'balance' => 9.99,
                    ],
                    [
                        'guid' => 1244987032468459526,
                        'product_id' => 1,
                        'amount' => 4.99,
                        'issued_by_guid' => 1244987032468459522,
                        'issued_at' => date('c', $refTime),
                        'claim_code' => 'change-me',
                        'expires_at' => date('c', strtotime('+1 year', $refTime)),
                        'claimed_by_guid' => 1244987032468459524,
                        'claimed_at' => date('c', $refTime),
                        'balance' => 0.99,
                    ]
                ]
            );
    

        $result = $this->getGiftCards(
            claimedByGuid: 1244987032468459522,
            limit: 10,
        );
        $result->shouldYieldLike(new \ArrayIterator([
            new GiftCard(
                guid: 1244987032468459523,
                productId: GiftCardProductIdEnum::PLUS,
                amount: 9.99,
                issuedByGuid: 1244987032468459522,
                issuedAt: $refTime,
                claimCode: 'change-me',
                expiresAt: strtotime('+1 year', $refTime),
                claimedByGuid: 1244987032468459524,
                claimedAt: $refTime,
                balance: 9.99,
            ),
            new GiftCard(
                guid: 1244987032468459526,
                productId: GiftCardProductIdEnum::PLUS,
                amount: 4.99,
                issuedByGuid: 1244987032468459522,
                issuedAt: $refTime,
                claimCode: 'change-me',
                expiresAt: strtotime('+1 year', $refTime),
                claimedByGuid: 1244987032468459524,
                claimedAt: $refTime,
                balance: 0.99,
            ),
        ]));
    }

    public function it_should_return_issued_and_claimed_gift_cards_list(PDOStatement $pdoStatementMock)
    {
        $refTime = time();

        $this->mysqlReplicaMock->query(Argument::type('string'))->willReturn($pdoStatementMock);
        $this->mysqlReplicaMock->prepare(Argument::any())->willReturn($pdoStatementMock);
        
        $pdoStatementMock->execute([
            'claimed_by_guid' => 1244987032468459524,
            'issued_by_guid' => 1244987032468459524,
        ]);

        $pdoStatementMock->fetchAll(PDO::FETCH_ASSOC)
            ->willReturn(
                [
                    [
                        'guid' => 1244987032468459523,
                        'product_id' => 1,
                        'amount' => 9.99,
                        'issued_by_guid' => 1244987032468459522,
                        'issued_at' => date('c', $refTime),
                        'claim_code' => 'change-me',
                        'expires_at' => date('c', strtotime('+1 year', $refTime)),
                        'claimed_by_guid' => 1244987032468459524,
                        'claimed_at' => date('c', $refTime),
                        'balance' => 9.99,
                    ],
                    [
                        'guid' => 1244987032468459526,
                        'product_id' => 1,
                        'amount' => 4.99,
                        'issued_by_guid' => 1244987032468459522,
                        'issued_at' => date('c', $refTime),
                        'claim_code' => 'change-me',
                        'expires_at' => date('c', strtotime('+1 year', $refTime)),
                        'claimed_by_guid' => 1244987032468459524,
                        'claimed_at' => date('c', $refTime),
                        'balance' => 0.99,
                    ]
                ]
            );
    

        $result = $this->getGiftCards(
            issuedByGuid: 1244987032468459524,
            claimedByGuid: 1244987032468459524,
            limit: 10,
        );
        $result->shouldYieldLike(new \ArrayIterator([
            new GiftCard(
                guid: 1244987032468459523,
                productId: GiftCardProductIdEnum::PLUS,
                amount: 9.99,
                issuedByGuid: 1244987032468459522,
                issuedAt: $refTime,
                claimCode: 'change-me',
                expiresAt: strtotime('+1 year', $refTime),
                claimedByGuid: 1244987032468459524,
                claimedAt: $refTime,
                balance: 9.99,
            ),
            new GiftCard(
                guid: 1244987032468459526,
                productId: GiftCardProductIdEnum::PLUS,
                amount: 4.99,
                issuedByGuid: 1244987032468459522,
                issuedAt: $refTime,
                claimCode: 'change-me',
                expiresAt: strtotime('+1 year', $refTime),
                claimedByGuid: 1244987032468459524,
                claimedAt: $refTime,
                balance: 0.99,
            ),
        ]));
    }

    public function it_should_return_total_balance(PDOStatement $pdoStatementMock)
    {
        $this->mysqlReplicaMock->quote(Argument::type('string'))->shouldBeCalled();
        $this->mysqlReplicaMock->query(Argument::type('string'))->willReturn($pdoStatementMock);
        
        $pdoStatementMock->fetchAll(PDO::FETCH_ASSOC)
            ->willReturn(
                [
                    [
                       'balance' => 9.99,
                    ],
                ]
            );
        $this->getUserBalance(1244987032468459524)->shouldbe(9.99);
    }

    public function it_should_return_balance_per_product(PDOStatement $pdoStatementMock)
    {
        $this->mysqlReplicaMock->quote(Argument::type('string'))->shouldBeCalled();
        $this->mysqlReplicaMock->query(Argument::type('string'))->willReturn($pdoStatementMock);
        
        $pdoStatementMock->fetchAll(PDO::FETCH_ASSOC)
            ->willReturn(
                [
                    [
                       'product_id' => 0,
                       'balance' => 10.00,
                    ],
                    [
                        'product_id' => 1,
                        'balance' => 5.00,
                     ],
                     [
                        'product_id' => 2,
                        'balance' => 15.00,
                     ],
                     [
                        'product_id' => 3,
                        'balance' => 1.00,
                     ],
                ]
            );
    

        $this->getUserBalanceByProduct(1244987032468459524)->shouldBe([
            GiftCardProductIdEnum::BOOST->value => 10.00,
            GiftCardProductIdEnum::PLUS->value => 5.00,
            GiftCardProductIdEnum::PRO->value => 15.00,
            GiftCardProductIdEnum::SUPERMIND->value => 1.00,
        ]);
    }

    public function it_should_return_transactions(PDOStatement $pdoStatementMock)
    {
        $refTime = time();

        $this->mysqlReplicaMock->query(Argument::type('string'))->willReturn($pdoStatementMock);
        
        $pdoStatementMock->fetchAll(PDO::FETCH_ASSOC)
            ->willReturn(
                [
                    [
                        'payment_guid' => 1244987032468459524,
                        'gift_card_guid' => 1244987032468459522,
                        'amount' => 0.99,
                        'created_at' => date('c', $refTime),
                        'refunded_at' => null,
                        'gift_card_balance' => 9.01,
                    ],
                    [
                        'payment_guid' => 1244987032468459523,
                        'gift_card_guid' => 1244987032468459522,
                        'amount' => 10.00,
                        'created_at' => date('c', $refTime),
                        'refunded_at' => date('c', $refTime),
                        'gift_card_balance' => 10.00,
                    ]
                ]
            );
    

        $result = $this->getGiftCardTransactions(
            limit: 10,
        );
        $result->shouldYieldLike(new \ArrayIterator([
            new GiftCardTransaction(
                paymentGuid: 1244987032468459524,
                giftCardGuid: 1244987032468459522,
                amount: 0.99,
                createdAt: $refTime,
                refundedAt: null,
                // giftCardRunningBalance: 9.01,
            ),
            new GiftCardTransaction(
                paymentGuid: 1244987032468459523,
                giftCardGuid: 1244987032468459522,
                amount: 10.00,
                createdAt: $refTime,
                refundedAt: $refTime,
                // giftCardRunningBalance: 10.00,
            ),
        ]));
    }

    public function it_should_return_transactions_ledger(PDOStatement $pdoStatementMock)
    {
        $refTime = time();

        $this->mysqlReplicaMock->query(Argument::type('string'))->willReturn($pdoStatementMock);
        $this->mysqlReplicaMock->quote(Argument::that(function ($value) {
            return match ($value) {
                "123" => true,
                "0" => true,
                default => false,
            };
        }))->shouldBeCalled()
            ->willReturn('123');
 
        $pdoStatementMock->fetchAll(PDO::FETCH_ASSOC)
            ->willReturn(
                [
                    [
                        'payment_guid' => 1244987032468459524,
                        'gift_card_guid' => 1244987032468459522,
                        'amount' => 0.99,
                        'created_at' => date('c', $refTime),
                        'refunded_at' => null,
                        'gift_card_balance' => 9.01,
                        'boost_guid' => null
                    ],
                    [
                        'payment_guid' => 1244987032468459523,
                        'gift_card_guid' => 1244987032468459522,
                        'amount' => 10.00,
                        'created_at' => date('c', $refTime),
                        'refunded_at' => date('c', $refTime),
                        'gift_card_balance' => 10.00,
                        'boost_guid' => 1244987032468459524
                    ]
                ]
            );


        $result = $this->getGiftCardTransactionLedger(
            giftCardGuid: 123,
            limit: 10,
        );
        $result->shouldYieldLike(new \ArrayIterator([
            new GiftCardTransaction(
                paymentGuid: 1244987032468459524,
                giftCardGuid: 1244987032468459522,
                amount: 0.99,
                createdAt: $refTime,
                refundedAt: null,
                boostGuid: null
                // giftCardRunningBalance: 9.01,
            ),
            new GiftCardTransaction(
                paymentGuid: 1244987032468459523,
                giftCardGuid: 1244987032468459522,
                amount: 10.00,
                createdAt: $refTime,
                refundedAt: $refTime,
                boostGuid: 1244987032468459524,
                // giftCardRunningBalance: 10.00,
            ),
        ]));
    }
}
