<?php
/**
* Minds Group API
* Membership-related operations
*/
namespace Minds\Controllers\api\v1\groups;

use Minds\Api\Exportable;
use Minds\Core;
use Minds\Core\Session;
use Minds\Interfaces;
use Minds\Api\Factory;
use Minds\Entities\Factory as EntitiesFactory;
use Minds\Core\Search\Documents;
use Minds\Core\Data\ElasticSearch\Prepared;
use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Groups\Invitations;
use Minds\Core\Groups\V2\Membership\Enums\GroupMembershipLevelEnum;
use Minds\Core\Groups\V2\Membership\Manager;
use Minds\Entities\Group;
use Minds\Entities\User;
use Minds\Exceptions\GroupOperationException;
use Minds\Exceptions\NotFoundException;
use Minds\Helpers\Export;

class membership implements Interfaces\Api
{
    public function __construct(
        protected ?Manager $membershipManager = null,
        protected ?Invitations $invitations = null,
        protected ?EntitiesBuilder $entitiesBuilder = null
    ) {
        $this->membershipManager = Di::_()->get(Core\Groups\V2\Membership\Manager::class);
        $this->invitations = new Invitations();
        $this->entitiesBuilder = Di::_()->get('EntitiesBuilder');
    }

    /**
    * Returns the members
    * @param array $pages
    *
    * API:: /v1/group/group/:guid
    */
    public function get($pages)
    {
        /** @var Group */
        $group = $this->entitiesBuilder->single($pages[0]);

        $loggedInUser = Core\Session::getLoggedinUser();

        try {
            if (!$loggedInUser) {
                throw new NotFoundException();
            }
            $membership = $this->membershipManager->getMembership($group, $loggedInUser);
        } catch (NotFoundException $e) {
            return Factory::response([]);
        }

        if (!$group->isPublic() && !$membership->isMember() && !$loggedInUser->isAdmin()) {
            return Factory::response([]);
        }

        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 12;
        $offset = isset($_GET['offset']) ? (int) $_GET['offset'] : 0;

        if (!isset($pages[1])) {
            $pages[1] = "members";
        }

        $response = [];
        $loadNext = 0;

        switch ($pages[1]) {
            case "requests":
                if (!$membership->isModerator()) {
                    return Factory::response([]);
                }

                $users = iterator_to_array($this->membershipManager->getRequests(
                    group: $group,
                    limit: $limit,
                    offset: $offset,
                    loadNext: $loadNext,
                ));

                if (!$users) {
                    return Factory::response([]);
                }

                $response['users'] = Exportable::_($users);
                $response['load-next'] = $loadNext;
                break;
            case "search":
                if (!isset($_GET['q']) || !$_GET['q']) {
                    return Factory::response([
                        'status' => 'error',
                        'message' => 'Missing query'
                    ]);
                }


                $query = Documents::escapeQuery((string) $_GET['q']);
                $query = "%$query%";
                $query = "(username:{$query})^10 OR (name:{$query})^5 OR (group_membership:\"{$group->getGuid()}\")^1000";

                $opts = [
                    'limit' => 100,
                    'type' => 'user',
                    'flags' => [
                    //    "+group_membership:\"{$group->getGuid()}\""
                    ]
                ];

                if (isset($_GET['offset']) && $_GET['offset']) {
                    $opts['offset'] = $_GET['offset'];
                }

                $suggestions = (new Documents())->suggestQuery($_GET['q'], [ 'size' => 100 ]);
                $guids = array_map(function ($row) {
                    return $row['_source']['guid'];
                }, $suggestions['suggest']['autocomplete'][0]['options']);

                $response = [];

                if (!$guids) {
                    return Factory::response([
                        'members' => [],
                    ]);
                }

                /** @var Core\Groups\V2\Membership\Membership[] */
                $members = [];

                $i = 0;
                foreach ($guids as $guid) {
                    if ($i > 12) {
                        break;
                    }
                    $user = $this->entitiesBuilder->single($guid);
                    if (!$user instanceof User) {
                        continue;
                    }
                    try {
                        $userMembership = $this->membershipManager->getMembership($group, $user);
                        if ($userMembership->isMember()) {
                            $userMembership->setUser($user);
                            $members[] = $userMembership;
                        }
                    } catch (\Exception $e) {

                    }
                }

                $guids = array_slice($guids, 0, 12);

                $response['members'] = Exportable::_($members);
                break;
            case "members":
            default:
                if (!$membership->isMember()) {
                    return Factory::response([]);
                }

                $membershipLevel = null;
                if (isset($_GET['membership_level'])) {
                    $membershipLevel = GroupMembershipLevelEnum::tryFrom((int)($_GET['membership_level']) ?? null);
                }

                $membershipLevelGte = isset($_GET['membership_level_gte']) ? (bool) $_GET['membership_level_gte'] : false;

                $members = iterator_to_array($this->membershipManager->getMembers(
                    group: $group,
                    membershipLevel: $membershipLevel,
                    membershipLevelGte: $membershipLevelGte,
                    limit: $limit,
                    offset: $offset,
                    loadNext: $loadNext,
                ));

                if (!$members) {
                    return Factory::response([]);
                }

                $response['members'] = Exportable::_($members);

                $response['load-next'] = $loadNext;
                $response['total'] = $this->membershipManager->getMembersCount($group);
                break;
        }

        return Factory::response($response);
    }

    public function post($pages)
    {
        Factory::isLoggedIn();

        /** @var Group */
        $group = $this->entitiesBuilder->single($pages[0]);

        $loggedInUser = Core\Session::getLoggedinUser();

        if (!isset($pages[1])) {
            return Factory::response([]);
        }

        $response = [];
        try {
            switch ($pages[1]) {
                case 'cancel':
                    $response['done'] = $this->membershipManager->cancelRequest($group, $loggedInUser);
                    break;
                case 'kick':
                    $userGuid = $_POST['user'];
                    $user = $this->entitiesBuilder->single($userGuid);

                    if (!$user instanceof User) {
                        break;
                    }
                    $response['done'] = $this->membershipManager->removeUser($group, $user, $loggedInUser);
                    break;
                case 'ban':
                    $userGuid = $_POST['user'];
                    $user = $this->entitiesBuilder->single($userGuid);

                    if (!$user instanceof User) {
                        break;
                    }

                    $response['done'] = $this->membershipManager->banUser($group, $user, $loggedInUser);
                    break;
                case 'unban':
                    $userGuid = $_POST['user'];
                    $user = $this->entitiesBuilder->single($userGuid);

                    if (!$user instanceof User) {
                        break;
                    }

                    $response['done'] = $this->membershipManager->unbanUser($group, $user, $loggedInUser);
                    break;
            }

            return Factory::response($response);
        } catch (GroupOperationException $e) {
            return Factory::response([
                'done' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function put($pages)
    {
        Factory::isLoggedIn();

        /** @var Group */
        $group = $this->entitiesBuilder->single($pages[0]);

        $loggedInUser = Core\Session::getLoggedinUser();

        try {
            $membership = $this->membershipManager->getMembership($group, $loggedInUser);
        } catch (NotFoundException $e) {
            $membership = null;
        }

        // if (!Core\Security\ACL::_()->interact($group, $loggedInUser)) {
        //     return Factory::response([
        //         'status' => 'error',
        //         'stage' => 'initial',
        //         'message' => "You are not allowed to join this group"
        //     ]);
        // }

        if (isset($pages[1]) && $membership?->isModerator()) {
            $userGuid = $pages[1];
            $user = $this->entitiesBuilder->single($userGuid);
            if (!$user instanceof User) {
                return Factory::response([
                    'status' => 'error',
                    'message' => 'Invalid user'
                ]);
            }
            //Admin approval
            try {
                $joined = $this->membershipManager->acceptUser(
                    group: $group,
                    user: $user,
                    actor: $loggedInUser,
                );

                $event = new Core\Analytics\Metrics\Event();
                $event->setType('action')
                    ->setProduct('platform')
                    ->setAction("join")
                    ->setUserGuid((string) $user->guid)
                    ->setUserPhoneNumberHash($user->getPhoneNumberHash())
                    ->setEntityGuid((string) $group->guid)
                    ->setEntityType($group->type)
                    ->push();

                return Factory::response([
                    'done' => $joined
                ]);
            } catch (GroupOperationException $e) {
                return Factory::response([
                    'done' => false,
                    'message' => $e->getMessage()
                ]);
            }
        }

        // Normal join
        try {
            if ($this->invitations->setGroup($group)->isInvited($loggedInUser)) {
                $joined = $this->invitations->setGroup($group)->setActor($loggedInUser)->accept();
            } else {
                $joined = $this->membershipManager->joinGroup($group, $loggedInUser);
            }

            $event = new Core\Analytics\Metrics\Event();
            $event->setType('action')
                ->setProduct('platform')
                ->setAction("join")
                ->setEntityMembership(2)
                ->setUserGuid((string) $loggedInUser->guid)
                ->setUserPhoneNumberHash($loggedInUser->getPhoneNumberHash())
                ->setEntityGuid((string) $group->guid)
                ->setEntityType($group->type)
                ->push();

            return Factory::response([
                'done' => $joined
            ]);
        } catch (GroupOperationException $e) {
            return Factory::response([
                'status' => 'error',
                'done' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function delete($pages)
    {
        Factory::isLoggedIn();

        /** @var Group */
        $group = $this->entitiesBuilder->single($pages[0]);

        $loggedInUser = Core\Session::getLoggedinUser();

        $membership = $this->membershipManager->getMembership($group, $loggedInUser);

        // TODO: [emi] Check if this logic makes sense

        if (isset($pages[1])) {
            $userGuid = $pages[1];
            $user = $this->entitiesBuilder->single($userGuid);
            if (!$user instanceof User) {
                return Factory::response([
                    'status' => 'error',
                    'message' => 'Invalid user'
                ]);
            }
            //Admin approval
            try {
                $cancelled = $this->membershipManager->removeUser($group, $user, $loggedInUser);

                return Factory::response([
                    'done' => $cancelled
                ]);
            } catch (GroupOperationException $e) {
                return Factory::response([
                    'done' => false,
                    'message' => $e->getMessage()
                ]);
            }
        }

        // Normal leave
        try {
            $left = $this->membershipManager->leaveGroup($group, $loggedInUser);

            return Factory::response([
                'done' => $left
            ]);
        } catch (GroupOperationException $e) {
            return Factory::response([
                'done' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
}
