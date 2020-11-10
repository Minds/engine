<?php
namespace Minds\Core\Security\Block;

use Minds\Common\Repository\AbstractRepositoryOpts;

class BlockListOpts extends AbstractRepositoryOpts
{
    /** @var string */
    protected $userGuid;

    /** @var int */
    protected $limit = 1000;

    /** @var bool */
    protected $hydrate = false;

    /** @var bool */
    protected $useCache = true;

    /** @var string */
    protected $pagingToken = '';
}
