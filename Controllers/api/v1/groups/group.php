<?php
/**
 * Minds Group API
 * Group information endpoints
 */
namespace Minds\Controllers\api\v1\groups;

use Minds\Core;
use Minds\Core\Groups\V2\Membership\Manager;
use Minds\Core\Session;
use Minds\Interfaces;
use Minds\Api\Factory;
use Minds\Core\Di\Di;
use Minds\Core\Entities\Actions\Delete;
use Minds\Core\Entities\Actions\Save;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Groups\V2\Membership\Enums\GroupMembershipLevelEnum;
use Minds\Core\Security\Rbac\Enums\PermissionsEnum;
use Minds\Core\Security\Rbac\Services\RbacGatekeeperService;
use Minds\Entities\User;
use Minds\Entities\File as FileEntity;
use Minds\Entities\Factory as EntitiesFactory;
use Minds\Entities\Group as GroupEntity;

use Minds\Exceptions\GroupOperationException;
use Minds\Exceptions\NotFoundException;
use Minds\Exceptions\UserErrorException;

class group implements Interfaces\Api
{
    public function __construct(
        protected ?Manager $membershipManager = null,
        protected ?EntitiesBuilder $entitiesBuilder = null,
        protected ?Save $save = null,
    ) {
        $this->membershipManager = Di::_()->get(Manager::class);
        $this->entitiesBuilder = Di::_()->get('EntitiesBuilder');
        $this->save = new Save();
    }

    /**
     * Returns the conversations or conversation
     * @param array $pages
     *
     * API:: /v1/group/group/:guid
     */
    public function get($pages)
    {
        $group = $this->entitiesBuilder->single($pages[0]);
        $user = Session::getLoggedInUser();

        $response = [];

        if (!$group instanceof GroupEntity) {
            return Factory::response([
                'status' => 'error',
                'message' => 'The group could not be found',
            ]);
        }

        $response['group'] = $group->export();

        try {
            if (!$user) {
                throw new NotFoundException();
            }
            $membership = $this->membershipManager->getMembership($group, $user);
        } catch (NotFoundException $e) {
            $membership = null;
        }

        $notifications = (new Core\Groups\Notifications)
          ->setGroup($group);

        $response['group']['is:muted'] = $user && $notifications->isMuted($user);

        $canRead = $user && ($membership?->isMember() || $user->isAdmin());
        $canModerate = $user && ($membership?->isOwner() || $membership?->isModerator());

        if (!$canRead) {
            // Specify if the user is banned
            if ($membership && $this->membershipManager->isBanned($user, $group)) {
                $response['group']['is:banned'] = true;
            }

            // Restrict output if cannot read
            $allowed = ['guid', 'name', 'membership', 'type', 'is:awaiting', 'is:banned', 'is:invited', 'nsfw', 'nsfw_lock', 'conversationDisabled', 'briefdescription' ];
            if ($response['group']['membership'] == 2) {
                $allowed = array_merge($allowed, ['members:count', 'activity:count', 'comments:count']);
            }

            $response['group'] = array_filter($response['group'], function ($key) use ($allowed) {
                return in_array($key, $allowed, true);
            }, ARRAY_FILTER_USE_KEY);
        }

        if ($canModerate) {
            /** @var Core\Groups\Feeds $feeds */
            $feeds = Core\Di\Di::_()->get('Groups\Feeds');
            $count = (int) $feeds->setGroup($group)->count();

            $response['group']['adminqueue:count'] = $count;
        }

        return Factory::response($response);
    }

    public function post($pages)
    {
        Factory::isLoggedIn();

        $user = Session::getLoggedInUser();

        if (isset($pages[0])) {
            $creation = false;

            /** @var GroupEntity */
            $group = $this->entitiesBuilder->single($pages[0]);

            try {
                $membership = $this->membershipManager->getMembership($group, $user);
            } catch (NotFoundException $e) {
                return Factory::response([
                    'error' => 'Group membership not found'
                ]);
            }

            if (!$membership->isOwner() && !Core\Session::isAdmin()) {
                return Factory::response([
                    'error' => 'You cannot edit this group'
                ]);
            }
        } else {
            $creation = true;
            $group = new GroupEntity();

            /** @var RbacGatekeeperService */
            $rbacGatekeeperService = Di::_()->get(RbacGatekeeperService::class);
            $rbacGatekeeperService->isAllowed(PermissionsEnum::CAN_CREATE_GROUP);
        }

        if (isset($pages[1]) && $group->getGuid()) {
            // Specific updating (uploads)

            $response = [ 'done' => false ];
            $group_owner = EntitiesFactory::build($group->getOwnerObj());

            switch ($pages[1]) {
                case "avatar":
                    if (is_uploaded_file($_FILES['file']['tmp_name'])) {
                        try {
                            $group = $this->uploadAvatar($group);
                            $response['icontime'] = $group->getIconTime();
                        } catch (\Exception $e) {
                            return Factory::response([
                                'status' => 'error',
                                'message' => $e->getMessage()
                            ]);
                        }
                        $response['done'] = true;
                    }
                    break;
                case "banner":
                    if (is_uploaded_file($_FILES['file']['tmp_name'])) {
                        try {
                            $group = $this->uploadBanner($group, $_POST['banner_position']);
                            $response['banner'] = $group->banner;
                            $response['banner_position'] = $group->getBannerPosition();
                        } catch (\Exception $e) {
                            return Factory::response([
                                'status' => 'error',
                                'message' => $e->getMessage()
                            ]);
                        }
                        $response['done'] = true;
                    }
                    break;
            }

            return Factory::response($response);
        }

        // Creation / Updating

        if (!isset($_POST['name']) && $creation) {
            throw new UserErrorException('Groups must have a name');
        }

        if (isset($_POST['name']) && mb_strlen($_POST['name']) > 200) {
            throw new UserErrorException('Group names must be 200 characters or less');
        }

        if (isset($_POST['name'])) {
            $group->setName($_POST['name']);
        }

        if (isset($_POST['briefdescription'])) {
            $sanitized_briefdescription = htmlspecialchars(trim($_POST['briefdescription']), ENT_QUOTES, null, false);

            if (strlen($sanitized_briefdescription) > 2048) {
                $sanitized_briefdescription = substr($sanitized_briefdescription, 0, 2048);
            }

            $group->setBriefDescription($sanitized_briefdescription);
        }

        if (isset($_POST['membership'])) {
            $group->setMembership($_POST['membership']);

            if ($_POST['membership'] == 2) {
                $group->setAccessId(ACCESS_PUBLIC);

                if (!$creation) {
                    // (new Core\Groups\Membership)
                    //   ->setGroup($group)
                    //   ->setActor($user)
                    //   ->acceptAllRequests();
                }
            } elseif ($_POST['membership'] == 0) {
                $group->setAccessId(ACCESS_PRIVATE);
            }
        }

        if (isset($_POST['moderated'])) {
            $oldModerationValue = $group->getModerated();
            $group->setModerated($_POST['moderated']);

            $moderationChange = $oldModerationValue != $group->getModerated();
        }

        if (isset($_POST['show_boosts'])) {
            $group->setShowBoosts($_POST['show_boosts']);
        }

        if (isset($_POST['default_view'])) {
            $group->setDefaultView($_POST['default_view']);
        }

        if (isset($_POST['videoChatDisabled'])) {
            $group->setVideoChatDisabled($_POST['videoChatDisabled']);
        }

        if (isset($_POST['conversationDisabled'])) {
            $group->setConversationDisabled($_POST['conversationDisabled']);
        }

        if (isset($_POST['tags'])) {
            $tags = $_POST['tags'];
            $sanitized_tags = [];

            foreach ($tags as $tag) {
                $tag = trim(strip_tags($tag));

                if (strlen($tag) > 25) {
                    $tag = substr($tag, 0, 25);
                }

                $sanitized_tags[] = $tag;
            }

            $group->setTags($sanitized_tags);
        }

        if ($creation) {
            $group->setAccessId(2)
              ->setOwnerObj($user);
        }

        $this->save->setEntity($group)->save();

        if ($creation) {
            // Join group
            try {
                $this->membershipManager->joinGroup(
                    group: $group,
                    user: $user,
                    membershipLevel: GroupMembershipLevelEnum::OWNER
                );
            } catch (GroupOperationException $e) {
            }
        }

        if (!$creation && ($moderationChange ?? false) && !$group->getModerated()) {
            Core\Di\Di::_()->get('Groups\Feeds')
                ->setGroup($group)
                ->approveAll();
        }

        // Legacy behavior
        if (isset($_FILES['file']['tmp_name']) && is_uploaded_file($_FILES['file']['tmp_name'])) {
            try {
                $this->uploadBanner($group, $_POST['banner_position']);
            } catch (\Exception $e) {
                return Factory::response([
                    'status' => 'error',
                    'message' => $e->getMessage()
                ]);
            }
        }

        $response = [];
        $response['guid'] = $group->getGuid();

        if ($creation && isset($_POST['invitees']) && $_POST['invitees']) {
            $invitations = (new Core\Groups\Invitations)->setGroup($group)->setActor($user);
            $invitees = $_POST['invitees'];

            foreach ($invitees as $invitee) {
                if (is_numeric($invitee)) {
                    try {
                        $invitee = $this->entitiesBuilder->single($invitee);
                        $invitations->invite($invitee);
                    } catch (GroupOperationException $e) {
                    }
                }
            }
        }

        return Factory::response($response);
    }

    public function put($pages)
    {
        return Factory::response([]);
    }

    public function delete($pages)
    {
        Factory::isLoggedIn();

        $group = EntitiesFactory::build($pages[0]);
        $user = Session::getLoggedInUser();

        if (!$group || !$group->getGuid()) {
            return Factory::response([]);
        }

        $canDelete = Session::isAdmin() || $group->isCreator($user);

        if (!$canDelete) {
            return Factory::response([
                'error' => 'You cannot delete this group'
            ]);
        }

        try {
            (new Delete())->setEntity($group)->delete();

            return Factory::response([
                'done' => true
            ]);
        } catch (GroupOperationException $e) {
            return Factory::response([
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Uploads a Group avatar
     * @param GroupEntity $group
     * @return GroupEntity
     * @throws \IOException
     * @throws \InvalidParameterException
     * @throws \ImagickException
     */
    protected function uploadAvatar(GroupEntity $group)
    {
        $icon_sizes = Core\Config::_()->get('icon_sizes');
        $group_owner = EntitiesFactory::build($group->getOwnerObj());

        foreach (['tiny', 'small', 'medium', 'large'] as $size) {
            /** @var Core\Media\Imagick\Manager $manager */
            $manager = Core\Di\Di::_()->get('Media\Imagick\Manager');

            $manager->setImage($_FILES['file']['tmp_name'])
                ->autorotate()
                ->resize($icon_sizes[$size]['w'], $icon_sizes[$size]['h'], true, $icon_sizes[$size]['square']);

            $file = new FileEntity();
            $file->owner_guid = $group->owner_guid ?: $group_owner->getGuid();
            $file->setFilename("groups/{$group->getGuid()}{$size}.jpg");
            $file->open('write');
            $file->write($manager->getJpeg());
            $file->close();
        }

        $group->setIconTime(time());

        $this->save->setEntity($group)->save();

        return $group;
    }

    /**
     * Uploads a Group banner
     * @param GroupEntity $group
     * @param $banner_position
     * @return GroupEntity
     * @throws \IOException
     * @throws \InvalidParameterException
     * @throws \ImagickException
     */
    protected function uploadBanner($group, $banner_position)
    {
        $group_owner = EntitiesFactory::build($group->getOwnerObj());

        /** @var Core\Media\Imagick\Manager $manager */
        $manager = Core\Di\Di::_()->get('Media\Imagick\Manager');

        $manager->setImage($_FILES['file']['tmp_name'])
            ->autorotate()
            ->resize(3840, 1404);

        $file = new FileEntity();
        $file->owner_guid = $group->owner_guid ?: $group_owner->getGuid();
        $file->setFilename("group/{$group->getGuid()}.jpg");
        $file->open('write');
        $file->write($manager->getJpeg());
        $file->close();

        $group
            ->setBanner(time())
            ->setBannerPosition($banner_position);

        $this->save->setEntity($group)->save();

        return $group;
    }
}
