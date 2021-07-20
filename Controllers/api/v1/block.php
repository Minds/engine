<?php
/**
 * Minds Subscriptions
 *
 * @version 1
 * @author Mark Harding
 */

namespace Minds\Controllers\api\v1;

use Minds\Api\Exportable;
use Minds\Components\Controller;
use Minds\Core;
use Minds\Helpers;
use Minds\Entities;
use Minds\Interfaces;
use Minds\Api\Factory;
use Minds\Core\Security\Block\Manager;
use Minds\Core\Security\Block\BlockEntry;
use Minds\Core\Security\Block\BlockListOpts;

class block extends Controller implements Interfaces\Api
{
    /**
     * Return a list of your blocked users
     */
    public function get($pages)
    {
        $response = [];

        if (!isset($pages[0])) {
            $pages[0] = "list";
        }

        switch ($pages[0]) {
            case "list":
                $sync = $_GET['sync'] ?? false;
                $limit = abs(intval($_GET['limit'] ?? 12));

                if ($sync && $limit > 10000) {
                    $limit = 10000;
                } elseif (!$sync && $limit > 120) {
                    $limit = 120;
                }

                $offset = $_GET['offset'] ?? '';

                /** @var Manager */
                $blockManager = $this->di->get('Security\Block\Manager');

                $opts = new BlockListOpts();
                $opts->setUserGuid(Core\Session::getLoggedinUserGuid());
                $opts->setUseCache(false);
                $opts->setLimit($limit);
                $opts->setPagingToken($offset);

                $list = $blockManager->getList($opts);
                $guids = $list->map(function ($blockEntry) {
                    return $blockEntry->getSubjectGuid();
                })->toArray();
                
                if ($sync) {
                    $guids = $list->map(function ($blockEntry) {
                        return $blockEntry->getSubjectGuid();
                    })->toArray();
                    $response['guids'] = Helpers\Text::buildArray($guids);
                } elseif ($guids) {
                    $aclManager = new Core\Security\ACL();
                    // ACL read needs to be bypassed so we can see who we have blocked
                    $ia =  $aclManager->setIgnore(true);
                    $entities = Core\Entities::get(['guids' => $guids]);
                    $aclManager->setIgnore($ia);
                    $response['entities'] = Exportable::_($entities);
                    $response['load-next'] = $list->getPagingToken();
                }
                break;
            case is_numeric($pages[0]):
                /** @var Manager */
                $blockManager = $this->di->get('Security\Block\Manager');
        
                $blockEntry = (new BlockEntry())
                    ->setActorGuid(Core\Session::getLoggedinUserGuid())
                    ->setSubject($pages[0]);

                /** @var bool */
                $hasBlocked = $blockManager->hasBlocked($blockEntry);
                $response['blocked'] = $hasBlocked;
                break;
        }


        return Factory::response($response);
    }

    /**
     *
     */
    public function post($pages)
    {
        return Factory::response([]);
    }

    /**
     * Block a user
     */
    public function put($pages)
    {
        Factory::isLoggedIn();

        $target = new Entities\User($pages[0]);

        if (!$target || !$target->guid || $target->isAdmin()) {
            return Factory::response([
                'status' => 'error',
                'message' => 'Invalid target'
            ]);
        }

        $block = $this->di->get('Security\ACL\Block');
        $blocked = $block->block($target->guid);

        if ($blocked) {
            // Unsubscribe self
            if (Core\Session::getLoggedInUser()->isSubscribed($target->guid)) {
                Core\Session::getLoggedInUser()->unSubscribe($target->guid);
            }

            // Unsubscribe target
            if ($target->isSubscribed(Core\Session::getLoggedInUser()->guid)) {
                $target->unSubscribe(Core\Session::getLoggedInUserGuid());
            }
        }

        return Factory::response([]);
    }

    /**
     * UnBlock a user
     */
    public function delete($pages)
    {
        Factory::isLoggedIn();

        $block = $this->di->get('Security\ACL\Block');
        $block->unBlock($pages[0]);

        return Factory::response([]);
    }
}
