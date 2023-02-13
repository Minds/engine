<?php
declare(strict_types=1);

namespace Minds\Controllers\Cli;

use Minds\Cli;
use Minds\Core\Monetization\Demonetization\DemonetizationContext;
use Minds\Core\Monetization\Demonetization\Strategies\DemonetizePostStrategy;
use Minds\Core\Monetization\Demonetization\Strategies\Interfaces\DemonetizableEntityInterface;
use Minds\Core\Settings\Manager as UserSettingsManager;
use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Security\ACL;
use Minds\Core\Settings\Exceptions\UserSettingsNotFoundException;
use Minds\Entities\User;
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
                ->storeUserSettings(['plus_demonetized_ts' => null]);
            $this->out('[Demonetize CLI] User remonetized');
        } else {
            $this->userSettingsManager->setUser($user)
                ->storeUserSettings(['plus_demonetized_ts' => date('c', time())]);
            $this->out('[Demonetize CLI] User demonetized');
        }
    }

    /**
     * Check whether a user is Minds+ demonetized.
     * @example
     * - php cli.php Demonetize isPlusDemonetized --guid='1470454736242085891'
     * @return void
     */
    public function isPlusUserDemonetized(): void
    {
        $guid = $this->getOpt('guid') ?? null;

        $user = $this->entitiesBuilder->single($guid);

        if (!($user instanceof User)) {
            $this->out('[Demonetize CLI] Invalid entity');
            return;
        }

        try {
            $settings = $this->userSettingsManager
                ->setUser($user)
                ->getUserSettings();
            
            $this->out(
                $settings->isPlusDemonetized() ?
                '[Demonetize CLI] User is Minds+ demonetized' :
                '[Demonetize CLI] User is not Minds+ demonetized'
            );
        } catch (UserSettingsNotFoundException $e) {
            $this->out('[Demonetize CLI] Setting not found for user - not Minds+ demonetized');
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
