<?php
/**
 * entities.
 *
 * @author emi
 */

namespace Minds\Controllers\api\v2;

use Minds\Api\Exportable;
use Minds\Api\Factory;
use Minds\Common\Urn;
use Minds\Core\Entities\Resolver;
use Minds\Core\Session;
use Minds\Interfaces;
use Minds\Entities\User;
use Minds\Core\Di\Di;

class entities implements Interfaces\Api
{
    /**
     * Equivalent to HTTP GET method
     * @param  array $pages
     * @return mixed|null
     */
    public function get($pages)
    {
        $asActivities = (bool) ($_GET['as_activities'] ?? false);
        $exportUserCounts = (bool) ($_GET['export_user_counts'] ?? false);
        $urns = array_map([Urn::class, '_'], array_filter(explode(',', $_GET['urns'] ?? ''), [Urn::class, 'isValid']));

        $resolver = new Resolver();
        $resolver
            ->setUser(Session::getLoggedinUser() ?: null)
            ->setUrns($urns)
            ->setOpts([
                'asActivities' => $asActivities,
            ]);

        $entities = $resolver->fetch();

        if ($exportUserCounts) {
            foreach ($entities as $user) {
                $user->exportCounts = true;
            }
        }

        // Return
        return Factory::response([
            'entities' => Exportable::_(array_values($entities)),
            'require_login' => $this->shouldRequireLogin(array_values($entities)),
        ]);
    }

    /**
     * Equivalent to HTTP POST method
     * @param  array $pages
     * @return mixed|null
     */
    public function post($pages)
    {
        return Factory::response([]);
    }

    /**
     * Equivalent to HTTP PUT method
     * @param  array $pages
     * @return mixed|null
     */
    public function put($pages)
    {
        return Factory::response([]);
    }

    /**
     * Equivalent to HTTP DELETE method
     * @param  array $pages
     * @return mixed|null
     */
    public function delete($pages)
    {
        return Factory::response([]);
    }

    /**
     * Require login
     * @return bool
     */
    public function shouldRequireLogin(array $entities): bool
    {
        $user = $entities[0] instanceof User ? $entities[0] : Di::_()->get('EntitiesBuilder')->single($entities[0]->owner_guid);

        if (!$user) {
            return false;
        }

        return !Session::isLoggedin() &&
            Di::_()->get('Blockchain\Wallets\Balance')
                ->setUser($user)
                ->count() === 0;
    }
}
