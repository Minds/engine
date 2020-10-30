<?php
/**
 * Minds Wire Api endpoint.
 *
 * @version 1
 *
 * @author Mark Harding
 */

namespace Minds\Controllers\api\v1;

use Minds\Api\Factory;
use Minds\Core;
use Minds\Core\Router\Exceptions\UnverifiedEmailException;
use Minds\Core\Util\BigNumber;
use Minds\Core\Wire\Exceptions\WalletNotSetupException;
use Minds\Entities;
use Minds\Interfaces;

class wire implements Interfaces\Api
{
    public function get($pages)
    {
        $response = [];

        return Factory::response($response);
    }

    /**
     * Send a wire to someone.
     *
     * @param array $pages
     *
     * API:: /v1/wire/:guid
     */
    public function post($pages)
    {
        return Factory::response([]);
    }

    public function put($pages)
    {
    }

    public function delete($pages)
    {
    }
}
