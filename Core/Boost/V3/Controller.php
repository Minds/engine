<?php
declare(strict_types=1);

namespace Minds\Core\Boost\V3;

use Exception;
use Minds\Api\Exportable;
use Minds\Core\Boost\V3\Enums\BoostStatus;
use Minds\Core\Boost\V3\Enums\BoostTargetAudiences;
use Minds\Core\Boost\V3\Exceptions\BoostPaymentSetupFailedException;
use Minds\Core\Boost\V3\Exceptions\InvalidBoostPaymentMethodException;
use Minds\Core\Boost\V3\Validators\BoostCreateRequestValidator;
use Minds\Core\Data\Locks\KeyNotSetupException;
use Minds\Core\Data\Locks\LockFailedException;
use Minds\Core\Di\Di;
use Minds\Core\Payments\Stripe\Exceptions\StripeTransferFailedException;
use Minds\Entities\User;
use Minds\Exceptions\ServerErrorException;
use Minds\Exceptions\UserErrorException;
use NotImplementedException;
use Psr\Http\Message\ServerRequestInterface;
use Stripe\Exception\ApiErrorException;
use Zend\Diactoros\Response\JsonResponse;

class Controller
{
    public function __construct(
        private ?Manager $manager = null
    ) {
        $this->manager ??= Di::_()->get(Manager::class);
    }

    public function getBoostFeed(ServerRequestInterface $request): JsonResponse
    {
        $loggedInUser = $request->getAttribute('_user');

        [
            'limit' => $limit,
            'offset' => $offset,
            'audience' => $audience,
            'location' => $targetLocation,
            'show_boosts_after_x' => $showBoostsAfterX
        ] = $request->getQueryParams();

        if (!$audience && $loggedInUser->getBoostRating() !== BoostTargetAudiences::CONTROVERSIAL) {
            $audience = BoostTargetAudiences::SAFE;
        }

        if (!$this->shouldShowBoosts($loggedInUser, (int) $showBoostsAfterX)) {
            return new JsonResponse([
                'status' => 'success',
                'boosts' => []
            ]);
        }

        $boosts = $this->manager
            ->setUser($loggedInUser)
            ->getBoostFeed(
                limit: (int) $limit,
                offset: (int) $offset,
                targetStatus: BoostStatus::APPROVED,
                orderByRanking: true,
                targetAudience: (int) $audience,
                targetLocation: (int) $targetLocation
            );

        return new JsonResponse([
            'status' => 'success',
            'boosts' => Exportable::_($boosts),
            'has_more' => $boosts->getPagingToken(),
        ]);
    }

    public function getOwnBoosts(ServerRequestInterface $request): JsonResponse
    {
        $loggedInUser = $request->getAttribute('_user');

        $queryParams = $request->getQueryParams();

        $limit = $queryParams['limit'] ?? 12;
        $offset = $queryParams['offset'] ?? 0;
        $targetLocation = $queryParams['location'] ?? null;
        $targetStatus = $queryParams['status'] ?? null;

        $boosts = $this->manager
            ->setUser($loggedInUser)
            ->getBoosts(
                limit: (int) $limit,
                offset: (int) $offset,
                targetStatus: (int) $targetStatus,
                targetUserGuid: $loggedInUser->getGuid(),
                targetLocation: (int) $targetLocation ?: null
            );
        return new JsonResponse([
            'boosts' => Exportable::_($boosts),
            'has_more' => $boosts->getPagingToken(),
        ]);
    }

    /**
     * Prepares an onchain boost by returning a guid
     * @param ServerRequestInterface $request
     * @return JsonResponse
     */
    public function prepareOnchainBoost(ServerRequestInterface $request): JsonResponse
    {
        $entityGuid = $request->getAttribute("parameters")["entityGuid"];
        return new JsonResponse($this->manager->prepareOnchainBoost($entityGuid));
    }

    /**
     * @param ServerRequestInterface $request
     * @return JsonResponse
     * @throws BoostPaymentSetupFailedException
     * @throws InvalidBoostPaymentMethodException
     * @throws KeyNotSetupException
     * @throws LockFailedException
     * @throws NotImplementedException
     * @throws ServerErrorException
     * @throws UserErrorException
     * @throws Exception
     */
    public function createBoost(ServerRequestInterface $request): JsonResponse
    {
        $loggedInUser = $request->getAttribute('_user');

        $data = $request->getParsedBody();

        // validate data received
        $validator = new BoostCreateRequestValidator();

        if (!$validator->validate($data)) {
            throw new UserErrorException(
                message: "An error occurred when validating the request data",
                code: 400,
                errors: $validator->getErrors()
            );
        }

        $this->manager
            ->setUser($loggedInUser)
            ->createBoost($data);

        return new JsonResponse(
            data: [],
            status: 201
        );
    }

    /**
     * @param ServerRequestInterface $request
     * @return JsonResponse
     */
    public function getBoostsForAdmin(ServerRequestInterface $request): JsonResponse
    {
        $loggedInUser = $request->getAttribute('_user');

        $queryParams = $request->getQueryParams();

        $limit = $queryParams['limit'] ?? 12;
        $offset = $queryParams['offset'] ?? 0;
        $targetLocation = $queryParams['location'] ?? null;
        $targetStatus = $queryParams['status'] ?? null;
        $targetAudience = $queryParams['audience'] ?? null;
        $paymentMethod = $queryParams['payment_method'] ?? null;

        $boosts = $this->manager
            ->setUser($loggedInUser)
            ->getBoosts(
                limit: (int) $limit,
                offset: (int) $offset,
                targetStatus: (int) $targetStatus ?: null,
                forApprovalQueue: true,
                targetAudience: (int) $targetAudience,
                targetLocation: (int) $targetLocation ?: null,
                paymentMethod: (int) $paymentMethod ?: null
            );
        return new JsonResponse([
            'boosts' => Exportable::_($boosts),
            'has_more' => $boosts->getPagingToken(),
        ]);
    }

    /**
     * Get admin stats.
     * @param ServerRequestInterface $request
     * @return JsonResponse
     */
    public function getAdminStats(ServerRequestInterface $request): JsonResponse
    {
        return new JsonResponse($this->manager->getAdminStats());
    }

    /**
     * @param ServerRequestInterface $request
     * @return JsonResponse
     * @throws Exceptions\BoostNotFoundException
     * @throws InvalidBoostPaymentMethodException
     * @throws NotImplementedException
     * @throws ServerErrorException
     * @throws StripeTransferFailedException
     * @throws UserErrorException
     * @throws ApiErrorException
     */
    public function approveBoost(ServerRequestInterface $request): JsonResponse
    {
        $boostGuid = $request->getAttribute("parameters")["guid"];

        $this->manager->approveBoost((string) $boostGuid);

        return new JsonResponse([]);
    }

    /**
     * @param ServerRequestInterface $request
     * @return JsonResponse
     * @throws ApiErrorException
     * @throws Exceptions\BoostNotFoundException
     * @throws Exceptions\BoostPaymentCaptureFailedException
     * @throws InvalidBoostPaymentMethodException
     * @throws KeyNotSetupException
     * @throws LockFailedException
     * @throws NotImplementedException
     * @throws ServerErrorException
     */
    public function rejectBoost(ServerRequestInterface $request): JsonResponse
    {
        $boostGuid = $request->getAttribute("parameters")["guid"];

        $this->manager->rejectBoost((string) $boostGuid);

        return new JsonResponse([]);
    }

    /**
     * @param ServerRequestInterface $request
     * @return JsonResponse
     * @throws ApiErrorException
     * @throws Exceptions\BoostNotFoundException
     * @throws Exceptions\BoostPaymentCaptureFailedException
     * @throws InvalidBoostPaymentMethodException
     * @throws KeyNotSetupException
     * @throws LockFailedException
     * @throws NotImplementedException
     * @throws ServerErrorException
     */
    public function cancelBoost(ServerRequestInterface $request): JsonResponse
    {
        $loggedInUser = $request->getAttribute('_user');
        $boostGuid = $request->getAttribute("parameters")["guid"];

        $this->manager
            ->setUser($loggedInUser)
            ->cancelBoost((string) $boostGuid);

        return new JsonResponse([]);
    }

    /**
     * Whether boosts should be shown for a user
     * @param User $user - user to show.
     * @param integer|null $showBoostsAfterX - how long after registration till users should see boosts.
     * @return boolean true if boosts should be shown.
     */
    private function shouldShowBoosts(User $user, ?int $showBoostsAfterX = null): bool
    {
        $showBoostsAfterX = filter_var($showBoostsAfterX, FILTER_VALIDATE_INT, [
            'options' => [
                'default' => 3600, // 1 day
                'min_range' => 0,
                'max_range' => 604800 // 1 week
            ]
        ]);
        return (time() - $user->getTimeCreated()) > $showBoostsAfterX;
    }
}
