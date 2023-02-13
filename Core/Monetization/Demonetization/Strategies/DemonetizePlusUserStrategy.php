<?php
declare(strict_types=1);

namespace Minds\Core\Monetization\Demonetization\Strategies;

use Minds\Core\Di\Di;
use Minds\Core\Settings\Manager as SettingsManager;
use Minds\Core\Monetization\Demonetization\Strategies\Interfaces\DemonetizationStrategyInterface;
use Minds\Core\Monetization\Demonetization\Strategies\Interfaces\DemonetizableEntityInterface;
use Minds\Entities\User;
use Minds\Exceptions\ServerErrorException;

/**
 * Strategy to demonetize a users ability to post to plus.
 */
class DemonetizePlusUserStrategy implements DemonetizationStrategyInterface
{
    public function __construct(private ?SettingsManager $settingsManager = null)
    {
        $this->settingsManager ??= Di::_()->get('Settings\Manager');
    }

    /**
     * Execute strategy to save plus_demonetized_ts as current timestamp in the users settings.
     * @param DemonetizableEntityInterface $entity - user entity to demonetize - will throw an exception if you do not pass a user.
     * @throws ServerErrorException - when entity is not an instance of User.
     * @return boolean true on success.
     */
    public function execute(DemonetizableEntityInterface $entity): bool
    {
        if (!$entity instanceof User) {
            throw new ServerErrorException('Invalid entity passed to demonetize plus user strategy');
        }

        $this->settingsManager->setUser($entity)
            ->storeUserSettings(['plus_demonetized_ts' => date('c', time())]);

        return true;
    }
}
