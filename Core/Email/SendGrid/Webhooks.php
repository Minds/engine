<?php
namespace Minds\Core\Email\SendGrid;

use Minds\Core\Di\Di;
use Minds\Core\Config;

class Webhooks
{
    /** @var string */
    private $authKey;

    /** @var Config */
    private $config;

    /** @var Delegates\UnsubscribeDelegate */
    private $unsubscribeDelegate;

    public function __construct($config = null, $unsubscribeDelegate = null)
    {
        $this->config = $config ?? Di::_()->get('Config');
        $this->unsubscribeDelegate = $unsubscribeDelegate ?? new Delegates\UnsubscribeDelegate;
    }

    /**
     * Set the public auth key that sengrid sends
     * @param string $authKey
     * @return self
     */
    public function setAuthKey(string $authKey): self
    {
        $this->authKey = $authKey;

        if ($this->authKey !== $this->config->get('email')['sendgrid']['webhook_key']) {
            throw new \Exception('Invalid SendGrid webhook key');
        }

        return $this;
    }

    /**
     * Process the events
     * @param array $events
     * @return void
     */
    public function process(array $events): void
    {
        if (!$this->authKey) {
            throw new \Exception('No sendgrid auth key provided');
        }

        foreach ($events as $event) {
            switch ($event['event']) {
                case 'unsubscribe':
                    $this->unsubscribeDelegate->onWebhook($event);
                    break;
            }
        }
    }
}
