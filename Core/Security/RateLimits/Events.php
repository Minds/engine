<?php
namespace Minds\Core\Security\RateLimits;

use Minds\Core\Di\Di;
use Minds\Core\Events\Dispatcher;
use Stripe\Exception\RateLimitException;

class Events
{
    /** @var InteractionsLimiter */
    protected $interactionsLimiter;

    public function __construct($interactionsLimiter = null)
    {
        $this->interactionsLimiter = $interactionsLimiter;
    }

    /**
     * Registers events that the block system hooks into
     */
    public function register(): void
    {
        Dispatcher::register('acl:interact', 'all', function ($e) {
            if (!$this->interactionsLimiter) {
                $this->interactionsLimiter =  Di::_()->get('Security\RateLimits\InteractionsLimiter');
            }

            $params = $e->getParameters();
            $entity = $params['entity'];
            $user = $params['user'];
            $interaction = $params['interaction'];

            if (!$interaction) {
                return;
            }

            $this->interactionsLimiter->checkAndIncrement($user->getGuid(), $interaction);
        });
    }
}
