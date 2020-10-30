<?php
namespace Minds\Core\Security\Block;

use Minds\Common\Repository\AbstractRepositoryOpts;

class BlockListOpts extends AbstractRepositoryOpts
{
    /** @var string */
    protected $userGuid;

    /** @var int */
    protected $limit = 500;

    /** @var bool */
    protected $hydrate = false;

    /** @var bool */
    protected $useCache = true;
}
