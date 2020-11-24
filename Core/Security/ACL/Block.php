<?php
/**
 * ACL: BLOCK
 *
 * !! DEPRECATED !!
 *
 * Use Core\Security\Block\Manager instead
 */
namespace Minds\Core\Security\ACL;

use Minds\Core;
use Minds\Core\Di\Di;
use Minds\Entities;
use Minds\Core\Security\Block\Manager;
use Minds\Core\Security\Block\BlockListOpts;
use Minds\Core\Security\Block\BlockEntry;

class Block
{
    private static $_;

    /** @var Manager */
    private $blockManager;

    public function __construct($blockManager = null)
    {
        $this->blockManager = $blockManager ?? Di::_()->get('Security\Block\Manager');
    }

    /**
     * Return a list of blocked users
     * @param mixed (Entities\User | string) $from
     * @param int $limit
     * @param string $offset
     * @return array (user_guids)
     */
    public function getBlockList($from = null, $limit = 9999, $offset = "")
    {
        if (!$from) {
            $from = Core\Session::getLoggedinUser();
        }

        $opts = new BlockListOpts();
        $opts->setUserGuid($from->getGuid());
        $opts->setLimit($limit);
    
        return $this->blockManager
            ->getList($opts)
            ->map(function (BlockEntry $blockEntry) {
                return $blockEntry->getSubjectGuid();
            })
            ->toArray();
    }

    /**
     * Add a user to the list of blogs
     * @param Entities\User $user - check if this user is blocked
     * @param mixed (Entities\User | string) - from this user
     */
    public function block($user, $from = null)
    {
        if (!$from) {
            $from = Core\Session::getLoggedinUser();
        }

        if ($from instanceof Entities\User) {
            $from = $from->guid;
        }

        if ($user instanceof Entities\User) {
            $user = $user->guid;
        }

        if (is_object($from)) { // Unlikely to be an user, and we cannot block anything that's not an user (yet)
            return false;
        }

        Core\Events\Dispatcher::trigger('acl:block', 'all', compact('user', 'from'));

        $blockEntry = new BlockEntry();
        $blockEntry->setActorGuid($from)
            ->setSubjectGuid($user);

        return $this->blockManager->add($blockEntry);
    }

    /**
     * Removes user to the list of blogs
     * @param Entities\User $user - check if this user is blocked
     * @param mixed (Entities\User | string) - from this user
     */
    public function unBlock($user, $from = null)
    {
        if (!$from) {
            $from = Core\Session::getLoggedinUser();
        }

        if ($from instanceof Entities\User) {
            $from = $from->guid;
        }

        if ($user instanceof Entities\User) {
            $user = $user->guid;
        }

        Core\Events\Dispatcher::trigger('acl:unblock', 'all', compact('user', 'from'));

        $blockEntry = new BlockEntry();
        $blockEntry->setActorGuid($from)
            ->setSubjectGuid($user);

        return $this->blockManager->delete($blockEntry);
    }

    public static function _()
    {
        if (!self::$_) {
            return new Block();
        }
        return self::$_;
    }
}
