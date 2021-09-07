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
use Minds\Entities\User;
use Minds\Core;
use Minds\Core\Sessions\ActiveSession;

class NotificationsDelegate
{
    /** @var EventsDispatcher */
    protected $dispatcher;


    /** @var ActionEventsTopic */
    protected $actionEventsTopic;

    /** @var ActiveSession */
    protected $activeSession;

    /**
     * NotificationsDelegate constructor.
     * @param EventsDispatcher $dispatcher
     */

    public function __construct(
        $dispatcher = null,
        ActionEventsTopic $actionEventsTopic = null,
        ActiveSession $activeSession = null
    ) {
        $this->dispatcher = $dispatcher ?: Di::_()->get('EventsDispatcher');
        $this->actionEventsTopic = $actionEventsTopic ?? Di::_()->get('EventStreams\Topics\ActionEventsTopic');
        $this->activeSession = $activeSession ?? Di::_()->get('Sessions\ActiveSession');
    }

    /**
     * @param Request $request
     */
    public function onRequest(Request $request): void
    {
        // TODO make this a toaster instead
        $message = 'Your on-chain transfer request was submitted successfully.';

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
     */
    public function onConfirm(Request $request): void
    {
        $message = 'Your on-chain transfer request was confirmed by the blockchain and has been placed onto the review queue.';

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
     */
    public function onFail(Request $request): void
    {
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
        $amount = BigNumber::fromPlain($request->getAmount(), 18)->toDouble();

        $message = sprintf(
            "Your on-chain transfer request has been approved and %g on-chain token(s) were issued.",
            $amount
        );

        $this->emitActionEvent(ActionEvent::ACTION_TOKEN_WITHDRAW_ACCEPTED, $request);

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

        $this->emitActionEvent(ActionEvent::ACTION_TOKEN_WITHDRAW_REJECTED, $request);

        $this->dispatcher->trigger('notification', 'all', [
            'to' => [ $request->getUserGuid() ],
            'from' => 100000000000000519,
            'notification_view' => 'custom_message',
            'params' => ['message' => $message],
            'message' => $message,
        ]);
    }

    /**
     * @param string $action
     * @param Request $request
     */
    public function emitActionEvent(string $action, Request $request)
    {
        $actor = $this->activeSession->getUser();

        $actionEvent = new ActionEvent();

        $actionEvent
            ->setAction($action)
            ->setEntity($request)
            ->setUser($actor);

        $this->actionEventsTopic->send($actionEvent);
    }
}
