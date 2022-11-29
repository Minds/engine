<?php
declare(strict_types=1);

namespace Minds\Core\Boost\V3;

use Minds\Api\Exportable;
use Minds\Core\Boost\V3\Enums\BoostStatus;
use Minds\Core\Boost\V3\Exceptions\BoostPaymentSetupFailedException;
use Minds\Core\Boost\V3\Exceptions\InvalidBoostPaymentMethodException;
use Minds\Core\Data\Locks\KeyNotSetupException;
use Minds\Core\Data\Locks\LockFailedException;
use Minds\Core\Di\Di;
use Minds\Core\Payments\Stripe\Exceptions\StripeTransferFailedException;
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
        $this->manager ??= Di::_()->get('Boost\V3\Manager');
    }

    public function getBoostFeed(ServerRequestInterface $request): JsonResponse
    {
        $loggedInUser = $request->getAttribute('_user');

        // TODO: get limit and offset from query params

        $boosts = $this->manager
            ->setUser($loggedInUser)
            ->getBoosts(
                targetStatus: BoostStatus::APPROVED,
                orderByRanking: true
            );
        return new JsonResponse(Exportable::_($boosts));
    }

    public function getBoosts(ServerRequestInterface $request): JsonResponse
    {
        $loggedInUser = $request->getAttribute('_user');

        ['status' => $targetStatus] = $request->getQueryParams();

        $boosts = $this->manager
            ->setUser($loggedInUser)
            ->getBoosts($targetStatus);
        return new JsonResponse(Exportable::_($boosts));
    }

    /**
     * @param ServerRequestInterface $request
     * @return JsonResponse
     * @throws BoostPaymentSetupFailedException
     * @throws InvalidBoostPaymentMethodException
     * @throws KeyNotSetupException
     * @throws LockFailedException
     * @throws ServerErrorException
     * @throws NotImplementedException
     */
    public function createBoost(ServerRequestInterface $request): JsonResponse
    {
        $loggedInUser = $request->getAttribute('_user');

        $data = $request->getParsedBody();

        $this->manager
            ->setUser($loggedInUser)
            ->createBoost($data);
        return new JsonResponse(
            data: "",
            status: 201
        );
    }

    /**
     * @param ServerRequestInterface $request
     * @return JsonResponse
     */
    public function getPendingBoosts(ServerRequestInterface $request): JsonResponse
    {
        $loggedInUser = $request->getAttribute('_user');

        $boosts = $this->manager
            ->setUser($loggedInUser)
            ->getBoosts(
                targetStatus: BoostStatus::PENDING,
                forApprovalQueue: true
            );
        return new JsonResponse(Exportable::_($boosts));
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
        ['guid' => $boostGuid] = $request->getAttribute("parameters")["guid"];

        $this->manager->approveBoost($boostGuid);

        return new JsonResponse("");
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
        ['guid' => $boostGuid] = $request->getAttribute("parameters")["guid"];

        $this->manager->rejectBoost($boostGuid);

        return new JsonResponse("");
    }
}
