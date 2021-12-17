<?php

namespace Minds\Core\Recommendations;

use Minds\Common\Repository\Response;

interface RepositoryInterface
{
    public function getList(?RepositoryOptions $options = null): Response;
}
