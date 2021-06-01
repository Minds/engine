<?php
namespace Minds\Core\Rewards;

use Brick\Math\BigDecimal;
use Minds\Common\SystemUser;
use Minds\Core\Blockchain\TokenPrices;
use Minds\Core\Di\Di;
use Minds\Core\Events\EventsDispatcher;
use Minds\Core\Log\Logger;
use Minds\Core\Notifications;

class Notify
{
    /** @var Repository */
    protected $repository;

    /** @var TokenPrices\Manager */
    protected $tokenPricesManager;

    /** @var EventsDispatcher */
    protected $eventsDispatcher;

    /** @var Logger */
    protected $logger;

    /** @var Notifications\Manager */
    protected $notificationsManager;

    public function __construct(
        Repository $repository = null,
        TokenPrices\Manager $tokenPricesManager = null,
        EventsDispatcher $eventsDispatcher = null,
        Logger $logger = null,
        Notifications\Manager $notificationsManager = null
    ) {
        $this->repository = $repository ?? new Repository();
        $this->tokenPricesManager = $tokenPricesManager ?? Di::_()->get('Blockchain\TokenPrices\Manager');
        $this->eventsDispatcher = $eventsDispatcher ?? Di::_()->get('EventsDispatcher');
        $this->logger = $logger ?? Di::_()->get('Logger');
        $this->notificationsManager = $notificationsManager ?? Di::_()->get('Notifications\Manager');
    }

    /**
     * Issues notifications to everyone who earned yesterday
     * @return void
     */
    public function run(): void
    {
        $opts = new RewardsQueryOpts();
        $opts->setDateTs(strtotime("midnight yesterday"));

        /** @var Core\Blockchain\TokenPrices\Manager */
        $tokenPrice = $this->tokenPricesManager->getPrices()['minds'];

        /** @var BigDecimal[] */
        $userGuidsToTotal = [];

        /**
         * First iterate through all rewards and move to in-memory map
         */
        foreach ($this->repository->getIterator($opts) as $rewardEntry) {
            $userGuid = $rewardEntry->getUserGuid();

            if (!isset($userGuidsToTotal[$userGuid])) {
                $userGuidsToTotal[$userGuid] = BigDecimal::of(0);
            }

            $userGuidsToTotal[$userGuid] = $userGuidsToTotal[$userGuid]->plus($rewardEntry->getTokenAmount());
        }

        $i = 0;
        foreach ($userGuidsToTotal as $userGuid => $total) {
            // Avoid sending 0 balances
            if ($total->isLessThanOrEqualTo("0.001")) {
                continue;
            }

            ++$i;

            $usd = $total->multipliedBy($tokenPrice)->toFloat();
            $usdFormated = number_format($usd, 2);
            $tokensFormatted = number_format($total->toFloat(), 3);

            if ($usd > 0.01) {
                // If USD is above 1 cent then send the USD amount
                $message = "🚀 You earned \${$usdFormated} worth of tokens yesterday. Nice job! 🚀";
            } else {
                $message = "🚀 You earned $tokensFormatted tokens yesterday 🚀";
            }

            $this->eventsDispatcher->trigger('notification', 'all', [
                    'to' => [ $userGuid ],
                    'from' => 100000000000000519,
                    'notification_view' => 'rewards_summary',
                    'params' => [
                        'message' => $message,
                        'amount' =>  $tokensFormatted
                    ],
                    'message' => $message,
                ]);

            $this->logger->log("INFO", "[$i]: {$userGuid} {$total} $message");

            $notification = new Notifications\Notification();
            $notification->setType(Notifications\NotificationTypes::TYPE_TOKEN_REWARDS_SUMMARY);
            $notification->setData([
                'tokens_formatted' =>  $tokensFormatted,
                'usd_formatted' => $usdFormated
            ]);
            $notification->setToGuid($userGuid);
            $notification->setEntityUrn('urn:user:' . $userGuid);
            $notification->setFromGuid(SystemUser::GUID);

            // Save and submit
            if ($this->notificationsManager->add($notification)) {
                // Some logging
                $this->logger->info("{$notification->getUuid()} {$notification->getType()} saved");
            }
        }
    }
}
