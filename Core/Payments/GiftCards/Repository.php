<?php
namespace Minds\Core\Payments\GiftCards;

use Minds\Core\Data\MySQL\AbstractRepository;
use Minds\Core\Payments\GiftCards\Enums\GiftCardOrderingEnum;
use Minds\Core\Payments\GiftCards\Enums\GiftCardProductIdEnum;
use Minds\Core\Payments\GiftCards\Models\GiftCard;
use Minds\Core\Payments\GiftCards\Models\GiftCardTransaction;
use PDO;
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
     * @return iterable<GiftCard>
     */
    public function getGiftCards(
        ?int $claimedByGuid = null,
        ?int $issuedByGuid = null,
        ?GiftCardProductIdEnum $productId = null,
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

        if ($loadAfter) {
            $query->where('guid', Operator::LT, base64_decode($loadAfter, true));
        }

        if ($loadBefore) {
            $query->where('guid', Operator::GT, base64_decode($loadBefore, true));
        }

        $query->orderBy(match ($ordering) {
            GiftCardOrderingEnum::CREATED_ASC => 'issued_at asc',
            GiftCardOrderingEnum::CREATED_DESC => 'issued_at desc',
            GiftCardOrderingEnum::EXPIRING_ASC => 'expring_at asc',
            GiftCardOrderingEnum::EXPIRING_DESC => 'expring_at asc',
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
     * Returns gift card transactions
     * @return iterable<GiftCardTransaction>
     */
    public function getGiftCardTransactions(
        ?int $giftCardCalimedByUserGuid = null,
        ?int $giftCardGuid = null,
        int $limit = self::DEFAULT_LIMIT,
        string &$loadAfter = null,
        string &$loadBefore = null,
        ?bool &$hasMore = false
    ): iterable {
        $query = $this->mysqlClientReaderHandler
            ->select()
            ->columns([
                'payment_guid',
                'gift_card_guid',
                'minds_gift_card_transactions.amount',
                'created_at',
                // Vitess currently doesn't support OVER/PARTITION, commenting out
                // new RawExp('SUM(minds_gift_card_transactions.amount) OVER (PARTITION BY gift_card_guid ORDER BY payment_guid) gift_card_balance'),
            ])
            ->from('minds_gift_card_transactions')
            ->innerJoin('minds_gift_cards', 'minds_gift_cards.guid', Operator::EQ, 'minds_gift_card_transactions.gift_card_guid')
            ->orderBy('created_at desc')
            ->limit($limit + 1);

        if ($giftCardCalimedByUserGuid) {
            $query->where('minds_gift_cards.claimed_by_guid', Operator::EQ, $giftCardCalimedByUserGuid);
        }

        if ($giftCardGuid) {
            $query->where('gift_card_guid', Operator::EQ, $giftCardGuid);
        }

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
                // giftCardRunningBalance: $row['gift_card_balance'],
            );

            $loadAfter = base64_encode($row['payment_guid']);

            yield $transaction;
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
}
