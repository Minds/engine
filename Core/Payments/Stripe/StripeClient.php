<?php
namespace Minds\Core\Payments\Stripe;

use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\Sessions\ActiveSession;
use Minds\Entities\User;
use Stripe;

class StripeClient extends Stripe\StripeClient
{
    public function __construct(
        $config = [], // Stripe provided
        protected ?Config $mindsConfig = null,
        protected ?ActiveSession $activeSession = null
    ) {
        $this->mindsConfig ??= Di::_()->get('Config');
        $this->activeSession ??= Di::_()->get('Sessions\ActiveSession');

        $stripeConfig = $this->mindsConfig->get('payments')['stripe'];

        if (!isset($config['api_key'])) {
            $useTestKey = false;
            
            /** @var User|null */
            $loggedInUser = $this->activeSession->getUser();
    
            if (
                $loggedInUser &&
                $loggedInUser->getEmail() === $stripeConfig['test_email'] &&
                $loggedInUser->isEmailConfirmed() // Note this is  not isTrusted as we want to require it is fully confirmed
            ) {
                $useTestKey = true;
            }

            $config['api_key'] = $useTestKey ? $stripeConfig['test_api_key'] : $stripeConfig['api_key'];
        }
        
        parent::__construct($config);
    }
}
