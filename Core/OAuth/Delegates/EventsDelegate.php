<?php
/**
 * Background events delegate
 */
namespace Minds\Core\OAuth\Delegates;

use Minds\Core\Queue;
use League\OAuth2\Server\RequestTypes\AuthorizationRequest;
use Minds\Core\Di\Di;

class EventsDelegate
{
    /** @var Queue\Interfaces\QueueClient */
    protected $queue;

    public function __construct($queue = null)
    {
        $this->queue = $queue ?? Di::_()->get('Queue');
    }

    /**
     * Submit the the OAuthEvents stream
     * @param AuthorizationRequest $authRequest
     */
    public function onAuthorizeSuccess(AuthorizationRequest $authRequest): void
    {
        // Submit an event to the queue
        $this->queue
            ->setQueue('OAuthEvents')
            ->send([
                'event' => 'authorize',
                'client_id' => $authRequest->getClient()->getIdentifier(),
                'user_guid' => $authRequest->getUser()->getIdentifier(),
            ], 60); // Delay this for 60 seconds
    }
}
