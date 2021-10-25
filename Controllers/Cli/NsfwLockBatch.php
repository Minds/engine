<?php

namespace Minds\Controllers\Cli;

use Minds\Entities\User;
use Minds\Core\Di\Di;
use Minds\Cli;
use Minds\Core\EntitiesBuilder;
use Minds\Core\EventStreams\AdminActionEvent;
use Minds\Core\EventStreams\Topics\AdminEventTopic;
use Minds\Interfaces;

/**
 * CLI Controller to push new nsfw_lock batch jobs to the admin events pulsar stream
 * Can be used to batch set and unset nsfw_lock status for a user's entities.
 */
class NsfwLockBatch extends Cli\Controller implements Interfaces\CliControllerInterface
{
    public function __construct(private ?EntitiesBuilder $entitiesBuilder = null)
    {
        $this->entitiesBuilder = $entitiesBuilder ?? Di::_()->get('EntitiesBuilder');
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

        // subject user guid
        $subjectGuid = $this->getOpt('user_guid');

        $value = $this->getOpt('value') ?
            explode(',', $this->getOpt('value')) :
            [];
        
        // since we are not running from a user context,
        // we need to pass an empty user.
        $actor = new User(null);

        if (!$subjectGuid) {
            $this->out('user_guid field must be provided.');
            exit(1);
        }

        $subject = $this->entitiesBuilder->single($subjectGuid);

        if (!$subject) {
            $this->out('User not found.');
            exit(1);
        }

        // construct and send event.
        $actionEvent = new AdminActionEvent();
        $actionEvent
            ->setAction(AdminActionEvent::ACTION_NSFW_LOCK)
            ->setActionData([
                'nsfw_lock' => $value,
            ])
            ->setActor($actor)
            ->setTimestamp(time())
            ->setSubject($subject);

        $actionEventTopic = new AdminEventTopic();
        $actionEventTopic->send($actionEvent);
    }
}
