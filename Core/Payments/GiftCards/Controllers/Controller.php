<?php
declare(strict_types=1);

namespace Minds\Core\Payments\GiftCards\Controllers;

use GraphQL\Error\UserError;
use Minds\Core\Di\Di;
use Minds\Core\GraphQL\Types\PageInfo;
use Minds\Core\Log\Logger;
use Minds\Core\Payments\GiftCards\Enums\GiftCardOrderingEnum;
use Minds\Core\Payments\GiftCards\Enums\GiftCardPaymentTypeEnum;
use Minds\Core\Payments\GiftCards\Enums\GiftCardProductIdEnum;
use Minds\Core\Payments\GiftCards\Enums\GiftCardStatusFilterEnum;
use Minds\Core\Payments\GiftCards\Exceptions\GiftCardAlreadyClaimedException;
use Minds\Core\Payments\GiftCards\Exceptions\GiftCardNotFoundException;
use Minds\Core\Payments\GiftCards\Exceptions\GiftCardPaymentFailedException;
use Minds\Core\Payments\GiftCards\Exceptions\InvalidGiftCardClaimCodeException;
use Minds\Core\Payments\GiftCards\Manager;
use Minds\Core\Payments\GiftCards\Models\GiftCard;
use Minds\Core\Payments\GiftCards\Models\GiftCardTransaction;
use Minds\Core\Payments\GiftCards\Types\GiftCardBalanceByProductId;
use Minds\Core\Payments\GiftCards\Types\GiftCardEdge;
use Minds\Core\Payments\GiftCards\Types\GiftCardsConnection;
use Minds\Core\Payments\GiftCards\Types\GiftCardTarget;
use Minds\Core\Payments\GiftCards\Types\GiftCardTransactionEdge;
use Minds\Core\Payments\GiftCards\Types\GiftCardTransactionsConnection;
use Minds\Core\Payments\Stripe\Exceptions\StripeTransferFailedException;
use Minds\Entities\User;
use Minds\Exceptions\ServerErrorException;
use Minds\Exceptions\UserErrorException;
use Stripe\Exception\ApiErrorException;
use TheCodingMachine\GraphQLite\Annotations\HideParameter;
use TheCodingMachine\GraphQLite\Annotations\InjectUser;
use TheCodingMachine\GraphQLite\Annotations\Logged;
use TheCodingMachine\GraphQLite\Annotations\Mutation;
use TheCodingMachine\GraphQLite\Annotations\Query;
use TheCodingMachine\GraphQLite\Exceptions\GraphQLException;

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
     * @param GiftCardTarget $targetInput
     * @param User $loggedInUser
     * @return GiftCard
     * @throws ApiErrorException
     * @throws GiftCardPaymentFailedException
     * @throws GraphQLException
     * @throws ServerErrorException
     * @throws StripeTransferFailedException
     * @throws UserErrorException
     */
    #[Mutation]
    #[Logged]
    public function createGiftCard(
        int $productIdEnum,
        float $amount,
        string $stripePaymentMethodId,
        ?int $expiresAt,
        GiftCardTarget $targetInput,
        #[InjectUser] User $loggedInUser // Do not add in docblock as it will break GraphQL
    ): GiftCard {
        $this->logger->info("Creating gift card", [
            'productIdEnum' => $productIdEnum,
            'amount' => $amount,
            'stripePaymentMethodId' => $stripePaymentMethodId,
            'expiresAt' => $expiresAt,
            'paymentTypeEnum' => GiftCardPaymentTypeEnum::CASH,
            'recipient' => $targetInput->targetUserGuid ?? $targetInput->targetUsername ?? $targetInput->targetEmail,
            'loggedInUser' => $loggedInUser->getGuid()
        ]);

        $targetInput = $this->manager->patchGiftCardTarget($targetInput);

        $giftCard = $this->manager->createGiftCard(
            issuer: $loggedInUser,
            productId: GiftCardProductIdEnum::tryFrom($productIdEnum) ?? throw new GraphQLException("An error occurred while validating the ", 400, null, "Validation", ['field' => 'productIdEnum']),
            amount: $amount,
            stripePaymentMethodId: $stripePaymentMethodId,
            expiresAt: $expiresAt
        );

        $this->manager->sendGiftCardToIssuer(
            giftCard: $giftCard,
            issuer: $loggedInUser
        );

        if ($targetInput->targetUserGuid) {
            $this->manager->sendGiftCardToRecipient(
                sender: $loggedInUser,
                recipient: $targetInput,
                giftCard: $giftCard
            );
        }

        return $giftCard;
    }

    /**
     * @param User $loggedInUser
     * @param string $claimCode
     * @return GiftCard
     * @throws GiftCardAlreadyClaimedException
     * @throws GiftCardNotFoundException
     * @throws InvalidGiftCardClaimCodeException
     * @throws ServerErrorException
     */
    #[Mutation]
    #[Logged]
    public function claimGiftCard(
        #[InjectUser] User $loggedInUser,
        string $claimCode
    ): GiftCard {
        return $this->manager->claimGiftCard($loggedInUser, $claimCode);
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
        ?GiftCardStatusFilterEnum $statusFilter = null,
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

        $limit = min($first ?? $last, 12); // MAX 12

        $edges = [];

        $giftCards = $this->manager->getGiftCards(
            claimedByUser: $loggedInUser,
            issuedByUser: $includeIssued ? $loggedInUser : null,
            productId: $productId,
            statusFilter: $statusFilter,
            limit: $limit,
            ordering: $ordering ?: GiftCardOrderingEnum::CREATED_DESC,
            loadAfter: $loadAfter,
            loadBefore: $loadBefore,
            hasMore: $hasMore,
        );
        
        foreach ($giftCards as $giftCard) {
            // Required for sub query of transactions
            $giftCard->setQueryRef($this, $loggedInUser);

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
    #[Logged]
    public function giftCard(
        string $guid,
        #[InjectUser] User $loggedInUser = null // Do not add in docblock as it will break GraphQL
    ): GiftCard {
        $giftCard = $this->manager->getGiftCard((int) $guid);

        if (
            (string) $giftCard->claimedByGuid !== $loggedInUser->getGuid() &&
            (string) $giftCard->issuedByGuid !== $loggedInUser->getGuid() &&
            !$loggedInUser->isAdmin()
        ) {
            throw new GraphQLException("You are not authorized to view this gift card.", 403);
        }

        // Required for sub query of transactions
        $giftCard->setQueryRef($this);
        return $giftCard;
    }

    /**
     * Returns an individual gift card by its claim code.
     * @param string $claimCode - claim code to get gift card by.
     * @return GiftCard requested gift card.
     */
    #[Query]
    #[Logged]
    public function giftCardByClaimCode(
        string $claimCode,
        #[InjectUser] User $loggedInUser = null // Do not add in docblock as it will break GraphQL
    ): GiftCard {
        $giftCard = $this->manager->getGiftCardByClaimCode($claimCode);
        // Required for sub query of transactions
        $giftCard->setQueryRef($this, $loggedInUser);
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
            $productIdEnum = GiftCardProductIdEnum::tryFrom($productId);

            if (!in_array($productIdEnum, GiftCardProductIdEnum::enabledProductIdEnums(), true)) {
                continue;
            }

            $giftCardBalance = new GiftCardBalanceByProductId($productIdEnum, $balance);
            $giftCardBalance->setQueryRef($this, $loggedInUser);
            $balances[] = $giftCardBalance;
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

    /**
     * Returns a list of gift card transactions for a ledger,
     * containing more information than just getting transactions,
     * including linked boost_guid's for Boost payments and injects
     * a transaction for the initial deposit.
     * @return GiftCardTransactionsConnection
     */
    #[Query]
    #[Logged]
    public function giftCardTransactionLedger(
        string $giftCardGuid,
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

        $transactions = $this->manager->getGiftCardTransactionLedger(
            user: $loggedInUser,
            giftCardGuid: (int) $giftCardGuid,
            limit: $limit,
            loadAfter: $loadAfter,
            loadBefore: $loadBefore,
            hasMore: $hasMore,
        );

        foreach ($transactions as $transaction) {
            $edges[] = new GiftCardTransactionEdge($transaction, $loadAfter);
        }

        // Inject the credit as there is no transaction for it.
        // If we introduce ordering we will need to consider whether to inject at start instead.
        if (!$hasMore) {
            $giftCard = $this->manager->getGiftCard((int) $giftCardGuid);
            $giftCardIssuer = Di::_()->get('EntitiesBuilder')->single($giftCard->issuedByGuid);

            $edges[] = new GiftCardTransactionEdge(
                new GiftCardTransaction(
                    paymentGuid: 0,
                    giftCardGuid: (int) $giftCardGuid,
                    amount: $giftCard->amount,
                    createdAt: $giftCard->claimedAt,
                    giftCardIssuerGuid: $giftCardIssuer ? $giftCardIssuer->getGuid() : null,
                    giftCardIssuerName: $giftCardIssuer ? $giftCardIssuer->getUsername() : null
                )
            );
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
