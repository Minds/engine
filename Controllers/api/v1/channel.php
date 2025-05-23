<?php
/**
 * Minds Channel API
 *
 * @version 1
 * @author Mark Harding
 */
namespace Minds\Controllers\api\v1;

use Minds\Core;
use Minds\Helpers;
use Minds\Interfaces;
use Minds\Entities;
use Minds\Api\Factory;
use Minds\Common\ChannelMode;
use Minds\Core\Di\Di;
use Minds\Core\Security\Block\BlockEntry;
use ElggFile;
use Minds\Common\Regex;
use Minds\Core\ActivityPub\Services\ProcessActorService;
use Minds\Core\Channels\AvatarService;
use Minds\Core\Channels\BannerService;
use Minds\Core\Config\Config;
use Minds\Core\Entities\Actions\Save;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Router\Exceptions\ForbiddenException;
use Minds\Core\Security\ACL;
use Minds\Core\Security\Rbac\Enums\PermissionsEnum;
use Minds\Core\Security\Rbac\Services\RolesService;
use Minds\Entities\User;
use Minds\Helpers\StringLengthValidators\BriefDescriptionLengthValidator;
use Zend\Diactoros\ServerRequestFactory;

class channel implements Interfaces\Api
{
    private EntitiesBuilder $entitiesBuilder;
    private Save $save;
    private Config $config;
    private ACL $acl;

    public function __construct()
    {
        $this->entitiesBuilder = Di::_()->get(EntitiesBuilder::class);
        $this->save = new Save();
        $this->config = Di::_()->get(Config::class);
        $this->acl = Di::_()->get(ACL::class);
    }

    /**
     * Return channel profile information
     * @param array $pages
     *
     * API:: /v1/channel/:username
     */
    public function get($pages)
    {
        if (!isset($pages[0])) {
            return Factory::response([
                'status'=>'error',
                'message'=>'Sorry, this user could not be found',
                'type'=>'ChannelNotFoundException',
            ]);
        }

        if ($pages[0] == 'me') {
            $pages[0] = elgg_get_logged_in_user_guid();
        }

        if (is_string($pages[0]) && !is_numeric($pages[0])) {
            $pages[0] = strtolower($pages[0]);
        }

        if (is_numeric($pages[0])) {
            $user = $this->entitiesBuilder->single((int) $pages[0]);
        } else {
            $user = $this->entitiesBuilder->getByUserByIndex($pages[0]);
        }

        /**
         * If we can't find the user, and it looks like an ActivityPub style username (mark@minds.com),
         * and the current user is logged and trusted, try to fetch the profile
         */
        if (
            !$user instanceof User
            && Core\Session::getLoggedinUser()
            && Core\Session::getLoggedinUser()->isTrusted()
        ) {
            try {
                /** @var ProcessActorService */
                $processActorService = Di::_()->get(ProcessActorService::class);
                $user = $processActorService
                    ->withUsername($pages[0])
                    ->process();
            } catch (\Exception $e) {
            }
        }

        $isAdmin = Core\Session::isAdmin();
        $hasModerationPermission = Core\Session::getLoggedInUser() && Di::_()->get(RolesService::class)->hasPermission(Core\Session::getLoggedInUser(), PermissionsEnum::CAN_MODERATE_CONTENT);
        $isLoggedIn = Core\Session::isLoggedin();
        $isOwner = $isLoggedIn && ((string) Core\Session::getLoggedinUser()->guid === (string) $user->guid);
        $isPublic = $isLoggedIn && $user instanceof User && $user->isPublicDateOfBirth();

        // Flush the cache when viewing a channel page
        if ($user instanceof User) {
            $channelsManager = Di::_()->get('Channels\Manager');
            $channelsManager->flushCache($user);
        }

        if (!$user->username ||
            (Helpers\Flags::shouldFail($user) && !$isAdmin && !$hasModerationPermission)
        ) {
            return Factory::response([
                'status'=>'error',
                'message'=>'Sorry, this user could not be found',
                'type'=>'ChannelNotFoundException',
            ]);
        }

        if ($user->enabled != "yes" && !Core\Session::isAdmin() && !$hasModerationPermission) {
            return Factory::response([
                'status'=>'error',
                'message'=>'Sorry, this user is disabled',
                'type'=>'ChannelDisabledException',
            ]);
        }

        if ($user->banned == 'yes' && !Core\Session::isAdmin() && !$hasModerationPermission) {
            return Factory::response([
                'status'=>'error',
                'message'=>'This user has been banned',
                'type'=>'ChannelBannedException',
            ]);
        }

        $user->fullExport = true; //get counts
        $user->exportCounts = true;
        $return = Factory::exportable([$user]);

        $response['channel'] = $return[0];
        if (Core\Session::isLoggedIn() && Core\Session::getLoggedinUser()->guid == $user->guid) {
            $response['channel']['admin'] = $user->admin;
        }
        $response['channel']['avatar_url'] = [
            'tiny' => $user->getIconURL('tiny'),
            'small' => $user->getIconURL('small'),
            'medium' => $user->getIconURL('medium'),
            'large' => $user->getIconURL('large'),
            'master' => $user->getIconURL('master')
        ];

        $response['channel']['briefdescription'] = $response['channel']['briefdescription'] ?: '';
        $response['channel']['city'] = $response['channel']['city'] ?: "";
        $response['channel']['gender'] = $response['channel']['gender'] ?: "";

        // if we are querying for our own user

        if (
            $user->getDateOfBirth() &&
            (
                $isAdmin ||
                $isOwner ||
                $isPublic
            )
        ) {
            $response['channel']['dob'] = $user->getDateOfBirth();
        }

        if (
            $isAdmin ||
            $isOwner
        ) {
            $response['channel']['public_dob'] = $user->isPublicDateOfBirth();
        }

        //

        $carousels = Core\Entities::get(['subtype'=>'carousel', 'owner_guid'=>$user->guid]);
        if ($carousels) {
            foreach ($carousels as $carousel) {
                $response['channel']['carousels'][] = [
                  'guid' => (string) $carousel->guid,
                  'top_offset' => $carousel->top_offset,
                  'src'=> Core\Config::_()->cdn_url . "fs/v1/banners/$carousel->guid/fat/$carousel->last_updated"
                ];
            }
        }


        // The 'blocked' exported field tells the current logged in user that they have BLOCKED
        // said user, not that they are blocked. We use 'hasBlocked' vs 'isBlocked' to get
        // the inversion
        if (Core\Session::getLoggedInUser()) {
            $blockEntry = (new BlockEntry)
                ->setActor(Core\Session::getLoggedInUser())
                ->setSubject($user);
            $hasBlocked = Di::_()->get('Security\Block\Manager')->hasBlocked($blockEntry);
            $isBlocked = Di::_()->get('Security\Block\Manager')->isBlocked($blockEntry);
            $response['channel']['blocked'] = $hasBlocked;
            $response['channel']['blocked_by'] = $isBlocked;
        }

        if ($user->isPro()) {
            /** @var Core\Pro\Manager $manager */
            $manager = Core\Di\Di::_()->get('Pro\Manager');
            $manager
                ->setUser($user);

            $proSettings = $manager->get();

            if ($proSettings) {
                $response['channel']['pro_settings'] = $proSettings;
            }
        }

        if (!$this->config->get('tenant_id')) {
            $response['require_login'] = !$isLoggedIn && Di::_()->get('Blockchain\Wallets\Balance')
                ->setUser($user)
                ->count() === 0;
        } else {
            $response['require_login'] = !$isLoggedIn && $this->config->get('tenant')?->config?->walledGardenEnabled;
        }
        
        return Factory::response($response);
    }

    public function post($pages)
    {
        Factory::isLoggedIn();
        $owner = Core\Session::getLoggedinUser();
        $guid = Core\Session::getLoggedinUser()->guid;
        if (Core\Session::getLoggedinUser()->legacy_guid) {
            $guid = Core\Session::getLoggedinUser()->legacy_guid;
        }

        /** @var Core\Media\Imagick\Manager $manager */
        $manager = Core\Di\Di::_()->get('Media\Imagick\Manager');

        $response = [];

        switch ($pages[0]) {
            case "avatar":
                /** @var AvatarService */
                $avatarService = Di::_()->get('Channels\AvatarService');
                
                $success = $avatarService->withUser(Core\Session::getLoggedinUser())
                    ->createFromFile($_FILES['file']['tmp_name']);
               
                if (!$success) {
                    Factory::response([
                        'status' => 'error',
                        'message' => "Avatar could not save",
                    ]);
                    return;
                }

                break;
            case "banner":
                // Tenants upload banners through V2 banner system.
                if ((bool) Di::_()->get(Config::class)->get('tenant_id')) {
                    if (is_uploaded_file($_FILES['file']['tmp_name'])) {
                        $response['uploaded'] = Di::_()->get(BannerService::class)
                            ->upload(
                                path: $_FILES['file']['tmp_name'],
                                user: $owner
                            );
                    }
                    break;
                }
                //remove all older banners
                try {
                    $db = new Core\Data\Call('entities_by_time');
                    $db->removeRow("object:carousel:user:" . elgg_get_logged_in_user_guid());
                } catch (\Exception $e) {
                }

                $item = new \Minds\Entities\Object\Carousel();
                $item->title = '';
                $item->owner_guid = elgg_get_logged_in_user_guid();
                $item->access_id = ACCESS_PUBLIC;

                $this->save
                    ->setEntity($item)
                    ->save();

                if (is_uploaded_file($_FILES['file']['tmp_name'])) {
                    $manager->setImage($_FILES['file']['tmp_name'])
                        ->autorotate()
                        ->resize(2000, 10000);

                    $file = new Entities\File();
                    $file->owner_guid = $item->owner_guid;
                    $file->setFilename("banners/{$item->guid}.jpg");
                    $file->open('write');
                    $file->write($manager->getJpeg());
                    $file->close();

                    $response['uploaded'] = true;
                }

                break;
            case "info":
            default:
                if (!$owner->canEdit()) {
                    return Factory::response(['status'=>'error']);
                }

                $update = [];
                foreach (['website', 'briefdescription', 'gender',
                        'city', 'coordinates', 'monetized'] as $field) {
                    if (isset($_POST[$field])) {
                        $update[$field] = $_POST[$field];
                        $owner->$field = $_POST[$field];
                    }
                }

                // @throws StringLengthException
                (new BriefDescriptionLengthValidator())->validate(
                    $_POST['briefdescription'] ?? '',
                    nameOverride: 'bio'
                );

                if (isset($_POST['name'])) {
                    $maxLength = Di::_()->get('Config')->max_name_length ?? 50;
                    $trimmedName = mb_substr($_POST['name'], 0, $maxLength);

                    $update['name'] = $trimmedName;
                    $owner->name = $trimmedName;
                }

                if (isset($_POST['dob']) && preg_match('/^\d{4}-\d{2}-\d{2}/', $_POST['dob'])) {
                    $update['dob'] = $_POST['dob'];
                    $owner->setDateOfBirth($_POST['dob']);
                }

                if (isset($_POST['public_dob'])) {
                    $publicDob = (bool) $_POST['public_dob'];

                    $update['public_dob'] = $publicDob;
                    $owner->setPublicDateOfBirth($publicDob);
                }

                if (isset($_POST['nsfw']) && is_array($_POST['nsfw'])) {
                    $nsfw = array_unique(array_merge($_POST['nsfw'], $owner->getNsfwLock()));
                    $update['nsfw'] = json_encode($nsfw);
                    $owner->setNsfw($nsfw);
                }

                if (isset($_POST['tags']) && is_array($_POST['tags'])) {
                    $update['tags'] = json_encode($_POST['tags']);
                    $owner->$field = $update['tags'];
                }

                if ($owner->time_created > 1559032594) {
                    try {
                        $spam = new Core\Security\Spam();
                        $spam->check($owner);
                    } catch (\Exception $e) {
                        return Factory::response(['status'=>'error', 'message' => $e->getMessage() ]);
                    }
                }

                if (isset($_POST['mode']) && ChannelMode::isValid($_POST['mode'])) {
                    $update['mode'] = $_POST['mode'];
                }

                if (isset($_POST['social_profiles']) && is_array($_POST['social_profiles'])) {
                    $profiles = [];

                    foreach ($_POST['social_profiles'] as $profile) {
                        if (!isset($profile['key']) || !isset($profile['value'])) {
                            continue;
                        }

                        $key = $profile['key'];
                        $value = $profile['value'];

                        if (!$value || !is_string($value)) {
                            continue;
                        }

                        $profiles[] = [
                            'key' => $profile['key'],
                            'value' => $profile['value'],
                        ];
                    }

                    $owner->setSocialProfiles($profiles);
                    $update['social_profiles'] = json_encode($profiles);
                }

                //always update icon time on profile edit...
                $update['icontime'] = time();
                $owner->icontime = time();

                $this->save
                    ->setEntity($owner)
                    ->withMutatedAttributes(array_keys($update))
                    ->save();
        }

        Core\Events\Dispatcher::trigger('entities-ops', 'update', [
            'entityUrn' => $owner->getUrn()
        ]);

        return Factory::response($response);
    }

    public function put($pages)
    {
        return Factory::response([]);
    }

    /**
     * Deactivate an account
     */
    public function delete($pages)
    {
        if (!Core\Session::getLoggedinUser()) {
            return Factory::response(['status' => 'error', 'message' => 'not logged in']);
        }

        $user = Core\Session::getLoggedinUser();

        // Force two-factor confirmation.
        $twoFactorManager = Di::_()->get('Security\TwoFactor\Manager');
        $twoFactorManager->gatekeeper($user, ServerRequestFactory::fromGlobals());

        switch ($pages[0]) {
            default:
                // check ACL BEFORE changing the enabled property.
                if (!$this->acl->write($user)) {
                    throw new ForbiddenException();
                }

                $channel = $user;
                $channel->enabled = 'no';

                $ignore = $this->acl::$ignore;
                $this->acl::$ignore = true;

                $this->save
                    ->setEntity($channel)
                    ->withMutatedAttributes(['enabled'])
                    ->save();

                $this->acl::$ignore = $ignore;

                (new Core\Sessions\CommonSessions\Manager())->deleteAll($channel);
        }

        return Factory::response([]);
    }
}
