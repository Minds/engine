<?php
/**
 * Sync block lists with matrix
 */
namespace Minds\Core\Matrix;

use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Matrix;
use Minds\Core\Security\Block;
use Minds\Core\Security\Block\BlockListOpts;
use Minds\Entities\User;
use Minds\Core\Log\Logger;

class BlockListSync
{
    /** @var Matrix\Manager */
    protected $matrixManager;

    /** @var Block\Manager */
    protected $blockManager;

    /** @var EntitiesBuilder */
    protected $entitiesBuilder;

    /** @var Logger */
    protected $logger;

    public function __construct($matrixManager = null, $blockManager = null, $entitiesBuilder = null)
    {
        $this->matrixManager = $matrixManager ?? Di::_()->get('Matrix\Manager');
        $this->blockManager = $blockManager ?? Di::_()->get('Security\Block\Manager');
        $this->entitiesBuilder = $entitiesBuilder ?? Di::_()->get('EntitiesBuilder');
        $this->logger = $logger ?? Di::_()->get('Logger');
    }
 
    /**
     * Sync block lists
     * @param User $user
     */
    public function sync(User $user): void
    {
        $this->logger->info('Syncing block list for ' . $user->getGuid());

        /** @var string[] */
        $blockedIds = [];

        // Get our current block list
        $blockListOpts = new BlockListOpts();
        $blockListOpts->setUserGuid($user->getGuid());
        $blockListOpts->setUseCache(false);
        $blockListOpts->setLimit(5000);

        foreach ($this->blockManager->getList($blockListOpts) as $blockEntry) {
            /** @var User */
            $subject = $this->entitiesBuilder->single($blockEntry->getSubjectGuid());
            
            if ($subject instanceof User && $subject->username) {
                $blockedIds[] = $this->matrixManager->getMatrixId($subject);
            }
        }

        // Submit it to matrix
        $this->matrixManager->syncBlockList($user, $blockedIds);

        $this->logger->info('Completed sycing block list for ' . $user->getGuid() . ' with ' . count($blockedIds) . ' entries');
    }
}
