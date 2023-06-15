<?php
namespace Minds\Core\Payments\GiftCards;

use Minds\Core\Data\MySQL\AbstractRepository;
use Minds\Core\Payments\GiftCards\Enums\GiftCardOrderingEnum;
use Minds\Core\Payments\GiftCards\Enums\GiftCardProductIdEnum;
use Minds\Core\Payments\GiftCards\Models\GiftCard;
use Minds\Core\Payments\GiftCards\Models\GiftCardTransaction;
use PDO;
use Selective\Database\Operator;

class Repository extends AbstractRepository
{
    /**
     * Saves a gift card to the database
     */
    public function addGiftCard(GiftCard $giftCard): bool
    {
        $statement = $this->mysqlClientWriterHandler
            ->insert()
            ->into('minds_gift_cards')
            ->set([
                'guid' => $giftCard->guid,
                'product_id' => (int) $giftCard->productId,
                'amount' => $giftCard->amount,
                'issued_by_guid' => $giftCard->issuedByGuid,
                'issued_at' => date('c', $giftCard->issuedAt),
                'claim_code' => $giftCard->claimCode,
                'expires_at' => date('c', $giftCard->expiresAt),
            ]);
        
        $statement->prepare();

        return $statement->execute();
    }

    /**
     * Returns a gift card from the database
     */
    public function getGiftCard(int $guid): GiftCard
    {
        $statement = $this->mysqlClientWriterHandler
            ->select()
            ->columns($this->buildGiftCardColumns())
            ->from('minds_gift_cards')
            ->where('guid', Operator::EQ, $guid);

        $pdoStatement = $statement->execute();

        $rows = $pdoStatement->fetchAll(PDO::FETCH_ASSOC);

        $row = $rows[0];

        return $this->buildGiftCardModel($row);
    }

    /**
     * Updates the claim_ columns on the database
     */
    public function updateGiftCardClaim(GiftCard $giftCard): bool
    {
        $statement = $this->mysqlClientWriterHandler
            ->update()
            ->table('minds_gift_cards')
            ->set([
                'claimed_by_guid' => $giftCard->claimedByGuid,
                'claimed_at' => date('c', $giftCard->claimedAt),
            ])
            ->where('guid', Operator::EQ, $giftCard->guid)
            ->where('claimed_by_guid', Operator::IS, null);

        return $statement->execute();
    }

    /**
     * Save a gift card transaction entry to the database
     */
    public function addGiftCardTransaction(GiftCardTransaction $giftCardTransaction): bool
    {
        $statement = $this->mysqlClientWriterHandler
            ->insert()
            ->into('minds_gift_card_transactions')
            ->set([
                'guid' => $giftCardTransaction->guid,
                'gift_card_guid' => $giftCardTransaction->giftCardGuid,
                'amount' => $giftCardTransaction->amount,
                'created_at' => date('c', $giftCardTransaction->createdAt),
            ]);
        return $statement->execute();
    }

    /**
     * @return iterable<GiftCard>
     */
    public function getGiftCards(
        ?int $claimedByGuid = null,
        ?GiftCardProductIdEnum $productId = null,
        int $limit = INF,
        GiftCardOrderingEnum $ordering = GiftCardOrderingEnum::CREATED_ASC,
        string &$loadAfter = null,
        string &$loadBefore = null,
        bool &$hasMore = false
    ): iterable {
        // TODO: pagination
    
        $statement = $this->mysqlClientReaderHandler
            ->select()
            ->columns($this->buildGiftCardColumns())
            ->from('minds_gift_cards')
            ->limit($limit);

        if ($claimedByGuid) {
            $statement->where('claimed_by_guid', Operator::EQ, $claimedByGuid);
        }

        if ($productId) {
            $statement->where('product_id', Operator::EQ, $productId);
        }

        $statement->orderBy(match ($ordering) {
            GiftCardOrderingEnum::CREATED_ASC => 'issued_at asc',
            GiftCardOrderingEnum::CREATED_DESC => 'issued_at desc',
            GiftCardOrderingEnum::EXPIRING_ASC => 'expring_at asc',
            GiftCardOrderingEnum::EXPIRING_DESC => 'expring_at asc',
        });

        $pdoStatement = $statement->execute();

        foreach ($pdoStatement->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $giftCard = $this->buildGiftCardModel($row);
            yield $giftCard;
        }
    }

    public function getGiftCardBalance(): float
    {
        return 0.00;
    }

    public function getUserBalance(): float
    {
        return 0.00;
    }

    /**
     * Builds the gift card from the database response
     */
    private function buildGiftCardModel(array $row): GiftCard
    {
        return new GiftCard(
            guid: $row['guid'],
            productId: $row['product_id'],
            amount: $row['amount'],
            issuedByGuid: $row['issued_by_guid'],
            issuedAt: strtotime($row['issued_at']),
            claimCode: $row['claim_code'],
            expiresAt: strtotime($row['expires_at']),
            claimedByGuid: $row['claimed_by_guid'] ?? null,
            claimedAt: isset($row['claimed_at']) ? strtotime($row['claimed_at']) : null,
        );
    }

    /**
     * @return string[]
     */
    private function buildGiftCardColumns(): array
    {
        return [
            'guid',
            'product_id',
            'amount',
            'issued_by_guid',
            'issued_at',
            'claim_code',
            'expires_at',
            'claimed_by_guid',
            'claimed_at',
        ];
    }
}
