<?php
/**
 *
 */
namespace Minds\Core\Notifications\Delegates;

use Minds\Core\Di\Di;
use Minds\Core\Notifications\EmailDigests;
use Minds\Core\Notifications\Notification;

class EmailDigestsDelegate implements NotificationsDelegateInterface
{
    /** @var EmailDigests\Manager */
    protected $emailDigestsManager;

    public function __construct(EmailDigests\Manager $emailDigestsManager = null)
    {
        $this->emailDigestsManager = $emailDigestsManager;
    }

    /**
     * @param Notification $notification
     * @return void
     */
    public function onAdd(Notification $notification): void
    {
        $this->getEmailDigestsManager()->addToQueue($notification);
    }

    /**
     * @return NotificationsTopic
     */
    protected function getEmailDigestsManager(): EmailDigests\Manager
    {
        if (!$this->emailDigestsManager) {
            $this->emailDigestsManager = Di::_()->get('Notifications\EmailDigests\Manager');
        }
        return $this->emailDigestsManager;
    }
}
