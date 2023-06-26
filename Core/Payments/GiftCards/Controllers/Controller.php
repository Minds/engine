<?php
declare(strict_types=1);

namespace Minds\Core\Payments\GiftCards\Controllers;

use GraphQL\Error\UserError;
use Minds\Core\GraphQL\Types\PageInfo;
use Minds\Core\Log\Logger;
use Minds\Core\Payments\GiftCards\Enums\GiftCardOrderingEnum;
use Minds\Core\Payments\GiftCards\Enums\GiftCardPaymentTypeEnum;
use Minds\Core\Payments\GiftCards\Enums\GiftCardProductIdEnum;
use Minds\Core\Payments\GiftCards\Manager;
use Minds\Core\Payments\GiftCards\Models\GiftCard;
use Minds\Core\Payments\GiftCards\Types\GiftCardBalanceByProductId;
use Minds\Core\Payments\GiftCards\Types\GiftCardEdge;
use Minds\Core\Payments\GiftCards\Types\GiftCardsConnection;
use Minds\Core\Payments\GiftCards\Types\GiftCardTransactionEdge;
use Minds\Core\Payments\GiftCards\Types\GiftCardTransactionsConnection;
use Minds\Entities\User;
use Minds\Entities\ValidationError;
use Minds\Entities\ValidationErrorCollection;
use Minds\Exceptions\ServerErrorException;
use Minds\Exceptions\UserErrorException;
use Stripe\Exception\ApiErrorException;
use TheCodingMachine\GraphQLite\Annotations\HideParameter;
use TheCodingMachine\GraphQLite\Annotations\InjectUser;
use TheCodingMachine\GraphQLite\Annotations\Logged;
use TheCodingMachine\GraphQLite\Annotations\Mutation;
use TheCodingMachine\GraphQLite\Annotations\Query;

class Controller
{
    public function __construct(
        private readonly Manager $manager,
        private readonly Logger $logger
    ) {
    }

    /**
     * @param int $productIdEnum
     * @param float $amount
     * @param string $stripePaymentMethodId
     * @param int|null $expiresAt
     * @param int|null $giftCardPaymentTypeEnum
     * @param int|null $targetUserGuid
     * @param string|null $targetEmail
     * @return GiftCard
     * @throws UserErrorException
     * @throws ServerErrorException
     * @throws ApiErrorException
     */
    #[Mutation]
    #[Logged]
    public function createGiftCard(
        int $productIdEnum,
        float $amount,
        string $stripePaymentMethodId,
        ?int $expiresAt,
        ?int $giftCardPaymentTypeEnum,
        ?int $targetUserGuid,
        ?string $targetEmail,
        #[InjectUser] User $loggedInUser // Do not add in docblock as it will break GraphQL
    ): GiftCard {
        $this->logger->info("Creating gift card", [
            'productIdEnum' => $productIdEnum,
            'amount' => $amount,
            'stripePaymentMethodId' => $stripePaymentMethodId,
            'expiresAt' => $expiresAt,
            'paymentTypeEnum' => $giftCardPaymentTypeEnum,
            'targetUserGuid' => $targetUserGuid,
            'targetEmail' => $targetEmail,
            'loggedInUser' => $loggedInUser->getGuid()
        ]);
        return $this->manager->createGiftCard(
            issuer: $loggedInUser,
            productId: GiftCardProductIdEnum::tryFrom($productIdEnum) ?? throw new UserErrorException("An error occurred while validating the ", 400, (new ValidationErrorCollection())->add(new ValidationError("productIdEnum", "The value provided is not a valid one"))),
            amount: $amount,
            stripePaymentMethodId: $stripePaymentMethodId,
            expiresAt: $expiresAt,
            giftCardPaymentTypeEnum: GiftCardPaymentTypeEnum::tryFrom($giftCardPaymentTypeEnum) ?? GiftCardPaymentTypeEnum::CASH
        );
    }

    /**
     * Returns a list of gift cards belonging to a user
     */
    #[Query]
    #[Logged]
    public function giftCards(
        bool $includeIssued = false,
        ?GiftCardOrderingEnum $ordering = null,
        ?GiftCardProductIdEnum $productId = null,
        ?int $first = null,
        ?string $after = null,
        ?int $last = null,
        ?string $before = null,
        #[InjectUser] User $loggedInUser = null // Do not add in docblock as it will break GraphQL
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
            claimedByUser: $loggedInUser,
            issuedByUser: $includeIssued ? $loggedInUser : null,
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
            hasNextPage: $hasMore,
            hasPreviousPage: $after && $loadBefore, // Always will be newer data on latest or if we are paging forward
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
    #[Logged]
    public function giftCardsBalance(
        #[InjectUser] User $loggedInUser = null // Do not add in docblock as it will break GraphQL
    ): float {
        return $this->manager->getUserBalance($loggedInUser);
    }

    /**
     * The available balances of each gift card types
     * @return GiftCardBalanceByProductId[]
     */
    #[Query]
    #[Logged]
    public function giftCardsBalances(
        #[InjectUser] User $loggedInUser = null // Do not add in docblock as it will break GraphQL
    ): array {
        $balances = [];
        foreach ($this->manager->getUserBalanceByProduct($loggedInUser) as $productId => $balance) {
            $balances[] = new GiftCardBalanceByProductId(GiftCardProductIdEnum::from($productId), $balance);
        }
        return $balances;
    }

    /**
     * Returns a list of gift card transactions
     */
    #[Query]
    #[Logged]
    public function giftCardTransactions(
        #[HideParameter]
        ?GiftCard $giftCard = null,
        ?int $first = null,
        ?string $after = null,
        ?int $last = null,
        ?string $before = null,
        #[InjectUser] User $loggedInUser = null // Do not add in docblock as it will break GraphQL
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
            user: $loggedInUser,
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
            hasNextPage: $hasMore,
            hasPreviousPage: $after && $loadBefore, // Always will be newer data on latest or if we are paging forward
            startCursor: ($after && $loadBefore) ? $loadBefore : null,
            endCursor: $hasMore ? $loadAfter : null,
        );

        $connection = new GiftCardTransactionsConnection();
        $connection->setEdges($edges);
        $connection->setPageInfo($pageInfo);

        return $connection;
    }
}
