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

class channel implements Interfaces\Api
{
    /**
     * Return channel profile information
     * @param array $pages
     *
     * API:: /v1/channel/:username
     */
    public function get($pages)
    {
        if ($pages[0] == 'me') {
            $pages[0] = elgg_get_logged_in_user_guid();
        }

        if (is_string($pages[0]) && !is_numeric($pages[0])) {
            $pages[0] = strtolower($pages[0]);
        }

        $user = new Entities\User($pages[0]);

        $isAdmin = Core\Session::isAdmin();
        $isLoggedIn = Core\Session::isLoggedin();
        $isOwner = $isLoggedIn && ((string) Core\Session::getLoggedinUser()->guid === (string) $user->guid);
        $isPublic = $isLoggedIn && $user->isPublicDateOfBirth();

        // Flush the cache when viewing a channel page
        $channelsManager = Di::_()->get('Channels\Manager');
        $channelsManager->flushCache($user);

        if (!$user->username ||
            (Helpers\Flags::shouldFail($user) && !$isAdmin)
        ) {
            return Factory::response([
                'status'=>'error',
                'message'=>'Sorry, this user could not be found',
                'type'=>'ChannelNotFoundException',
            ]);
        }

        if ($user->enabled != "yes" && !Core\Session::isAdmin()) {
            return Factory::response([
                'status'=>'error',
                'message'=>'Sorry, this user is disabled',
                'type'=>'ChannelDisabledException',
            ]);
        }

        if ($user->banned == 'yes' && !Core\Session::isAdmin()) {
            return Factory::response([
                'status'=>'error',
                'message'=>'This user has been banned',
                'type'=>'ChannelBannedException',
            ]);
        }

        Di::_()->get('Referrals\Cookie')
            ->setEntity($user)
            ->create();

        $user->fullExport = true; //get counts
        $user->exportCounts = true;
        $return = Factory::exportable([$user]);

        $response['channel'] = $return[0];
        if (Core\Session::getLoggedinUser()->guid == $user->guid) {
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

        if (!$user->merchant || !$supporters_count) {
            $db = new Core\Data\Call('entities_by_time');
            //$feed_count = $db->countRow("activity:user:" . $user->guid);
            $response['channel']['activity_count'] = $feed_count;
        }

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

        $response['require_login'] = !$isLoggedIn && Di::_()->get('Blockchain\Wallets\Balance')
            ->setUser($user)
            ->count() === 0;

        $response['foo'] = 'bar';
        
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
                $icon_sizes = Core\Config::_()->get('icon_sizes');
                // get the images and save their file handlers into an array
                // so we can do clean up if one fails.
                $files = [];
                foreach ($icon_sizes as $name => $size_info) {
                    $manager->setImage($_FILES['file']['tmp_name'])
                        ->autorotate()
                        ->resize($size_info['w'], $size_info['h'], $size_info['upscale'], $size_info['square']);

                    if ($blob = $manager->getJpeg()) {
                        //@todo Make these actual entities.  See exts #348.
                        $file = new ElggFile();
                        $file->owner_guid = Core\Session::getLoggedinUser()->guid;
                        $file->setFilename("profile/{$guid}{$name}.jpg");
                        $file->open('write');
                        $file->write($blob);
                        $file->close();
                        $files[] = $file;
                    } else {
                        // cleanup on fail
                        foreach ($files as $file) {
                            $file->delete();
                        }

                        return Factory::response([
                          'status' => 'error',
                          'message' => 'Could not resize'
                        ]);
                    }
                }

                $db = new Core\Data\Call('entities');
                $db->insert($owner->guid, [
                    'x1' => 0,
                    'x2' => 0,
                    'y1' => 0,
                    'y2' => 0,
                    'icontime' => time(),
                    'last_avatar_upload' => time(),
                ]);

                break;
            case "banner":
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
                $item->save();

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
            case "carousel":
              $item = new \Minds\Entities\Object\Carousel(isset($_POST['guid']) ? $_POST['guid'] : null);
              $item->access_id = ACCESS_PUBLIC;
              $item->top_offset = $_POST['top'];
              $item->last_updated = time();
              $item->save();

              $response['carousel'] = [
                 'guid' => (string) $item->guid,
                 'top_offset' => $item->top_offset,
                 'src'=> Core\Config::build()->cdn_url . "fs/v1/banners/$item->guid/fat/$item->last_updated"
              ];

              if ($item->canEdit() && is_uploaded_file($_FILES['file']['tmp_name'])) {
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
                foreach (['name', 'website', 'briefdescription', 'gender',
                        'city', 'coordinates', 'monetized'] as $field) {
                    if (isset($_POST[$field])) {
                        $update[$field] = $_POST[$field];
                        $owner->$field = $_POST[$field];
                    }
                }

                if (isset($_POST['dob'])) {
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

                $db = new Core\Data\Call('entities');
                $db->insert($owner->guid, $update);
       }

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

        switch ($pages[0]) {
            case "carousel":
                $db = new Core\Data\Call('entities_by_time');
                //  $db->removeAttributes("object:carousel:user:" . elgg_get_logged_in_user_guid());
                $item = new \Minds\Entities\Object\Carousel($pages[1]);
                $item->delete();
                break;
            default:
                $channel = Core\Session::getLoggedinUser();
                $channel->enabled = 'no';
                $channel->save();

                (new Core\Data\Sessions())->destroyAll($channel->guid);
        }

        return Factory::response([]);
    }
}
