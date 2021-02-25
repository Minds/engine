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

class NotificationsDelegate
{
    /** @var EventsDispatcher */
    protected $dispatcher;

    /**
     * NotificationsDelegate constructor.
     * @param EventsDispatcher $dispatcher
     */
    public function __construct(
        $dispatcher = null
    ) {
        $this->dispatcher = $dispatcher ?: Di::_()->get('EventsDispatcher');
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
        $message = sprintf(
            "Your on-chain transfer request has been approved and %g on-chain token(s) were issued.",
            BigNumber::fromPlain($request->getAmount(), 18)->toDouble()
        );

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
        $message = sprintf(
            "Your on-chain transfer request has been rejected. Your %g off-chain token(s) were refunded.",
            BigNumber::fromPlain($request->getAmount(), 18)->toDouble()
        );

        $this->dispatcher->trigger('notification', 'all', [
            'to' => [ $request->getUserGuid() ],
            'from' => 100000000000000519,
            'notification_view' => 'custom_message',
            'params' => ['message' => $message],
            'message' => $message,
        ]);
    }
}
