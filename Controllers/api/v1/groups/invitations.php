<?php
/**
 * Minds Group API
 * Group invitations endpoints
 */
namespace Minds\Controllers\api\v1\groups;

use Minds\Core;
use Minds\Core\Session;
use Minds\Interfaces;
use Minds\Api\Factory;
use Minds\Core\Di\Di;
use Minds\Entities;
use Minds\Entities\Factory as EntitiesFactory;
use Minds\Entities\User;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Groups\V2\Membership\Manager;
use Minds\Entities\Group;
use Minds\Exceptions\GroupOperationException;
use Minds\Exceptions\NotFoundException;
use Minds\Exceptions\UserErrorException;

class invitations implements Interfaces\Api
{
    public function __construct(
        protected ?Manager $membershipManager = null,
        protected ?EntitiesBuilder $entitiesBuilder = null
    ) {
        $this->membershipManager = Di::_()->get(Core\Groups\V2\Membership\Manager::class);
        $this->entitiesBuilder = Di::_()->get('EntitiesBuilder');
    }

    public function get($pages)
    {
        Factory::isLoggedIn();

        $group = $this->entitiesBuilder->single($pages[0]);
    
        if (!$group instanceof Group) {
            throw new UserErrorException("Invalid group");
        }
    
        $user = Session::getLoggedInUser();

        try {
            $membership = $this->membershipManager->getMembership($group, $user);
        } catch (NotFoundException $e) {
            return Factory::response([
                'error' => 'You cannot read invitations'
            ]);
        }

        if (!$membership->isMember()) {
            return Factory::response([
                'error' => 'You cannot read invitations'
            ]);
        }

        $invitees = (new Core\Groups\Invitations)
          ->setGroup($group)
          ->getInvitations([
            'hydrate' => true,
            'limit' => isset($_GET['limit']) ? (int) $_GET['limit'] : 12,
            'offset' => isset($_GET['offset']) ? $_GET['offset'] : ''
          ]);

        $response = [
            'users' => $invitees
        ];

        if ($invitees) {
            $response['load-next'] = (string) end($invitees)->guid;
        }

        return Factory::response($response);
    }

    public function post($pages)
    {
        Factory::isLoggedIn();

        // Start check-only response
        if ($pages[0] == 'check') {
            return $this->checkOnly();
        }
        // End check-only response

        $group = $this->entitiesBuilder->single($pages[0]);
    
        if (!$group instanceof Group) {
            throw new UserErrorException("Invalid group");
        }
    
        $invitee = Session::getLoggedInUser();

        try {
            $membership = $this->membershipManager->getMembership($group, $invitee);
        } catch (NotFoundException $e) {
            return Factory::response([]);
        }

        $invitations = (new Core\Groups\Invitations)
          ->setGroup($group)
          ->setActor($invitee);

        if (!$invitations->isInvited($invitee)) {
            return Factory::response([]);
        }

        $done = false;

        try {
            switch ($pages[1]) {
                case 'accept':
                    $done = $invitations->accept();
                    break;
                case 'decline':
                    $done = $invitations->decline();
                    break;
            }
        } catch (GroupOperationException $e) {
            return Factory::response([
                'done' => false,
                'error' => $e->getMessage()
            ]);
        }

        return Factory::response([
            'done' => $done
        ]);
    }

    public function put($pages)
    {
        Factory::isLoggedIn();

        /** @var Group */
        $group = $this->entitiesBuilder->single($pages[0]);
        $user = Session::getLoggedInUser();
        $payload = json_decode(file_get_contents('php://input'), true);

        if (!$group || !$group->getGuid()) {
            return Factory::response([
                'done' => false,
                'error' => 'No group'
            ]);
        }

        if (!isset($payload['guid']) || !$payload['guid'] || !is_numeric($payload['guid'])) {
            return Factory::response([
                'done' => false,
                'error' => 'Invalid guid'
            ]);
        }

        $invitee = $this->entitiesBuilder->single($payload['guid']);

        if (!$invitee || !$invitee->username) {
            return Factory::response([
                'done' => false,
                'error' => 'User not found'
            ]);
        }

        try {
            $membership = $this->membershipManager->getMembership($group, $invitee);
        } catch (NotFoundException $e) {
            $membership = null;
        }
    
        if ($banned = $membership?->isBanned()) {
            $loggedInUserMembershp = null;

            try {
                $loggedInUserMembershp = $this->membershipManager->getMembership($group, $user);
            } catch (NotFoundException $e) {
                // do nothing - user has no membership record.
            }

            // if the user is not at minimum, a moderator, prevent them from inviting the banned user.
            if (!$loggedInUserMembershp?->isModerator()) {
                return Factory::response([
                    'done' => false,
                    'error' => 'User is banned from this group'
                ]);
            }
        }

        $invitations = (new Core\Groups\Invitations)
          ->setGroup($group)
          ->setActor($user);

        try {
            $invited = $invitations->invite($invitee);
        } catch (GroupOperationException $e) {
            return Factory::response([
                'done' => false,
                'error' => $e->getMessage()
            ]);
        }

        // lift any existing bans on the user.
        if ($banned) {
            $this->membershipManager->unbanUser($group, $invitee, $user);
        }

        return Factory::response([
            'done' => $invited
        ]);
    }

    public function delete($pages)
    {
        Factory::isLoggedIn();

        $group = EntitiesFactory::build($pages[0]);
        $user = Session::getLoggedInUser();
        $payload = json_decode(file_get_contents('php://input'), true);

        if (!$group || !$group->getGuid()) {
            return Factory::response([
                'done' => false,
                'error' => 'No group'
            ]);
        }

        if (!isset($payload['invitee']) || !$payload['invitee'] || !ctype_alnum($payload['invitee'])) {
            return Factory::response([
                'done' => false,
                'error' => 'Invalid username'
            ]);
        }

        $invitee = $this->entitiesBuilder->getByUserByIndex(strtolower($payload['invitee']));

        if (!$invitee || !$invitee->guid) {
            return Factory::response([
                'done' => false,
                'error' => 'User not found'
            ]);
        }

        $invitations = (new Core\Groups\Invitations)
          ->setGroup($group)
          ->setActor($user);

        try {
            $uninvited = $invitations->uninvite($invitee);
        } catch (GroupOperationException $e) {
            return Factory::response([
                'done' => false,
                'error' => $e->getMessage()
            ]);
        }

        return Factory::response([
            'done' => $uninvited
        ]);
    }

    protected function checkOnly()
    {
        if (!isset($_POST['user']) || !$_POST['user']) {
            return Factory::response([
                'done' => false,
                'error' => 'User not found'
            ]);
        }

        $user = Session::getLoggedInUser();
        $invitee = $this->entitiesBuilder->single($_POST['user']);

        if (!$invitee || !$invitee->guid) {
            return Factory::response([
                'done' => false,
                'error' => 'User not found'
            ]);
        }

        if ($user->guid == $invitee->guid) {
            return Factory::response([
                'done' => false,
                'error' => 'You cannot invite yourself'
            ]);
        }

        $invitations = (new Core\Groups\Invitations)
          ->setGroup(new Entities\Group());

        return Factory::response([
            'done' => $invitations->userHasSubscriber($user, $invitee)
        ]);
    }
}
