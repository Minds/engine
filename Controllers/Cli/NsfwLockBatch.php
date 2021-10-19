<?php

namespace Minds\Controllers\Cli;

use Minds\Core;
use Minds\Cli;
use Minds\Interfaces;

/**
 * CLI Controller to push new nsfw_lock batch jobs to the NsfwLockBatch queue.
 * Can be used to batch set and unset nsfw_lock status for a user's entities.
 */
class NsfwLockBatch extends Cli\Controller implements Interfaces\CliControllerInterface
{
    public function __construct()
    {
    }

    public function help($command = null)
    {
        $this->out("example usage: php cli.php NsfwLockBatch exec --user_guid='999999999999' --value=1,2,3");
    }
    
    /**
     * Execute a batch job to modify a users entities nsfw_lock.
     * values param can either be
     * - empty, for un-setting nsfw_lock status.
     * - a comma separated list, e.g. 1,2,3
     *
     * example usage:
     * // set all of the user's entities to have an nsfw_lock of `[1, 2, 3]`.
     * php cli.php NsfwLockBatch exec --user_guid='999999999999' --value=1,2,3
     *
     * // set all of a user's entities to have an empty nsfw_lock of `[]`.
     * php cli.php NsfwLockBatch exec --user_guid='999999999999'
     */
    public function exec()
    {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);

        $userGuid = $this->getOpt('user_guid');

        $value = $this->getOpt('value') ?
            explode(',', $this->getOpt('value')) :
            [];
    
        if (!$userGuid) {
            $this->out('user_guid field must be provided.');
            exit(1);
        }

        /** @var Core\Queue\Interfaces\QueueClient $queueClient */
        $queueClient = Core\Queue\Client::build();

        $queueClient
            ->setQueue('NsfwLockBatch')
            ->send([
                'user_guid' => $userGuid,
                'value' => $value,
            ]);
    }
}
