<?php
/**
 * Minds Entities API
 *
 * @version 1
 * @author Mark Harding
 */
namespace Minds\Controllers\api\v1;

use Minds\Core;
use Minds\Interfaces;
use Minds\Api\Factory;

class entities implements Interfaces\Api
{
    /**
     * Returns the entities
     * @param array $pages
     *
     * @SWG\GET(
     *     tags={"entities"},
     *     summary="Returns basic entities",
     *     path="/v1/entities",
     *     @SWG\Parameter(
     *      name="type",
     *      in="query",
     *      description="Type (eg. object, user, activity)",
     *      required=false,
     *      type="string"
     *     ),
     *     @SWG\Parameter(
     *      name="subtype",
     *      in="query",
     *      description="Subtype (eg. video, image, blog)",
     *      required=false,
     *      type="string"
     *     ),
     *     @SWG\Parameter(
     *      name="owner_guid",
     *      in="query",
     *      description="The owner of the content to return",
     *      required=false,
     *      type="integer"
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
        $type = "object";
        $subtype = null;
        $owner = null;
        switch ($pages[0]) {
            case "all":
                $type="user";
                break;
            case "owner":
                if (!is_numeric($pages[2])) {
                    $lookup = new Core\Data\lookup();
                    $pages[2] = key($lookup->get(strtolower($pages[2])));
                }
                $owner = isset($pages[2]) && is_numeric($pages[2]) ? $pages[2] : Core\Session::getLoggedInUser()->guid;

                $subtype = "archive";
                break;
        }

        if ($pages[1] != "all") {
            switch ($pages[1]) {
                case "images":
                    $type = "object";
                    $subtype = "image";
                    break;
                case "albums":
                    $type = "object";
                    $subtype = "album";
                    break;
                case "videos":
                    $type = "object";
                    $subtype = "video";
              }
        }

        //the allowed, plus default, options
        $options = array(
            'type' => $type,
            'subtype' => $subtype,
            'owner_guid'=> $owner,
            'limit'=>12,
            'offset'=>''
            );

        foreach ($options as $key => $value) {
            if (isset($_GET[$key])) {
                $options[$key] = $_GET[$key];
            }
        }


        $entities = Core\Entities::get($options);
        $response = array();
        if ($entities) {
            $response['entities'] = factory::exportable($entities);
            $response['load-next'] = (string) end($entities)->guid;
            $response['load-previous'] = (string) key($entities)->guid;
        }

        return Factory::response($response);
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
