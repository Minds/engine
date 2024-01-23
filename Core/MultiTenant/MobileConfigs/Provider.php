<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\MobileConfigs;

use Minds\Common\Jwt;
use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\Di\ImmutableException;
use Minds\Core\Di\Provider as DiProvider;
use Minds\Core\MultiTenant\MobileConfigs\Helpers\GitlabPipelineJwtTokenValidator;

class Provider extends DiProvider
{
    /**
     * @return void
     * @throws ImmutableException
     */
    public function register(): void
    {
        #region Controllers
        (new Controllers\ControllersProvider())->register();
        #endregion

        #region Services
        (new Services\ServicesProvider())->register();
        #endregion

        #region Repositories
        (new Repositories\RepositoriesProvider())->register();
        #endregion

        #region Deployments
        (new Deployments\Provider())->register();
        #endregion

        #region Helpers
        $this->di->bind(
            GitlabPipelineJwtTokenValidator::class,
            fn (Di $di): GitlabPipelineJwtTokenValidator => new GitlabPipelineJwtTokenValidator(
                jwt: new Jwt(),
                config: $di->get(Config::class)
            )
        );
        #endregion
    }
}
