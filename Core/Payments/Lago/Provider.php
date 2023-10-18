<?php
declare(strict_types=1);

namespace Minds\Core\Payments\Lago;
;

use Minds\Core\Di\Di;
use Minds\Core\Di\ImmutableException;
use Minds\Core\Di\Provider as DiProvider;
use Minds\Core\Payments\Lago\Controllers\Controller;
use Minds\Core\Payments\Lago\Types\InputTypesProvider;

class Provider extends DiProvider
{
    /**
     * @return void
     * @throws ImmutableException
     */
    public function register(): void
    {
        ###### Controllers ######
        (new Controllers\ControllersProvider())->register();

        ###### Webhook Controller ######
        $this->di->bind(WebhookController::class, function (Di $di): WebhookController {
            return new WebhookController();
        });

        ###### Services ######
        (new Services\ServicesProvider())->register();

        ###### Clients ######
        (new Clients\ClientsProvider())->register();

        ###### GraphQL Input Types Factories ######
        (new InputTypesProvider())->register();
    }
}
