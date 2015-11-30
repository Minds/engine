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
        if (!$user->username) {
            return Factory::response(array('status'=>'error', 'message'=>'The user could not be found'));
        }

        if ($user->enabled != "yes") {
            return Factory::response(array('status'=>'error', 'message'=>'The user is disabled'));
        }

        $return = Factory::exportable(array($user));

        $response['channel'] = $return[0];
        $response['channel']['avatar_url'] = array(
            'tiny' => $user->getIconURL('tiny'),
            'small' => $user->getIconURL('small'),
            'medium' => $user->getIconURL('medium'),
            'large' => $user->getIconURL('large'),
            'master' => $user->getIconURL('master')
        );
        $response['channel']['chat'] = (bool) elgg_get_plugin_user_setting('option', elgg_get_logged_in_user_guid(), 'gatherings') == 1 ? true : false;
        $response['channel']['briefdescription'] = $response['channel']['briefdescription'] ?: '';
        $response['channel']['city'] = $response['channel']['city'] ?: "";
        $response['channel']['gender'] = $response['channel']['gender'] ?: "";
        $response['channel']['dob'] = $response['channel']['dob'] ?: "";

        $db = new Core\Data\Call('entities_by_time');
        $feed_count = $db->countRow("activity:user:" . $user->guid);
        $response['channel']['activity_count'] = $feed_count;

        $carousels = Core\Entities::get(array('subtype'=>'carousel', 'owner_guid'=>$user->guid));
        if ($carousels) {
            foreach ($carousels as $carousel) {
                $response['channel']['carousels'][] = array(
                  'guid' => (string) $carousel->guid,
                  'top_offset' => $carousel->top_offset,
                  'src'=> Core\Config::_()->cdn_url . "fs/v1/banners/$carousel->guid/fat/$carousel->last_updated"
                );
            }
        }

        $block = Core\Security\ACL\Block::_();
        $response['channel']['blocked'] = $block->isBlocked($user);

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

        $response = [];

        switch ($pages[0]) {
            case "avatar":
                $icon_sizes = Core\Config::_()->get('icon_sizes');
                // get the images and save their file handlers into an array
                // so we can do clean up if one fails.
                $files = array();
                foreach ($icon_sizes as $name => $size_info) {
                    $resized = get_resized_image_from_uploaded_file('file', $size_info['w'], $size_info['h'], $size_info['square'], $size_info['upscale']);

                    if ($resized) {
                        //@todo Make these actual entities.  See exts #348.
                        $file = new ElggFile();
                        $file->owner_guid = Core\Session::getLoggedinUser()->guid;
                        $file->setFilename("profile/{$guid}{$name}.jpg");
                        $file->open('write');
                        $file->write($resized);
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

                // reset crop coordinates
                $owner->x1 = 0;
                $owner->x2 = 0;
                $owner->y1 = 0;
                $owner->y2 = 0;

                $owner->icontime = time();
                $owner->save();
                break;
            case "banner":
                //remove all older banners
                try {
                    $db = new Core\Data\Call('entities_by_time');
                    $db->removeRow("object:carousel:user:" . elgg_get_logged_in_user_guid());
                } catch (\Exception $e) {
                }

                $item = new \Minds\Entities\Carousel();
                $item->title = '';
                $item->owner_guid = elgg_get_logged_in_user_guid();
                $item->access_id = ACCESS_PUBLIC;
                $item->save();
                $files = array();
                $sizes = array(
                    'thin' => array(
                        'w' => 2000,
                        'h' => 800,
                        'square' => false,
                        'upscale' => true
                        ),
                    'fat' => array(
                        'w' => 2000,
                        // 'h' => 800,
                        'square' => false,
                        'upscale' => true
                        )
                    );
                foreach ($sizes as $name => $size_info) {
                    global $CONFIG;
                    $theme_dir = $CONFIG->dataroot . 'carousel/';
                    $dimensions = getimagesize($_FILES["file"]['tmp_name']);
                    $h = $dimensions[1];
                    $x1 = 0;
                    $x2 = $dimensions[0];
                    if ($h <= 800 || $size_info['h'] != 800) {
                        $y1 = 0;
                        $y2 = $h;
                    } else {
                        $y1 = $h/3;
                        $y2 = $h-($h/3);
                    }
                    $resized = get_resized_image_from_existing_file($_FILES["file"]['tmp_name'], $size_info['w'], $size_info['h'], $size_info['square'], $x1, $y1, $x2, $y2, $size_info['upscale'], 'jpeg', 80);
                    if ($resized) {
                        @mkdir($theme_dir);
                        file_put_contents($theme_dir . $item->guid . $name, $resized);
                    }
                    if (isset($_FILES["file"]) && ($_FILES["file"]['error'] != UPLOAD_ERR_NO_FILE) && $_FILES["file"]['error'] != 0) {
                        // register_error(minds_themeconfig_codeToMessage($_FILES['logo']['error'])); // Debug uploads
                    }
                    $item->last_updated = time();
                    $item->background = true;
                }
                $item->save();
                break;
            case "carousel":
              $item = new \Minds\Entities\Object\Carousel(isset($_POST['guid']) ? $_POST['guid'] : null);
              $item->access_id = ACCESS_PUBLIC;
              $item->top_offset = $_POST['top'];
              $item->save();

              $response['carousel'] = array(
                 'guid' => (string) $item->guid,
                 'top_offset' => $item->top_offset,
                 'src'=> Core\Config::build()->cdn_url . "fs/v1/banners/$item->guid/fat/$item->last_updated"
              );

              if (is_uploaded_file($_FILES['file']['tmp_name'])) {
                  $resized = get_resized_image_from_uploaded_file('file', 2000);
                  $file = new Entities\File();
                  $file->owner_guid = $item->owner_guid;
                  $file->setFilename("banners/{$item->guid}.jpg");
                  $file->open('write');
                  $file->write($resized);
                  $file->close();

                  $response['uploaded'] = true;
              }


              break;
            case "info":
            default:
                if (!$owner->canEdit()) {
                    return Factory::response(array('status'=>'error'));
                }
                $update = array();
                foreach (array('name', 'website', 'briefdescription', 'gender', 'dob', 'city', 'coordinates') as $field) {
                    if (isset($_POST[$field])) {
                        $update[$field] = $_POST[$field];
                    }
                }

                if (isset($_POST['coordinates'])) {
                    //update neo4j with our coordinates
                    $prepared = new Core\Data\Neo4j\Prepared\Common();
                    list($lat, $lon) = explode(',', $_POST['coordinates']);
                    $result = Core\Data\Client::build('Neo4j')->request($prepared->updateEntity($owner, array('lat'=> (double) $lat, 'lon'=> (double) $lon)));
                    $rows = $result->getRows();
                    $id = $rows["id(entity)"][0];
                    error_log(print_r($id, true));
                    Core\Data\Client::build('Neo4j')->client()->geoLink($id);
                }
                $db = new Core\Data\Call('entities');
                $db->insert($owner->guid, $update);
                //update session also
                Core\Session::regenerate();
       }

        return Factory::response($response);
    }

    public function put($pages)
    {
        return Factory::response(array());
    }

    /**
     * Deactivate an account
     */
    public function delete($pages)
    {
        if (!Core\Session::getLoggedinUser()) {
            return Factory::response(array('status'=>'error', 'message'=>'not logged in'));
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
        }

        return Factory::response(array());
    }
}
