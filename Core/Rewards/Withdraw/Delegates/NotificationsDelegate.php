<?php
/**
 * NotificationsDelegate
 * @author edgebal
 */

namespace Minds\Core\Rewards\Withdraw\Delegates;

use Exception;
use Minds\Core\Di\Di;
use Minds\Core\Events\EventsDispatcher;
use Minds\Core\Rewards\Withdraw\Request;
use Minds\Core\Util\BigNumber;
use Minds\Core\EventStreams\ActionEvent;
use Minds\Core\EventStreams\Topics\ActionEventsTopic;
use Minds\Core\EntitiesBuilder;
use Minds\Entities\User;
use Minds\Core;

class NotificationsDelegate
{
    /** @var EventsDispatcher */
    protected $dispatcher;

    /** @var EntitiesBuilder */
    protected $entitiesBuilder;

    /**
     * NotificationsDelegate constructor.
     * @param EventsDispatcher $dispatcher
     */

    public function __construct(
        $dispatcher = null,
        EntitiesBuilder $entitiesBuilder = null
    ) {
        $this->dispatcher = $dispatcher ?: Di::_()->get('EventsDispatcher');
        $this->entitiesBuilder = $entitiesBuilder ?? Di::_()->get('EntitiesBuilder');
    }

    /**
     * @param Request $request
     */
    public function onRequest(Request $request): void
    {
        $message = 'Your on-chain transfer request was submitted successfully.';

        $this->dispatcher->trigger('notification', 'all', [
            'to' => [ $request->getUserGuid() ],
            'from' => 100000000000000519,
            'notification_view' => 'custom_message',
            'params' => ['message' => $message],
            'message' => $message,
        ]);

        // TODO make this a toaster instead ojm
    }

    /**
     * @param Request $request
     */
    public function onConfirm(Request $request): void
    {
        // ojm ignore this
        $message = 'Your on-chain transfer request was confirmed by the blockchain and has been placed onto the review queue.';

        $this->dispatcher->trigger('notification', 'all', [
            'to' => [ $request->getUserGuid() ],
            'from' => 100000000000000519,
            'notification_view' => 'custom_message',
            'params' => ['message' => $message],
            'message' => $message,
        ]);

        $entity = $this->entitiesBuilder->single($request->getUserGuid());
    }

    /**
     * @param Request $request
     */
    public function onFail(Request $request): void
    {
        // ojm rare - only sent to hackers. so this would not be an action. leave it for now
        $message = 'Your on-chain transfer request failed. Please contact an administrator.';

        $this->dispatcher->trigger('notification', 'all', [
            'to' => [ $request->getUserGuid() ],
            'from' => 100000000000000519,
            'notification_view' => 'custom_message',
            'params' => ['message' => $message],
            'message' => $message,
        ]);
    }

    /**
     * @param Request $request
     * @throws Exception
     */
    public function onApprove(Request $request): void
    {
        // ojm yes
        $amount = BigNumber::fromPlain($request->getAmount(), 18)->toDouble();

        $message = sprintf(
            "Your on-chain transfer request has been approved and %g on-chain token(s) were issued.",
            $amount
        );

        $this->emitActionEvent(ActionEvent::ACTION_TOKEN_WITHDRAW_ACCEPTED, $request->getUserGuid(), $amount);

        $this->dispatcher->trigger('notification', 'all', [
            'to' => [ $request->getUserGuid() ],
            'from' => 100000000000000519,
            'notification_view' => 'custom_message',
            'params' => ['message' => $message],
            'message' => $message,
        ]);
    }

    /**
     * @param Request $request
     * @throws Exception
     */
    public function onReject(Request $request): void
    {
        $amount = BigNumber::fromPlain($request->getAmount(), 18)->toDouble();
        $message = sprintf(
            "Your on-chain transfer request has been rejected. Your %g off-chain token(s) were refunded.",
            $amount
        );

        $this->emitActionEvent(ActionEvent::ACTION_TOKEN_WITHDRAW_REJECTED, $request->getUserGuid(), $amount);

        $this->dispatcher->trigger('notification', 'all', [
            'to' => [ $request->getUserGuid() ],
            'from' => 100000000000000519,
            'notification_view' => 'custom_message',
            'params' => ['message' => $message],
            'message' => $message,
        ]);
    }

    /**
     * @param string $type
     * @param Entities\Activity $activity
     */
    public function emitActionEvent($action, $toGuid, $amount = null)
    {
        $entity = $this->entitiesBuilder->single($toGuid);
        $actor = $this->entitiesBuilder->single(Core\Session::getLoggedInUser());

        if (!$actor instanceof User || !$entity instanceof User) {
            return;
        }

        $actionEvent = new ActionEvent();

        $actionEvent
            ->setAction($action)
            ->setEntity($entity)
            ->setUser($actor)
            ->setActionData([
                'amount' => $amount,
            ]);

        $actionEventTopic = new ActionEventsTopic();
        $actionEventTopic->send($actionEvent);
    }
}
