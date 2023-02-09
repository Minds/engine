<?php
declare(strict_types=1);

namespace Minds\Controllers\Cli;

use Minds\Cli;
use Minds\Core\Demonetization\DemonetizationContext;
use Minds\Core\Demonetization\Strategies\DemonetizePostStrategy;
use Minds\Core\Demonetization\Strategies\Interfaces\DemonetizableEntityInterface;
use Minds\Core\Settings\Manager as UserSettingsManager;
use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Security\ACL;
use Minds\Interfaces;

class Demonetize extends Cli\Controller implements Interfaces\CliControllerInterface
{
    public function __construct(
        private ?EntitiesBuilder $entitiesBuilder = null,
        private ?DemonetizationContext $demonetizationContext = null,
        private ?DemonetizePostStrategy $demonetizePostStrategy = null,
        private ?UserSettingsManager $userSettingsManager = null,
    ) {
        $this->entitiesBuilder ??= Di::_()->get('EntitiesBuilder');
        $this->demonetizationContext ??= Di::_()->get(DemonetizationContext::class);
        $this->demonetizePostStrategy ??= Di::_()->get(DemonetizePostStrategy::class);
        $this->userSettingsManager ??= Di::_()->get('Settings\Manager');
    }

    public function help($command = null)
    {
        $this->out('Usage: cli Demonetize [fn] [args]');
    }

    public function exec()
    {
        $this->help();
    }

    /**
     * Demonetize a plus user.
     * @example
     * - php cli.php Demonetize plusUser --guid='1470454736242085891'
     * - php cli.php Demonetize plusUser --guid='1470454736242085891' --remonetize
     * @return void
     */
    public function plusUser(): void
    {
        $guid = $this->getOpt('guid') ?? null;
        $remonetize = $this->getOpt('remonetize') ?? false;

        $user = $this->entitiesBuilder->single($guid);

        if (!($user instanceof DemonetizableEntityInterface)) {
            $this->out('[Demonetize CLI] Invalid entity');
            return;
        }

        if ($remonetize) {
            $this->userSettingsManager->setUser($user)
            ->storeUserSettings(['plus_demonetized' => false]);
            $this->out('[Demonetize CLI] User remonetized');
        } else {
            $this->userSettingsManager->setUser($user)
                ->storeUserSettings(['plus_demonetized' => true]);
            $this->out('[Demonetize CLI] User demonetized');
        }
    }

    /**
     * Demonetize a post.
     * @example
     * - php cli.php Demonetize post --guid='1470454736242085891'
     * - php cli.php Demonetize post --guid='1470454736242085891'
     * @return void
     */
    public function post(): void
    {
        $guid = $this->getOpt('guid') ?? null;

        $entity = $this->entitiesBuilder->single($guid);
        if (!($entity instanceof DemonetizableEntityInterface)) {
            $this->out('[Demonetize CLI] Invalid entity');
            return;
        }
        
        try {
            ACL::$ignore = true;
            $this->demonetizationContext->withStrategy($this->demonetizePostStrategy)
                ->execute($entity);
        } catch (\Exception $e) {
            $this->out('[Demonetize CLI] An error has occurred');
        } finally {
            ACL::$ignore = false;
        }

        $this->out('[Demonetize CLI] Demonetized entity');
    }
}
