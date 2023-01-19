<?php
declare(strict_types=1);

namespace Minds\Core\Verification;

use Minds\Core\Di\Di;
use Minds\Core\Di\ImmutableException;
use Minds\Core\Di\Provider as DiProvider;
use Minds\Core\Verification\Helpers\OCR\GoogleVisionOCRClient;
use Minds\Core\Verification\Helpers\OCR\MindsOCRInterface;

class Provider extends DiProvider
{
    /**
     * @return void
     * @throws ImmutableException
     */
    public function register(): void
    {
        Di::_()->bind('Verification\Controller', function ($di): Controller {
            return new Controller();
        });
        Di::_()->bind('Verification\Manager', function ($di): Manager {
            return new Manager();
        });
        Di::_()->bind('Verification\Repository', function ($di): Repository {
            return new Repository();
        });

        /**
         * Helpers
         */
        Di::_()->bind('Verification\Helpers\OCR\DefaultOCRClient', function ($di): MindsOCRInterface {
            return new GoogleVisionOCRClient();
        });
    }
}
