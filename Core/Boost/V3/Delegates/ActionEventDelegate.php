<?php

declare(strict_types=1);

namespace Minds\Core\Boost\V3\Delegates;

use Minds\Common\SystemUser;
use Minds\Core\Analytics\Metrics\Event;
use Minds\Core\Analytics\PostHog\PostHogService;
use Minds\Core\Boost\V3\Enums\BoostPaymentMethod;
use Minds\Core\Boost\V3\Enums\BoostTargetLocation;
use Minds\Core\Boost\V3\Enums\BoostTargetSuitability;
use Minds\Core\Boost\V3\Models\Boost;
use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Core\EventStreams\ActionEvent;
use Minds\Core\EventStreams\Topics\ActionEventsTopic;
use Minds\Core\Sessions\ActiveSession;
use Minds\Entities\User;

/**
 * Delegate to handle the dispatch of action events.
 */
class ActionEventDelegate
{
    public function __construct(
        private ?ActionEventsTopic $actionEventsTopic = null,
        private ?EntitiesBuilder $entitiesBuilder = null,
        private ?ActiveSession $activeSession = null,
        private ?PostHogService $postHogService = null,
        private ?Config $config = null,
    ) {
        $this->actionEventsTopic ??= Di::_()->get('EventStreams\Topics\ActionEventsTopic');
        $this->entitiesBuilder ??= Di::_()->get('EntitiesBuilder');
        $this->activeSession ??= Di::_()->get('Sessions\ActiveSession');
        $this->postHogService ??= Di::_()->get(PostHogService::class);
        $this->config ??= Di::_()->get(Config::class);
    }

    /**
     * On boost creation, dispatch an ActionEvent to subscribers.
     * @param Boost $boost - boost to dispatch event for.
     * @return void
     */
    public function onCreate(Boost $boost): void
    {
        $this->send($boost, ActionEvent::ACTION_BOOST_CREATED);
    }

    /**
     * On boost approval, dispatch an ActionEvent to subscribers.
     * @param Boost $boost - boost to dispatch event for.
     * @return void
     */
    public function onApprove(Boost $boost): void
    {
        $this->send($boost, ActionEvent::ACTION_BOOST_ACCEPTED);
    }

    /**
     * On boost rejection, dispatch an ActionEvent to subscribers.
     * @param Boost $boost - boost to dispatch event for.
     * @param int $reason - reason for rejection.
     * @return void
     */
    public function onReject(Boost $boost, int $reason): void
    {
        $this->send($boost, ActionEvent::ACTION_BOOST_REJECTED, [
            'boost_reject_reason' => $reason
        ]);
    }

    /**
     * On boost completion, dispatch an ActionEvent to subscribers.
     * @param Boost $boost - boost to dispatch event for.
     * @return void
     */
    public function onComplete(Boost $boost): void
    {
        $this->send($boost, ActionEvent::ACTION_BOOST_COMPLETED);
    }

    /**
     * On boost cancellation, dispatch an ActionEvent to subscribers.
     * @param Boost $boost - boost to dispatch event for.
     * @return void
     */
    public function onCancel(Boost $boost): void
    {
        $this->send($boost, ActionEvent::ACTION_BOOST_CANCELLED);
    }

    /**
     * Dispatch an ActionEvent to subscribers.
     * @param Boost $boost - boost to dispatch event for.
     * @param string $action - action to be dispatched for.
     * @param array $actionData - optional action data.
     * @return void
     */
    private function send(Boost $boost, string $action, array $actionData = []): void
    {
        $sender = $this->getSender($action);

        $actionEvent = new ActionEvent();
        $actionEvent->setAction($action)
            ->setEntity($boost)
            ->setUser($sender);

        if (count($actionData)) {
            $actionEvent->setActionData($actionData);
        }

        $this->actionEventsTopic->send($actionEvent);

        // To PostHog

        $boostMethod =  match($boost->getPaymentMethod()) {
            BoostPaymentMethod::CASH => 'cash',
            BoostPaymentMethod::OFFCHAIN_TOKENS => 'offchain_tokens',
            BoostPaymentMethod::ONCHAIN_TOKENS => 'onchain_tokens',
        };

        $set = [];
        $setOnce = [];

        if ($boost->getOwnerGuid() === $sender->getGuid()) {
            $setOnce["boost_first_{$boostMethod}_timestamp"] = date('c', $boost->getCreatedTimestamp());
            $set["boost_latest_{$boostMethod}_timestamp"] = date('c', $boost->getCreatedTimestamp());
        }

        $this->postHogService->capture(
            event: $action,
            user: $sender,
            properties: [
                'entity_guid' => $boost->getEntityGuid(),
                'boost_guid' => $boost->getGuid(),
                'boost_duration_days' => $boost->getDurationDays(),
                'boost_daily_bid' => $boost->getDailyBid(),
                'boost_method' => $boostMethod,
                'boost_payment_amount' => $boost->getPaymentAmount(),
                'boost_target_location' => match($boost->getTargetLocation()) {
                    BoostTargetLocation::NEWSFEED => 'newsfeed',
                    BoostTargetLocation::SIDEBAR => 'sidebar',
                },
                'boost_target_suitability' => match($boost->getTargetSuitability()) {
                    BoostTargetSuitability::SAFE => 'safe',
                    BoostTargetSuitability::CONTROVERSIAL => 'controversial',
                },
            ],
            set: $set,
            setOnce: $setOnce,
        );
    }

    /**
     * Gets sender for ActionEvent based on event type.
     * @param string $action - action.
     * @return User sender.
     */
    private function getSender(string $action): User
    {
        if ($action === ActionEvent::ACTION_BOOST_COMPLETED || php_sapi_name() === 'cli') {
            $systemUserGuid = $this->config->get('system_user_guid') ?: SystemUser::GUID;
            return $this->entitiesBuilder->single($systemUserGuid);
        }
        return $this->activeSession->getUser();
    }
}
