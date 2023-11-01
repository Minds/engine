<?php
namespace Minds\Core\Email\SendGrid\Lists;

use Minds\Core\Di\Di;
use Minds\Core\Email\SendGrid\SendGridContact;
use Minds\Core\Subscriptions\Manager;
use Minds\Entities\User;

/**
 * Assembles list of users subscribed to notable channels
 */
class SubscribersList implements SendGridListInterface
{
    protected array $subscribedTo = [
        '100000000000000341', // ottman
        '100000000000000793', // iancrossland
        '626772382194872329', // timcast
        '1049109748013604865', // zubymusic
        '1196157469932396557', // klara_sjo
        '618430429199872014', // aragmar
    ];

    public function __construct(
        private ?Manager $subscriptionsManager = null
    ) {
        $this->subscriptionsManager ??= Di::_()->get(Manager::class);
    }

    /**
     * @return SendGridContact[]
     */
    public function getContacts(): iterable
    {
        foreach ($this->subscribedTo as $subscribedTo) {
            $pagingToken = '';
            while (true) {
                $response = $this->subscriptionsManager->getList([
                    'type' => 'subscribers',
                    'guid' => $subscribedTo,
                    'offset' => $pagingToken
                ]);

                foreach ($response as $user) {
                    if (!$user instanceof User) {
                        continue;
                    }

                    $contact = new SendGridContact();
                    $contact
                        ->setUser($user)
                        ->setUserGuid($user->getGuid())
                        ->setUsername($user->getUsername())
                        ->setEmail($user->getEmail())
                        ->setSubscribedTo([
                            $subscribedTo
                        ]);

                    if (!$contact->getEmail()) {
                        continue;
                    }

                    yield $contact;
                }

                if ($response->isLastPage()) {
                    break;
                }

                $pagingToken = $response->getPagingToken();
            }
        }
    }
}
