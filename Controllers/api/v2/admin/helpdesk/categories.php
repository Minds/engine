<?php


namespace Minds\Controllers\api\v2\admin\helpdesk;

use Minds\Interfaces\Api;
use Minds\Interfaces\ApiAdminPam;
use Minds\Core\Helpdesk\Entities\Category;
use Minds\Api\Factory;
use Minds\Core\Di\Di;
use Minds\Core\Helpdesk\Repository;

class categories implements Api, ApiAdminPam
{
    public function get($pages)
    {
        return Factory::response([]);
    }

    public function post($pages)
    {
        /** @var \Minds\Core\Helpdesk\Category\Repository $repo */
        $repo = Di::_()->get('Helpdesk\Category\Repository');

        try {
            $title = $this->getParam('title', 'title must be provided');
            $parent_uuid = $this->getParam('parent_uuid');

            $entity = new Category();
            $entity->setTitle($title)
                ->setParentUuid($parent_uuid);

            $repo->add($entity);

        } catch (\Exception $e) {
            return Factory::response(['status' => 'error', 'message' => $e->getMessage()]);
        }

        return Factory::response([]);
    }

    public function put($pages)
    {
        return Factory::response([]);
    }

    public function delete($pages)
    {
        $category_uuid = $pages[0];

        if (!$category_uuid) {
            return Factory::response(['status' => 'error', 'message' => 'category_uuid must be provided']);
        }

        /** @var \Minds\Core\Helpdesk\Category\Repository $repo */
        $repo = Di::_()->get('Helpdesk\Category\Repository');

        $done = $repo->delete($category_uuid);

        return Factory::response([
            'status' => 'success',
            'done' => $done
        ]);
    }

    protected function getParam($param, $error = null)
    {
        if (!isset($_POST[$param]) && $error) {
            throw new \Exception($error);
        }
        return $_POST[$param] ?: null;
    }

}