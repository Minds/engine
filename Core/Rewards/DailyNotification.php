<?php
namespace Minds\Core\Rewards;

use Minds\Core\Config;
use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Events\EventsDispatcher;
use Minds\Entities\User;
use Minds\Core\Util\BigNumber;

class DailyNotification
{
    /** @var Config */
    protected $config;

    /** @var EntitiesBuilder */
    protected $entitiesBuilder;

    /** @var Contributions\Manager */
    protected $contributionsManager;

    /** @var EventsDispatcher */
    protected $eventsDispatcher;

    /** @var int */
    protected $from;

    /**
     * @param Config $config
     * @param EntitiesBuilder $entitiesBuilder
     */
    public function __construct(Config $config = null, EntitiesBuilder $entitiesBuilder = null, Contributions\Manager $contributionsManager = null, EventsDispatcher $eventsDispatcher = null)
    {
        $this->config = $config ?? Di::_()->get('Config');
        $this->entitiesBuilder = $entitiesBuilder ?? Di::_()->get('EntitiesBuilder');
        $this->contributionsManager = $contributionsManager ?? Di::_()->get('Rewards\Contributions\Manager');
        $this->eventsDispatcher = $eventsDispatcher ?? Di::_()->get('EventsDispatcher');
        $this->from = strtotime('midnight yesterday') * 1000;
    }

    /**
     * @param int $from
     * @return DailyNotification
     */
    public function setFrom($from): DailyNotification
    {
        $instance = clone $this;
        $instance->from = $from;
        return $instance;
    }

    /**
     * Send daily notification
     * @return void
     */
    public function sendToAll(): void
    {
        $users = new Contributions\UsersIterator;
        $users->setFrom($this->from)
            ->setTo($this->getToTs());

        foreach ($users as $guid) {
            /** @var User */
            $user = $this->entitiesBuilder->single($guid);
            $this->send($user);
        }
    }
    
    /**
     * Send daily notification
     * @param User $user
     * @return void
     */
    public function send(User $user): void
    {
        // Get the balance
        $this->contributionsManager
            ->setFrom($this->from)
            ->setTo($this->getToTs())
            ->setUser($user);

        $amount = (int) BigNumber::fromPlain($this->contributionsManager->getRewardsAmount(), 18)
            ->mul(1000) // We want 3 decimal places
            ->toString() / 1000;

        if ($amount <= 0) {
            return;
        }

        $message = $this->getMessageVariation((string) $amount);

        $this->eventsDispatcher->trigger('notification', 'all', [
            'to' => [ $user->getGuid() ],
            'from' => 100000000000000519,
            'notification_view' => 'custom_message',
            // The below can be changed once the frontend and mobile has the new language updated
            // 'notification_view' => 'rewards_summary',
            'params' => [
                'amount' => (string) $amount,
                'message' => $message,
                'router_link' => '/wallet/canary/tokens/transactions',
            ],
            'message' => $message,
        ]);
    }

    /**
     * Returns one day in the future relative to the from timestamp
     * @return int
     */
    private function getToTs(): int
    {
        return strtotime('tomorrow', $this->from / 1000) * 1000;
    }

    /**
     * Returns a variation of the message we are sending
     * @param string $amount
     * @return string
     */
    private function getMessageVariation(string $amount): string
    {
        $variants = [
            "Nice work. You earned @ tokens yesterday. Keep going!",
            "Congrats! You earned @ tokens yesterday.",
            "Your activity yesterday earned you @ tokens. Nice job!",
            "Wow...Your efforts yesterday earned you @ tokens. Keep it up!",
        ];
        return str_replace('@', $amount, $variants[array_rand($variants)]);
    }
}
