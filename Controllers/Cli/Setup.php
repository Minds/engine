<?php
declare(strict_types=1);

namespace Minds\Controllers\Cli;

use Minds\Cli\Controller as CliController;
use Minds\Common\SystemUser;
use Minds\Core\Di\Di;
use Minds\Core\Entities\Actions\Save;
use Minds\Core\Log\Logger;
use Minds\Interfaces\CliControllerInterface;

class Setup extends CliController implements CliControllerInterface
{
    public function __construct(
        private ?Logger $logger = null
    ) {
        $this->logger ??= Di::_()->get('Logger');
    }

    /**
     * @inheritDoc
     */
    public function help($command = null): void
    {
        $this->logger->info('TBD');
    }

    /**
     * @inheritDoc
     */
    public function exec(): void
    {
        // TODO: Implement exec() method.
    }

    public function initSystemUser(): void
    {
        $user = new SystemUser();
        $user->set('username', 'system_user');
        $user->setAccessId('2');

        $saved = (new Save())
            ->setEntity($user)
            ->save();
        $this->logger->info("System User saved: " . $saved);
    }
}
