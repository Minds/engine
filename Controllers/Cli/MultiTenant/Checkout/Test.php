<?php
declare(strict_types=1);

namespace Minds\Controllers\Cli\MultiTenant\Checkout;

use Minds\Cli\Controller;
use Minds\Core\Di\Di;
use Minds\Core\MultiTenant\Services\CheckoutService;
use Minds\Interfaces\CliControllerInterface;
use TheCodingMachine\GraphQLite\Exceptions\GraphQLException;

class Test extends Controller implements CliControllerInterface
{
    public function help($command = null)
    {
        // TODO: Implement help() method.
    }

    /**
     * @return void
     * @throws GraphQLException
     */
    public function exec(): void
    {
        /**
         * @var CheckoutService $checkoutService
         */
        // $checkoutService = Di::_()->get(CheckoutService::class);

        // $checkoutService->testStrapiIntegration('networks_community');
    }
}
