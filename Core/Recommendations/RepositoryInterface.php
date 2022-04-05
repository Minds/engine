<?php

namespace Minds\Core\Recommendations;

use Minds\Common\Repository\Response;
use Minds\Exceptions\UserErrorException;

interface RepositoryInterface
{
    /**
     * Returns a list of entities
     * @param array|null $options
     * @return Response
     */
    public function getList(?array $options = null): Response;
}
