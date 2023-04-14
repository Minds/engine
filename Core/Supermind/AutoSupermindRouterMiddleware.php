<?php
namespace Minds\Core\Supermind;

use Minds\Core\Data\Call;
use Minds\Core\Di\Di;
use Minds\Core\Email\V2\Campaigns\Recurring\SupermindBulkIncentive\SupermindBulkIncentive;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Guid;
use Minds\Core\Payments\Models\GetPaymentsOpts;
use Minds\Core\Router\PrePsr7\Middleware\RouterMiddleware;
use Minds\Core\Supermind\Models\SupermindRequest;
use Minds\Core\Payments\Stripe\Intents;
use Minds\Entities\User;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\ServerRequest;

/**
 * Note: this is NOT a PSR7 router middleware because api/v1/minds/config is a pre psr7 route.
 */
class AutoSupermindRouterMiddleware implements RouterMiddleware
{
    public function __construct(
        protected ?Manager $supermindManager = null,
        protected ?SupermindBulkIncentive $supermindBulkIncentiveEmailCampaign = null,
        protected ?EntitiesBuilder $entitiesBuilder = null,
        protected ?Call $db = null,
        protected ?Intents\ManagerV2 $paymentIntentsManager = null
    ) {
        // Do not construct here, avoid circular dependencies and initialising classes that may never be used
    }

    /**
     * @param ServerRequest $request
     * @param JsonResponse $response
     * @return bool|null
     * @throws Exception
     */
    public function onRequest(ServerRequest $request, JsonResponse &$response): ?bool
    {
        $queryParams = $request->getQueryParams();
        
        if (strpos($queryParams['utm_campaign'] ?? '', 'supermind_boffer_', 0) === false) {
            return true;
        }

        /**
         * Get the user who clicked on the email
         */
        $receiverGuid = $queryParams['__e_ct_guid'] ?? null;
        if (!$receiverGuid) {
            return true;
        }

        /**
         * Build the user entity
         */
        $receiverUser = $this->getEntitiesBuilder()->single($receiverGuid);
        if (!$receiverUser instanceof User) {
            return false; // invalid user
        }
       
        /**
         * Build the validation token
         */
        $validatorTokenProvided = $queryParams['validator'] ?? '';
        $activityGuid = $queryParams['activity_guid'] ?? '';
        $replyType = $queryParams['reply_type'] ?? SupermindRequestReplyType::TEXT;
        $paymentMethod = $queryParams['payment_method'] ?? SupermindRequestPaymentMethod::OFFCHAIN_TOKEN;
        $paymentAmount = $queryParams['payment_amount'] ?? 5;
        $validatorTokenExpected = $this
            ->getSupermindBulkIncentiveEmailCampaign()
            ->withActivityGuid($activityGuid)
            ->withReplyType((int) $replyType)
            ->withPaymentMethod($paymentMethod)
            ->withPaymentAmount($paymentAmount)
            ->setUser($receiverUser)->getValidatorToken();
        
        if ($validatorTokenProvided !== $validatorTokenExpected) {
            return true;
        }

        /**
         * If the reward has been used, there will be a record here
         */
        $row = $this->getDb()->getRow("analytics:rewarded:email:$validatorTokenExpected", [
            'offset' => $receiverGuid,
            'limit' => 1
        ]);

        if (isset($row[$receiverGuid])) {
            return true; // Don't proceed further as the user has claimed the supermind offer
        }

        // Save the ref so we don't allow to proceed past this point on next run
        $this->getDb()->insert("analytics:rewarded:email:$validatorTokenExpected", [ $receiverGuid => time() ]);
        
        /**
         * Build the activity
         */
        /** @var Activity */
        $activity = $this->getEntitiesBuilder()->single($activityGuid);
        /** @var User */
        $activityOwner =  $this->getEntitiesBuilder()->single($activity->getOwnerGuid());

        // Make a new supermind
        $supermindRequest = (new SupermindRequest())
            ->setGuid(Guid::build())
            ->setSenderGuid((string) $activityOwner->getGuid())
            ->setReceiverGuid((string) $receiverUser->getGuid())
            ->setReplyType($replyType)
            ->setTwitterRequired(false)
            ->setPaymentAmount($paymentAmount)
            ->setPaymentMethod($paymentMethod);

        $paymentMethodId = null;

        /**
         * If a CASH method, get the default card
         */
        if ($paymentMethod == SupermindRequestPaymentMethod::CASH) {
            $paymentIntents = $this->getPaymentIntentsManager()->getPaymentIntentsByUserGuid($activityOwner->getOwnerGuid(), new GetPaymentsOpts());
            if (!$paymentIntents) {
                return false;
            }
            $paymentMethodId = $paymentIntents[0]['id'];
        }

        $this->getSupermindManager()->setUser($activityOwner);
        $this->getSupermindManager()->addSupermindRequest($supermindRequest, $paymentMethodId);

        // Mutli phased commit, add the activity column
        $this->getSupermindManager()->completeSupermindRequestCreation($supermindRequest->getGuid(), $activity->getGuid());
        
        return true;
    }

    /**
     * @return EntitiesBuilder
     */
    protected function getEntitiesBuilder(): EntitiesBuilder
    {
        return $this->entitiesBuilder ??= Di::_()->get('EntitiesBuilder');
    }

    /**
     * @return Manager
     */
    protected function getSupermindManager(): Manager
    {
        return $this->supermindManager ??=  Di::_()->get("Supermind\Manager");
    }

    /**
     * @return SupermindBulkIncentive
     */
    protected function getSupermindBulkIncentiveEmailCampaign(): SupermindBulkIncentive
    {
        return $this->supermindBulkIncentiveEmailCampaign ??= new SupermindBulkIncentive();
    }

    /**
     * @return Call
     */
    protected function getDb(): Call
    {
        return $this->db ??= new Call('entities_by_time');
    }

    /**
     * @return Intents\ManagerV2
     */
    protected function getPaymentIntentsManager(): Intents\ManagerV2
    {
        return $this->paymentIntentsManager ??= new Intents\ManagerV2();
    }
}
