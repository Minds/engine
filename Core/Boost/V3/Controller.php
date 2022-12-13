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
            'location' => $targetLocation
        ] = $request->getQueryParams();

        if (!$audience && $loggedInUser->getBoostRating() !== BoostTargetAudiences::CONTROVERSIAL) {
            $audience = BoostTargetAudiences::SAFE;
        }

        $boosts = $this->manager
            ->setUser($loggedInUser)
            ->getBoosts(
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

        ['status' => $targetStatus] = $request->getQueryParams();

        $boosts = $this->manager
            ->setUser($loggedInUser)
            ->getBoosts(
                targetStatus: (int) $targetStatus,
                targetUserGuid: $loggedInUser->getGuid()
            );
        return new JsonResponse([
            'boosts' => Exportable::_($boosts),
            'has_more' => $boosts->getPagingToken(),
        ]);
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

        $status = null;
        if (array_key_exists('status', $request->getQueryParams())) {
            ['status' => $status] = $request->getQueryParams();
        }

        $boosts = $this->manager
            ->setUser($loggedInUser)
            ->getBoosts(
                targetStatus: $status,
                forApprovalQueue: true
            );
        return new JsonResponse([
            'boosts' => Exportable::_($boosts),
            'has_more' => $boosts->getPagingToken(),
        ]);
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
}
