<?php
declare(strict_types=1);

namespace Minds\Core\Boost\V3;

use Exception;
use Minds\Api\Exportable;
use Minds\Core\Boost\V3\Enums\BoostRejectionReason;
use Minds\Core\Boost\V3\Enums\BoostStatus;
use Minds\Core\Boost\V3\Enums\BoostTargetAudiences;
use Minds\Core\Boost\V3\Exceptions\BoostNotFoundException;
use Minds\Core\Boost\V3\Exceptions\BoostPaymentRefundFailedException;
use Minds\Core\Boost\V3\Exceptions\BoostPaymentSetupFailedException;
use Minds\Core\Boost\V3\Exceptions\InvalidBoostPaymentMethodException;
use Minds\Core\Boost\V3\Exceptions\InvalidRejectionReasonException;
use Minds\Core\Boost\V3\Validators\BoostCreateRequestValidator;
use Minds\Core\Data\Locks\KeyNotSetupException;
use Minds\Core\Data\Locks\LockFailedException;
use Minds\Core\Di\Di;
use Minds\Core\Payments\Stripe\Exceptions\StripeTransferFailedException;
use Minds\Core\Router\Exceptions\ForbiddenException;
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

        $params = $request->getQueryParams();

        if (!$this->manager->shouldShowBoosts($loggedInUser) && !($params['force_boost_enabled'] ?? false)) {
            return new JsonResponse([
                'status' => 'success',
                'boosts' => []
            ]);
        }

        $limit = $params['limit'] ?? 12;
        $offset = $params['offset'] ?? 0;
        $servedByGuid = $params['served_by_guid'] ?? null;
        $source = $params['source'] ?? null;

        $audience =
            $params['audience'] ??
            (
                $loggedInUser->getBoostRating() !== BoostTargetAudiences::CONTROVERSIAL ?
                    BoostTargetAudiences::SAFE :
                    BoostTargetAudiences::CONTROVERSIAL
            );

        $targetLocation = $params['location'] ?? null;

        $boosts = $this->manager
            ->setUser($loggedInUser)
            ->getBoostFeed(
                limit: (int) $limit,
                offset: (int) $offset,
                targetStatus: BoostStatus::APPROVED,
                orderByRanking: true,
                targetAudience: (int) $audience,
                targetLocation: (int) $targetLocation ?: null,
                servedByGuid: $servedByGuid,
                source: $source
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
     * Get a single boost by boostGuid
     * @param ServerRequestInterface $request
     * @return JsonResponse
     */
    public function getSingleBoost(ServerRequestInterface $request): JsonResponse
    {
        $boostGuid = $request->getAttribute("parameters")["boostGuid"];
        $boost = $this->manager->getBoostByGuid($boostGuid);

        /**
         * TODO: actually use the ACL
         */
        $loggedInUser = $request->getAttribute('_user');

        if ($boost->getOwnerGuid() !== $loggedInUser->getGuid()) {
            throw new ForbiddenException();
        }

        return new JsonResponse([
            'status' => 'success',
            'boost' => $boost->export()
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
        $remoteUserGuid = $queryParams['remote_user_guid'] ?? null;

        $boosts = $this->manager
            ->setUser($loggedInUser)
            ->getBoosts(
                limit: (int) $limit,
                offset: (int) $offset,
                targetStatus: (int) $targetStatus ?: null,
                forApprovalQueue: is_numeric($remoteUserGuid) ? false : true,
                targetAudience: (int) $targetAudience,
                targetLocation: (int) $targetLocation ?: null,
                paymentMethod: (int) $paymentMethod ?: null,
                targetUserGuid: $remoteUserGuid ?: null
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
        $loggedInUser = $request->getAttribute('_user');

        $this->manager->approveBoost(
            boostGuid: (string) $boostGuid,
            adminGuid: (string) $loggedInUser->getGuid()
        );

        return new JsonResponse([]);
    }

    /**
     * @param ServerRequestInterface $request
     * @return JsonResponse
     * @throws ApiErrorException
     * @throws BoostNotFoundException
     * @throws BoostPaymentRefundFailedException
     * @throws InvalidBoostPaymentMethodException
     * @throws InvalidRejectionReasonException
     * @throws KeyNotSetupException
     * @throws LockFailedException
     * @throws NotImplementedException
     * @throws ServerErrorException
     */
    public function rejectBoost(ServerRequestInterface $request): JsonResponse
    {
        $boostGuid = $request->getAttribute("parameters")["guid"];

        ['reason' => $reasonCode] = $request->getParsedBody();

        if (!BoostRejectionReason::isValid($reasonCode)) {
            throw new InvalidRejectionReasonException();
        }

        $this->manager->rejectBoost((string) $boostGuid, $reasonCode);

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
}
