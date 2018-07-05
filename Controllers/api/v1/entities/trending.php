<?php
/**
 * Minds Trending API
 *
 * @version 1
 * @author Mark Harding
 */
namespace Minds\Controllers\api\v1\entities;

use Minds\Core;
use Minds\Core\Di\Di;
use Minds\Entities;
use Minds\Interfaces;
use Minds\Api\Factory;

class trending implements Interfaces\Api, Interfaces\ApiIgnorePam
{
    /**
     * Returns the entities
     * @param array $pages
     *
     * @SWG\GET(
     *     tags={"entities"},
     *     summary="Returns trending entities",
     *     path="/v1/entities/trending/{type}/{subtype}",
     *     @SWG\Parameter(
     *      name="type",
     *      in="path",
     *      description="Type (eg. object, user, activity)",
     *      required=false,
     *      type="string"
     *     ),
     *     @SWG\Parameter(
     *      name="subtype",
     *      in="path",
     *      description="Subtype (eg. video, image, blog)",
     *      required=false,
     *      type="string"
     *     ),
     *     @SWG\Parameter(
     *      name="limit",
     *      in="query",
     *      description="Limit the number of returned entities",
     *      required=false,
     *      type="integer"
     *     ),
     *     @SWG\Parameter(
     *      name="offset",
     *      in="query",
     *      description="Pagination. Include the entity guid to start the list from",
     *      required=false,
     *      type="integer"
     *     ),
     *     @SWG\Response(name="200", description="Array")
     * )
     */
    public function get($pages)
    {
        //temp hack..
        //if(isset($pages[1]) && $pages[1] == 'video')
          //  $pages[1] = 'kaltura_video';
        if (!isset($pages[1])) {
            $pages[1] = $pages[0];
        }
        $key = "";
        switch ($pages[1]) {
            case "image":
            case "images":
                $key = "images";
                break;
            case "videos":
            case "video":
                $key = "videos";
                break;
            case "channels":
            case "channel":
            case "users":
            case "user":
                $key = "channels";
                break;
            case "blog":
            case "blogs":
                $key = "blogs";
                break;
            case "group":
            case "groups":
                $key = "groups";
                break;
            case "activity":
            case "activities":
            case "newsfeed":
                $key = "newsfeed";
                break;
        }

        if (!$key) {
            return Factory::response([
                'status' => 'error'
            ]);
        }

        $offset = get_input('offset', 0);
        if ($offset && strlen($offset) < 15) {
            $offset = (new \GUID())->migrate($offset);
        }

        $limit = isset($_GET['limit']) && $_GET['limit'] < 50 ? $_GET['limit'] : 12;

        $result = Di::_()->get('Trending\Repository')->getList([
            'type' => $key,
            'rating' => isset($_GET['rating']) ? (int) $_GET['rating'] : 1,
            'limit' => $limit,
            'offset' => $offset
        ]);

        if (!$result) {
            return Factory::response([
                'entities' => []
            ]);
        }

        ksort($result['guids']);
        $entities = core\Entities::get(array('guids'=>$result['guids']));
        $response['entities'] = Factory::exportable($entities);
        $response['load-next'] = base64_encode($result['token']);

        return Factory::response($response);

        // if (!isset($_GET['load-next']) && isset($_GET['offset'])) {
        //     $_GET['load-next'] = $_GET['offset'];
        // }

        // if ($entities) {
        //     $response['entities'] = factory::exportable($entities);
        //     //$response['load-next'] = isset($_GET['load-next']) ? count($entities) + $_GET['load-next'] : count($entities);
        //     $response['load-previous'] = isset($_GET['load-previous']) ? $_GET['load-previous'] - count($entities) : 0;
        // }

        // return Factory::response($response);
    }

    public function post($pages)
    {
    }

    public function put($pages)
    {
    }

    public function delete($pages)
    {
    }
}
