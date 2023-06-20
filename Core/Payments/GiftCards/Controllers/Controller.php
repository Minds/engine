<?php
declare(strict_types=1);

namespace Minds\Core\Payments\GiftCards\Controllers;

use GraphQL\Error\UserError;
use Minds\Core\GraphQL\Types\PageInfo;
use Minds\Core\Payments\GiftCards\Enums\GiftCardOrderingEnum;
use Minds\Core\Payments\GiftCards\Types\GiftCardsConnection;
use Minds\Core\Payments\GiftCards\Enums\GiftCardProductIdEnum;
use Minds\Core\Payments\GiftCards\Models\GiftCard;
use Minds\Core\Payments\GiftCards\Manager;
use Minds\Core\Payments\GiftCards\Types\GiftCardBalanceByProductId;
use Minds\Core\Payments\GiftCards\Types\GiftCardEdge;
use Minds\Core\Payments\GiftCards\Types\GiftCardTransactionEdge;
use Minds\Core\Payments\GiftCards\Types\GiftCardTransactionsConnection;
use Minds\Core\Session;
use Minds\Entities\ValidationError;
use Minds\Entities\ValidationErrorCollection;
use Minds\Exceptions\UserErrorException;
use TheCodingMachine\GraphQLite\Annotations\HideParameter;
use TheCodingMachine\GraphQLite\Annotations\Mutation;
use TheCodingMachine\GraphQLite\Annotations\Query;

class Controller
{
    public function __construct(protected Manager $manager)
    {
    }

    /**
     * @param int $productIdEnum
     * @param float $amount
     * @return GiftCard
     * @throws UserErrorException
     */
    #[Mutation]
    public function createGiftCard(
        int $productIdEnum,
        float $amount
    ): GiftCard {
        return new GiftCard(
            guid: 0,
            productId: GiftCardProductIdEnum::tryFrom($productIdEnum) ?? throw new UserErrorException("An error occurred while validating the ", 400, (new ValidationErrorCollection())->add(new ValidationError("productIdEnum", "The value provided is not a valid one"))),
            amount: $amount,
            issuedByGuid: 0,
            issuedAt: strtotime("now"),
            claimCode: "",
            expiresAt: strtotime("+1 year"),
            claimedByGuid: null,
            claimedAt: null
        );
    }

    /**
     * Returns a list of gift cards belonging to a user
     */
    #[Query]
    public function giftCards(
        bool $includeIssued = false,
        ?GiftCardOrderingEnum $ordering = null,
        ?GiftCardProductIdEnum $productId = null,
        ?int $first = null,
        ?string $after = null,
        ?int $last = null,
        ?string $before = null,
    ): GiftCardsConnection {
        if ($first && $last) {
            throw new UserError("first and last supplied, can only paginate in one direction");
        }

        if ($after && $before) {
            throw new UserError("after and before supplied, can only provide one cursor");
        }

        $loadAfter = $after;
        $loadBefore = $before;

        $limit = min($first ?: $last, 12); // MAX 12

        $edges = [];

        $giftCards = $this->manager->getGiftCards(
            issuedByUser: $includeIssued ? Session::getLoggedinUser() : null,
            claimedByUser: Session::getLoggedinUser(),
            productId: $productId,
            limit: $limit,
            ordering: $ordering ?: GiftCardOrderingEnum::CREATED_DESC,
            loadAfter: $loadAfter,
            loadBefore: $loadBefore,
            hasMore: $hasMore,
        );
    
        foreach ($giftCards as $giftCard) {
            // Required for sub query of transactions
            $giftCard->setQueryRef($this);

            $edges[] = new GiftCardEdge($giftCard, $loadAfter);
        }

        $pageInfo = new PageInfo(
            hasPreviousPage: ($after && $loadBefore) ? true : false, // Always will be newer data on latest or if we are paging forward
            hasNextPage: $hasMore,
            startCursor: ($after && $loadBefore) ? $loadBefore : null,
            endCursor: $hasMore ? $loadAfter : null,
        );

        $connection = new GiftCardsConnection();
        $connection->setEdges($edges);
        $connection->setPageInfo($pageInfo);

        return $connection;
    }

    /**
     * Returns an individual gift card
     */
    #[Query]
    public function giftCard(
        string $guid
    ): GiftCard {
        $giftCard = $this->manager->getGiftCard((int) $guid);
        // Required for sub query of transactions
        $giftCard->setQueryRef($this);
        return $giftCard;
    }

    /**
     * The available balance a user has
     */
    #[Query]
    public function giftCardsBalance(): float
    {
        return $this->manager->getUserBalance(Session::getLoggedinUser());
    }

    /**
     * The available balances of each gift card types
     * @return GiftCardBalanceByProductId[]
     */
    #[Query]
    public function giftCardsBalances(): array
    {
        $balances = [];
        foreach ($this->manager->getUserBalanceByProduct(Session::getLoggedinUser()) as $productId => $balance) {
            $balances[] = new GiftCardBalanceByProductId(GiftCardProductIdEnum::from($productId), $balance);
        }
        return $balances;
    }

    /**
     * Returns a list of gift card transactions
     */
    #[Query]
    public function giftCardTransactions(
        #[HideParameter]
        ?GiftCard $giftCard = null,
        ?int $first = null,
        ?string $after = null,
        ?int $last = null,
        ?string $before = null,
    ): GiftCardTransactionsConnection {
        if ($first && $last) {
            throw new UserError("first and last supplied, can only paginate in one direction");
        }

        if ($after && $before) {
            throw new UserError("after and before supplied, can only provide one cursor");
        }

        $loadAfter = $after;
        $loadBefore = $before;

        $limit = min($first ?: $last, 12); // MAX 12

        $edges = [];

        $transactions = $this->manager->getGiftCardTransactions(
            user: Session::getLoggedinUser(),
            giftCard: $giftCard,
            limit: $limit,
            loadAfter: $loadAfter,
            loadBefore: $loadBefore,
            hasMore: $hasMore,
        );

        foreach ($transactions as $transaction) {
            $edges[] = new GiftCardTransactionEdge($transaction, $loadAfter);
        }

        $pageInfo = new PageInfo(
            hasPreviousPage: ($after && $loadBefore) ? true : false, // Always will be newer data on latest or if we are paging forward
            hasNextPage: $hasMore,
            startCursor: ($after && $loadBefore) ? $loadBefore : null,
            endCursor: $hasMore ? $loadAfter : null,
        );

        $connection = new GiftCardTransactionsConnection();
        $connection->setEdges($edges);
        $connection->setPageInfo($pageInfo);

        return $connection;
    }
}
