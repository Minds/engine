<?php
/**
 * Invitations to Groups
 */
namespace Minds\Core\Groups;

use Minds\Core;
use Minds\Core\Di\Di;
use Minds\Core\Events\Dispatcher;
use Minds\Entities;
use Minds\Entities\User;
use Minds\Entities\Group;
use Minds\Core\EntitiesBuilder;
use Minds\Core\EventStreams\ActionEvent;
use Minds\Core\EventStreams\Topics\ActionEventsTopic;

use Minds\Behaviors\Actorable;
use Minds\Core\Groups\V2\Membership\Enums\GroupMembershipLevelEnum;
use Minds\Exceptions\GroupOperationException;
use Minds\Exceptions\NotFoundException;

class Invitations
{
    use Actorable;

    /** @var Group $group  */
    protected $group;

    protected $relDB;
    protected $friendsDB;

    /** @var EntitiesBuilder */
    protected $entitiesBuilder;

    /** @var ActionEventsTopic */
    protected $actionEventsTopic;

    /**
     * Constructor
     * @param Group $group
     */
    public function __construct(
        $db = null,
        $acl = null,
        $friendsDB = null,
        EntitiesBuilder $entitiesBuilder = null,
        ActionEventsTopic $actionEventsTopic = null,
        protected ?V2\Membership\Manager $membershipManager = null
    ) {
        $this->relDB = $db ?: Di::_()->get('Database\Cassandra\Relationships');
        // TODO: [emi] Ask Mark about a 'friendsof' replacement (or create a DI entry)
        $this->friendsDB = $friendsDB ?: new Core\Data\Call('friendsof');
        $this->entitiesBuilder = $entitiesBuilder ?? Di::_()->get('EntitiesBuilder');
        $this->actionEventsTopic = $actionEventsTopic ?? Di::_()->get('EventStreams\Topics\ActionEventsTopic');
        $this->membershipManager ??= Di::_()->get(V2\Membership\Manager::class);
        $this->setAcl($acl);
    }

    /**
     * Set the group
     * @param Group $group
     * @return $this
     */
    public function setGroup($group)
    {
        $this->group = $group;
        return $this;
    }

    /**
     * Fetch the group invitations
     * @return array
     */
    public function getInvitations(array $opts = [])
    {
        $opts = array_merge([
            'limit' => 12,
            'offset' => '',
            'hydrate' => true
        ], $opts);

        $this->relDB->setGuid($this->group->getGuid());

        $guids = $this->relDB->get('group:invited', [
            'limit' => $opts['limit'],
            'offset' => $opts['offset'],
            'inverse' => true
        ]);

        if (!$guids) {
            return [];
        }

        if (!$opts['hydrate']) {
            return $guids;
        }

        $users = Core\Entities::get([ 'guids' => $guids ]);

        return $users;
    }

    /**
     * Checks a GUID array for invitation status
     * @param  array   $users
     * @return boolean
     */
    public function isInvitedBatch(array $users = [])
    {
        if (!$users) {
            return [];
        }

        $invited_guids = $this->getInvitations([ 'hydrate' => false ]);
        $result = [];

        foreach ($users as $user) {
            $result[$user] = in_array($user, $invited_guids, false);
        }

        return $result;
    }

    /**
     * Checks invitation status
     * @param  mixed   $invitee
     * @return boolean
     */
    public function isInvited($invitee)
    {
        if (!$invitee) {
            return false;
        }

        $invitee_guid = is_object($invitee) ? $invitee->guid : $invitee;
        $this->relDB->setGuid($invitee_guid);

        return $this->relDB->check('group:invited', $this->group->getGuid());
    }

    /**
     * Invites a user to the group
     * @param  mixed   $invitee
     * @param  mixed   $from
     * @return boolean
     */
    public function invite($invitee, array $opts = [])
    {
        $opts = array_merge([
            'notify' => true
        ], $opts);

        if (!$invitee || !$invitee->guid) {
            throw new GroupOperationException('User not found');
        }

        if ($this->getActor() && ($this->getActor()->guid == $invitee->guid)) {
            throw new GroupOperationException('Cannot invite yourself');
        }

        if ($this->isGroupMember($invitee)) {
            throw new GroupOperationException('User is already a member of the group');
        }

        $canInvite = $this->userCanInvite($this->getActor(), $invitee);

        if (!$canInvite) {
            throw new GroupOperationException('You cannot invite this user');
        }

        $invitee_guid = is_object($invitee) ? $invitee->guid : $invitee;
        // TODO: [emi] Check if the user blocked this group from sending invites
        $this->relDB->setGuid($invitee_guid);

        $invited = $this->relDB->create('group:invited', $this->group->getGuid());

        $actionEvent = new ActionEvent();
        $actionEvent
            ->setAction(ActionEvent::ACTION_GROUP_INVITE)
            ->setEntity($invitee)
            ->setUser($this->getActor())
            ->setActionData([
                'group_urn' => $this->group->getUrn(),
            ]);

        $this->actionEventsTopic->send($actionEvent);

        if ($opts['notify']) {
            Dispatcher::trigger('notification', 'all', [
                'to' => [ $invitee_guid ],
                'notification_view' => 'group_invite',
                'params' => [
                    'entity_guid' => $this->group->guid,
                    'group' => $this->group->export(),
                    'user' => $this->getActor() ? $this->getActor()->username : 'A user'
                ]
            ]);
        }

        return $invited;
    }

    /**
     * Destroys a user invitation to the group
     * @param  mixed   $invitee
     * @param  mixed   $from
     * @return boolean
     */
    public function uninvite($invitee)
    {
        if (!$invitee || !$invitee->guid) {
            throw new GroupOperationException('User not found');
        }

        if ($this->isGroupMember($invitee)) {
            throw new GroupOperationException('User is already a member of the group');
        }

        $canInvite = $this->userCanInvite($this->getActor(), $invitee);

        if (!$canInvite) {
            throw new GroupOperationException('You cannot invite this user');
        }

        return $this->removeInviteFromIndex($invitee);
    }

    /**
     * Accepts an invitation to the group
     * @param  mixed   $invitee
     * @return boolean
     */
    public function accept()
    {
        if (!$this->hasActor()) {
            throw new GroupOperationException('User not found');
        }

        if (!$this->isInvited($this->getActor())) {
            throw new GroupOperationException('You were not invited to this group');
        }

        $this->removeInviteFromIndex($this->getActor());
        return $this->membershipManager->joinGroup(
            group: $this->group,
            user: $this->getActor(),
            membershipLevel: GroupMembershipLevelEnum::MEMBER
        );
    }

    /**
     * Declines an invitation to the group
     * @param  mixed   $invitee
     * @return boolean
     */
    public function decline()
    {
        if (!$this->hasActor()) {
            throw new GroupOperationException('User not found');
        }

        if (!$this->isInvited($this->getActor())) {
            throw new GroupOperationException('You were not invited to this group');
        }

        $this->removeInviteFromIndex($this->getActor());
        return true;
    }

    /**
     * Checks if the user can invite to the group. It'll optionally check if it can invite a certain user.
     * @param  mixed   $user
     * @param  mixed   $invitee Optional.
     * @return boolean
     */
    public function userCanInvite($user, $invitee = null)
    {
        if (!$user) {
            return false;
        }

        if ($user && !($user instanceof User)) {
            $user = Entities\Factory::build($user);
        }

        if ($invitee && !($user instanceof User)) {
            $invitee = Entities\Factory::build($invitee);
        }

        if ($user->isAdmin()) {
            return true;
        } elseif ($this->group->isPublic() && $this->isGroupMember($user)) {
            return $invitee ? $this->userHasSubscriber($user, $invitee) : true;
        } elseif (!$this->group->isPublic() && $this->acl->write($this->group, $user)) {
            return $invitee ? $this->userHasSubscriber($user, $invitee) : true;
        }

        return false;
    }

    /**
     * Checks if a user has a certain subscriber
     * @param  User   $user
     * @param  User   $subscriber
     * @return boolean
     */
    public function userHasSubscriber(User $user, User $subscriber)
    {
        $row = $this->friendsDB->getRow($user->guid, [ 'limit' => 1, 'offset' => (string) $subscriber->guid ]);

        return $row && isset($row[(string) $subscriber->guid]);
    }

    /**
     * Shrotcut function to remove a GUID from the "group:invited" index.
     * @param  mixed $invitee
     * @return boolean
     */
    protected function removeInviteFromIndex($invitee)
    {
        $invitee_guid = is_object($invitee) ? $invitee->guid : $invitee;
        $this->relDB->setGuid($invitee_guid);

        return $this->relDB->remove('group:invited', $this->group->getGuid());
    }

    /**
     * Helper function to replace existing use case of Group->isMember
     */
    private function isGroupMember(User $user): bool
    {
        try {
            return $this->membershipManager->getMembership($this->group, $user)->isMember();
        } catch (NotFoundException $e) {
            return false;
        }
    }
}
