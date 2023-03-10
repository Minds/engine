<?php
declare(strict_types=1);

namespace Minds\Core\Boost\V3\Insights;

use Minds\Core\Boost\V3\Enums\BoostPaymentMethod;
use Minds\Core\Boost\V3\Enums\BoostTargetAudiences;
use Minds\Core\Boost\V3\Enums\BoostTargetLocation;
use Minds\Core\Di\Di;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\ServerRequest;

class Controller
{
    public function __construct(
        private ?Manager $manager = null
    ) {
        $this->manager ??= Di::_()->get(Manager::class);
    }

    /**
     * @param ServerRequest $request
     * @return JsonResponse
     */
    public function getEstimate(ServerRequest $request): JsonResponse
    {
        $dailyBid = (int) $request->getQueryParams()['daily_bid'];
        $duration = (int) $request->getQueryParams()['duration'];
        $audience = (int) $request->getQueryParams()['audience'];
        $paymentMethod = (int) $request->getQueryParams()['payment_method'];

        BoostTargetAudiences::validate($audience);

        $estimate = $this->manager->getEstimate(
            targetAudience: $audience,
            targetLocation: BoostTargetLocation::NEWSFEED,
            paymentMethod: $paymentMethod,
            dailyBid: $dailyBid,
            duration: $duration
        );

        return new JsonResponse($estimate);
    }
}
