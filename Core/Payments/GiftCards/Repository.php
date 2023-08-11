<?php
namespace Minds\Core\Payments\GiftCards;

use Exception;
use Minds\Core\Data\MySQL\AbstractRepository;
use Minds\Core\Payments\GiftCards\Enums\GiftCardOrderingEnum;
use Minds\Core\Payments\GiftCards\Enums\GiftCardProductIdEnum;
use Minds\Core\Payments\GiftCards\Enums\GiftCardStatusFilterEnum;
use Minds\Core\Payments\GiftCards\Exceptions\GiftCardNotFoundException;
use Minds\Core\Payments\GiftCards\Models\GiftCard;
use Minds\Core\Payments\GiftCards\Models\GiftCardTransaction;
use Minds\Exceptions\ServerErrorException;
use PDO;
use PDOException;
use Selective\Database\Operator;
use Selective\Database\RawExp;
use Selective\Database\SelectQuery;

class Repository extends AbstractRepository
{
    const DEFAULT_LIMIT = 999999;

    /**
     * Saves a gift card to the database
     */
    public function addGiftCard(GiftCard $giftCard): bool
    {
        $query = $this->mysqlClientWriterHandler
            ->insert()
            ->into('minds_gift_cards')
            ->set([
                'guid' => $giftCard->guid,
                'product_id' => $giftCard->productId->value,
                'amount' => $giftCard->amount,
                'issued_by_guid' => $giftCard->issuedByGuid,
                'issued_at' => date('c', $giftCard->issuedAt),
                'claim_code' => $giftCard->claimCode,
                'expires_at' => date('c', $giftCard->expiresAt),
            ]);

        return $query->execute();
    }

    /**
     * Updates the claim_ columns on the database
     */
    public function updateGiftCardClaim(GiftCard $giftCard): bool
    {
        $query = $this->mysqlClientWriterHandler
            ->update()
            ->table('minds_gift_cards')
            ->set([
                'claimed_by_guid' => $giftCard->claimedByGuid,
                'claimed_at' => date('c', $giftCard->claimedAt),
            ])
            ->where('guid', Operator::EQ, $giftCard->guid)
            ->where('claimed_by_guid', Operator::IS, null);

        return $query->execute();
    }

    /**
     * Save a gift card transaction entry to the database
     */
    public function addGiftCardTransaction(GiftCardTransaction $giftCardTransaction): bool
    {
        try {
            $this->logger->info('Adding gift card transaction', [
                'payment_guid' => $giftCardTransaction->paymentGuid,
                'gift_card_guid' => $giftCardTransaction->giftCardGuid,
                'amount' => $giftCardTransaction->amount,
                'created_at' => $giftCardTransaction->createdAt,
            ]);

            $query = $this->mysqlClientWriterHandler
                ->insert()
                ->into('minds_gift_card_transactions')
                ->set([
                    'payment_guid' => $giftCardTransaction->paymentGuid,
                    'gift_card_guid' => $giftCardTransaction->giftCardGuid,
                    'amount' => $giftCardTransaction->amount,
                    'created_at' => date('c', $giftCardTransaction->createdAt),
                ]);
            return $query->execute();
        } catch (PDOException $e) {
            error_log($e->getMessage());
            throw $e;
        }
    }

    /**
     * Returns a gift card from the database
     */
    public function getGiftCard(int $guid): GiftCard
    {
        $query = $this->buildGetGiftCardsQuery()
            ->where('guid', Operator::EQ, $guid);

        $pdoStatement = $query->execute();

        $rows = $pdoStatement->fetchAll(PDO::FETCH_ASSOC);

        $row = $rows[0];

        return $this->buildGiftCardModel($row);
    }

    /**
     * Returns a gift card from the database based on claim code
     *
     * @param string $claimCode
     * @return GiftCard
     * @throws GiftCardNotFoundException
     * @throws ServerErrorException
     * @throws Exception
     */
    public function getGiftCardByClaimCode(string $claimCode): GiftCard
    {
        $statement = $this->buildGetGiftCardsQuery()
            ->where('claim_code', Operator::EQ, new RawExp(':claim_code'))
            ->prepare();

        $this->mysqlHandler->bindValuesToPreparedStatement($statement, [
            'claim_code' => $claimCode,
        ]);

        try {
            $statement->execute();
            if ($statement->rowCount() === 0) {
                throw new GiftCardNotFoundException();
            }

            $row = $statement->fetch(PDO::FETCH_ASSOC);
            return $this->buildGiftCardModel($row);
        } catch (\PDOException $e) {
            error_log($e->getMessage());
            throw new \Exception('Error getting gift card by claim code', 0, $e);
        }
    }

    /**
     * @return iterable<GiftCard>
     */
    public function getGiftCards(
        ?int $claimedByGuid = null,
        ?int $issuedByGuid = null,
        ?GiftCardProductIdEnum $productId = null,
        ?GiftCardStatusFilterEnum $statusFilter = null,
        int $limit = self::DEFAULT_LIMIT,
        GiftCardOrderingEnum $ordering = GiftCardOrderingEnum::CREATED_ASC,
        ?string &$loadAfter = null,
        ?string &$loadBefore = null,
        ?bool &$hasMore = null
    ): iterable {
        $query = $this->buildGetGiftCardsQuery()
            ->limit($limit + 1);

        $params = [];

        if ($claimedByGuid && !$issuedByGuid) {
            $query->where('claimed_by_guid', Operator::EQ, $claimedByGuid);
        }

        // If querying both claimed by and issued, we will do an OR statement
        if ($issuedByGuid && !$claimedByGuid) {
            $query->where('issued_by_guid', Operator::EQ, $issuedByGuid);
        }

        if ($issuedByGuid && $claimedByGuid) {
            $query->whereRaw('(issued_by_guid = :issued_by_guid OR claimed_by_guid = :claimed_by_guid)');
            $params = [
                'issued_by_guid' => $issuedByGuid,
                'claimed_by_guid' => $claimedByGuid,
            ];
        }

        if ($productId) {
            $query->where('product_id', Operator::EQ, $productId->value);
        }

        if ($statusFilter) {
            $query->where(
                'expires_at',
                $statusFilter === GiftCardStatusFilterEnum::ACTIVE ?
                    Operator::GT :
                    Operator::LTE,
                date('c')
            );
        }

        if ($loadAfter) {
            $operator = Operator::GT;

            if ($ordering === GiftCardOrderingEnum::CREATED_DESC || $ordering === GiftCardOrderingEnum::EXPIRING_DESC) {
                $operator = Operator::LT;
            }

            $query->where('guid', $operator, base64_decode($loadAfter, true));
        }

        // Note, backward traversal may take you back to the first page rather than the expected page.
        // You should combine with previous after cursor.
        if ($loadBefore) {
            $operator = Operator::LT;

            if ($ordering === GiftCardOrderingEnum::CREATED_DESC || $ordering === GiftCardOrderingEnum::EXPIRING_DESC) {
                $operator = Operator::GT;
            }

            $query->where('guid', $operator, base64_decode($loadBefore, true));
        }

        $query->orderBy(match ($ordering) {
            GiftCardOrderingEnum::CREATED_ASC => 'issued_at asc',
            GiftCardOrderingEnum::CREATED_DESC => 'issued_at desc',
            GiftCardOrderingEnum::EXPIRING_ASC => 'expires_at asc',
            GiftCardOrderingEnum::EXPIRING_DESC => 'expires_at desc',
        });

        $pdoStatement = $query->prepare();

        $pdoStatement->execute($params);

        $hasMore = false;

        foreach ($pdoStatement->fetchAll(PDO::FETCH_ASSOC) as $i => $row) {
            if ($i === 0) {
                $loadBefore = base64_encode($row['guid']);
            }

            if ($i >= $limit) {
                $hasMore = true;
                break;
            }

            $giftCard = $this->buildGiftCardModel($row);

            $loadAfter = base64_encode($row['guid']);

            yield $giftCard;
        }
    }

    /**
     * Returns a users spendable balance across all gift cards
     */
    public function getUserBalance(int $guid): float
    {
        $query = $this->buildUserBalanceQuery(claimedByGuid: $guid);
        
        $pdoStatement = $query->execute();

        $rows = $pdoStatement->fetchAll(PDO::FETCH_ASSOC);

        return $rows[0] && $rows[0]['balance'] ? $rows[0]['balance']: 0.00;
    }

    /**
     * Returns a users spendable balance across all gift cards, by product ids
     * @return float[]
     */
    public function getUserBalanceByProduct(int $guid): array
    {
        $query = $this->buildUserBalanceQuery(claimedByGuid: $guid);

        $query->columns(['product_id']);
        $query->groupBy('product_id');

        $pdoStatement = $query->execute();

        $rows = $pdoStatement->fetchAll(PDO::FETCH_ASSOC);

        $productBalances = array_fill_keys(array_map(function ($productId) {
            return $productId->value;
        }, GiftCardProductIdEnum::cases()), 0);

        foreach ($rows as $row) {
            $productId = GiftCardProductIdEnum::tryFrom($row['product_id']);
            if (!$productId) {
                $this->logger->error("Invalid product_id {$row['product_id']} returned from database.");
                continue;
            }
            $productBalances[$productId->value] = (float) $row['balance'];
        }

        return $productBalances;
    }

    /**
     * @param int $guid
     * @param GiftCardProductIdEnum $productIdEnum
     * @return float
     * @throws GiftCardNotFoundException
     * @throws ServerErrorException
     */
    public function getUserBalanceForProduct(int $guid, GiftCardProductIdEnum $productIdEnum): float
    {
        $query = $this->buildUserBalanceQuery(claimedByGuid: $guid);
        $query->where('product_id', Operator::EQ, new RawExp(':product_id'));

        $query->groupBy('claimed_by_guid');
        $statement = $query->prepare();

        $this->mysqlHandler->bindValuesToPreparedStatement($statement, [
            'product_id' => $productIdEnum->value,
        ]);

        $statement->execute();
        return $statement->fetch(PDO::FETCH_ASSOC)['balance'] ?? throw new GiftCardNotFoundException();
    }

    /**
     * Returns gift card transactions.
     * @param ?int $giftCardClaimedByUserGuid - user who claimed the gift card.
     * @param ?int $giftCardGuid - guid of the gift card to get transactions for.
     * @param int $limit - limit of transactions to return.
     * @param string &$loadAfter - cursor to load after.
     * @param string &$loadBefore - cursor to load before.
     * @param ?bool &$hasMore - whether there are more transactions to load.
     * @return iterable<GiftCardTransaction>
     */
    public function getGiftCardTransactions(
        ?int $giftCardClaimedByUserGuid = null,
        ?int $giftCardGuid = null,
        int $limit = self::DEFAULT_LIMIT,
        string &$loadAfter = null,
        string &$loadBefore = null,
        ?bool &$hasMore = false
    ): iterable {
        $query = $this->buildBaseGiftCardsTransactionsQuery(
            giftCardClaimedByUserGuid: $giftCardClaimedByUserGuid,
            giftCardGuid: $giftCardGuid
        );

        $query->columns(['payment_guid']);

        if ($loadAfter) {
            $query->where('payment_guid', Operator::LT, base64_decode($loadAfter, true));
        }

        if ($loadBefore) {
            $query->where('payment_guid', Operator::GT, base64_decode($loadBefore, true));
        }

        $pdoStatement = $query->execute();

        $rows = $pdoStatement->fetchAll(PDO::FETCH_ASSOC);

        $hasMore = false;

        foreach ($rows as $i => $row) {
            // Pagination will request $limit+1, we stop yielding if we've returned the requested limit already
            if ($i >= $limit) {
                $hasMore = true;
                break;
            }
            
            if ($i === 0) {
                $loadBefore = base64_encode($row['payment_guid']);
            }

            $transaction = new GiftCardTransaction(
                paymentGuid: $row['payment_guid'],
                giftCardGuid: $row['gift_card_guid'],
                amount: $row['amount'],
                createdAt: strtotime($row['created_at']),
                refundedAt: $row['refunded_at'] ? strtotime($row['refunded_at']) : null,
                // giftCardRunningBalance: $row['gift_card_balance'],
            );

            $loadAfter = base64_encode($row['payment_guid']);

            yield $transaction;
        }
    }

    /**
     * Returns gift card transactions with additional joins on other tables such as Boost to provide
     * more information for ledgers to display transactions to users.
     * @param ?int $giftCardGuid - guid of the gift card to get transactions for.
     * @param ?int $giftCardClaimedByUserGuid - user who claimed the gift card.
     * @param int $limit - limit of transactions to return.
     * @param string &$loadAfter - cursor to load after.
     * @param string &$loadBefore - cursor to load before.
     * @param ?bool &$hasMore - whether there are more transactions to load.
     * @return iterable<GiftCardTransaction>
     */
    public function getGiftCardTransactionLedger(
        int $giftCardGuid = null,
        ?int $giftCardClaimedByUserGuid = null,
        int $limit = self::DEFAULT_LIMIT,
        string &$loadAfter = null,
        string &$loadBefore = null,
        ?bool &$hasMore = false
    ): iterable {
        $query = $this->buildBaseGiftCardsTransactionsQuery(
            giftCardClaimedByUserGuid: $giftCardClaimedByUserGuid,
            giftCardGuid: $giftCardGuid
        );

        $query->columns([
            'payment_guid' => 'minds_gift_card_transactions.payment_guid',
            new RawExp('boosts.guid AS boost_guid'),
        ])
            ->leftJoin('boosts', 'boosts.payment_guid', Operator::EQ, 'minds_gift_card_transactions.payment_guid')
            ->where('minds_gift_card_transactions.amount', Operator::LT, 0)
            ->limit($limit + 1);

        
        if ($loadAfter) {
            $query->where('minds_gift_card_transactions.payment_guid', Operator::LT, base64_decode($loadAfter, true));
        }

        if ($loadBefore) {
            $query->where('minds_gift_card_transactions.payment_guid', Operator::GT, base64_decode($loadBefore, true));
        }

        $pdoStatement = $query->execute();

        $rows = $pdoStatement->fetchAll(PDO::FETCH_ASSOC);
        
        $hasMore = false;

        foreach ($rows as $i => $row) {
            // Pagination will request $limit+1, we stop yielding if we've returned the requested limit already
            if ($i >= $limit) {
                $hasMore = true;
                break;
            }
            
            if ($i === 0) {
                $loadBefore = base64_encode($row['payment_guid']);
            }

            $transaction = new GiftCardTransaction(
                paymentGuid: $row['payment_guid'],
                giftCardGuid: $row['gift_card_guid'],
                amount: $row['amount'],
                createdAt: strtotime($row['created_at']),
                refundedAt: $row['refunded_at'] ? strtotime($row['refunded_at']) : null,
                boostGuid: $row['boost_guid'],
            );

            $loadAfter = base64_encode($row['payment_guid']);

            yield $transaction;
        }
    }

    /**
     * Build base query to get gift card transactions.
     * @param ?int $giftCardClaimedByUserGuid - user who claimed the gift card.
     * @param ?int $giftCardGuid - guid of the gift card to get transactions for.
     * @return SelectQuery - base query.
     */
    private function buildBaseGiftCardsTransactionsQuery(
        ?int $giftCardClaimedByUserGuid = null,
        ?int $giftCardGuid = null
    ): SelectQuery {
        $query = $this->mysqlClientReaderHandler
            ->select()
            ->columns([
                'gift_card_guid',
                'minds_gift_card_transactions.amount',
                'created_at',
                'refunded_at'
            ])
            ->from('minds_gift_card_transactions')
            ->innerJoin('minds_gift_cards', 'minds_gift_cards.guid', Operator::EQ, 'minds_gift_card_transactions.gift_card_guid')
            ->orderBy('created_at desc');

        if ($giftCardClaimedByUserGuid) {
            $query->where('minds_gift_cards.claimed_by_guid', Operator::EQ, $giftCardClaimedByUserGuid);
        }

        if ($giftCardGuid) {
            $query->where('gift_card_guid', Operator::EQ, $giftCardGuid);
        }

        return $query;
    }

    /**
     * @param int $paymentGuid
     * @return iterable<GiftCardTransaction>
     * @throws ServerErrorException
     */
    public function getGiftCardTransactionsFromPaymentGuid(
        int $paymentGuid
    ): iterable {
        $statement = $this->mysqlClientReaderHandler
            ->select()
            ->columns([
                'payment_guid',
                'gift_card_guid',
                'minds_gift_card_transactions.amount',
                'created_at',
                'refunded_at',
            ])
            ->from('minds_gift_card_transactions')
            ->innerJoin('minds_gift_cards', 'minds_gift_cards.guid', Operator::EQ, 'minds_gift_card_transactions.gift_card_guid')
            ->where('payment_guid', Operator::EQ, new RawExp(':payment_guid'))
            ->orderBy('created_at desc')
            ->prepare();

        $this->mysqlHandler->bindValuesToPreparedStatement($statement, [
            'payment_guid' => $paymentGuid,
        ]);

        $statement->execute();

        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            yield new GiftCardTransaction(
                paymentGuid: $row['payment_guid'],
                giftCardGuid: $row['gift_card_guid'],
                amount: $row['amount'],
                createdAt: strtotime($row['created_at']),
                refundedAt: $row['refunded_at'] ? strtotime($row['refunded_at']) : null,
            );
        }
    }

    /**
     * Builds the base query for
     */
    private function buildGetGiftCardsQuery(): SelectQuery
    {
        return $this->mysqlClientReaderHandler
            ->select()
            ->columns([
                'guid',
                'product_id',
                'minds_gift_cards.amount',
                'issued_by_guid',
                'issued_at',
                'claim_code',
                'expires_at',
                'claimed_by_guid',
                'claimed_at',
                'balance' => new RawExp('SUM(minds_gift_card_transactions.amount)')
            ])
            ->from('minds_gift_cards')
            ->innerJoin('minds_gift_card_transactions', 'minds_gift_cards.guid', Operator::EQ, 'minds_gift_card_transactions.gift_card_guid')
            ->where('minds_gift_card_transactions.refunded_at', Operator::IS, null)
            ->groupBy('minds_gift_cards.guid');
    }

    /**
     * Builds the query that is used by multiple other functions
     */
    private function buildUserBalanceQuery(int $claimedByGuid): SelectQuery
    {
        $query = $this->mysqlClientReaderHandler
            ->select()
            ->columns([
                'balance' => new RawExp('SUM(minds_gift_card_transactions.amount)'),
            ])
            ->from('minds_gift_cards')
            ->innerJoin('minds_gift_card_transactions', 'minds_gift_cards.guid', Operator::EQ, 'minds_gift_card_transactions.gift_card_guid');

        $query->where('minds_gift_card_transactions.refunded_at', Operator::IS, null);

        $query->where('claimed_by_guid', Operator::EQ, $claimedByGuid);

        // Only get balances of unexpired cards
        $query->where('expires_at', Operator::GT, date('c', time()));

        return $query;
    }

    /**
     * Builds the gift card from the database response
     */
    private function buildGiftCardModel(array $row): GiftCard
    {
        return new GiftCard(
            guid: $row['guid'],
            productId: GiftCardProductIdEnum::from($row['product_id']),
            amount: $row['amount'],
            issuedByGuid: $row['issued_by_guid'],
            issuedAt: strtotime($row['issued_at']),
            claimCode: $row['claim_code'],
            expiresAt: strtotime($row['expires_at']),
            claimedByGuid: $row['claimed_by_guid'] ?? null,
            claimedAt: isset($row['claimed_at']) ? strtotime($row['claimed_at']) : null,
            balance: $row['balance'],
        );
    }

    /**
     * Marks a gift card's transaction as refunded
     * @param int $paymentGuid
     * @param int $giftCardGuid
     * @param int $refundedAt
     * @return void
     * @throws ServerErrorException
     */
    public function markTransactionAsRefunded(
        int $paymentGuid,
        int $giftCardGuid,
        int $refundedAt
    ): void {
        $this->logger->info('Marking gift card transaction as refunded', [
            'payment_guid' => $paymentGuid,
            'gift_card_guid' => $giftCardGuid,
            'refunded_at' => date('c', $refundedAt),
        ]);

        $statement = $this->mysqlClientWriterHandler->update()
            ->table('minds_gift_card_transactions')
            ->set([
                'refunded_at' => new RawExp(':refunded_at'),
            ])
            ->where('payment_guid', Operator::EQ, new RawExp(':payment_guid'))
            ->where('gift_card_guid', Operator::EQ, new RawExp(':gift_card_guid'))
            ->prepare();
        $values = [
            'refunded_at' => date('c', $refundedAt),
            'payment_guid' => $paymentGuid,
            'gift_card_guid' => $giftCardGuid,
        ];

        $this->mysqlHandler->bindValuesToPreparedStatement($statement, $values);
        $statement->execute();
    }
}
