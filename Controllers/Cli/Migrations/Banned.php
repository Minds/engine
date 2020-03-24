<?php
/**
 * @author: eiennohi.
 */

namespace Minds\Controllers\Cli\Migrations;

use Cassandra\Rows;
use Minds\Cli;
use Minds\Core\Data\Cassandra\Client;
use Minds\Core\Data\Cassandra\Prepared\Custom;
use Minds\Core\Di\Di;
use Minds\Core\Events\EventsDispatcher;
use Minds\Entities\User;
use Minds\Exceptions;
use Minds\Interfaces;

class Banned extends Cli\Controller implements Interfaces\CliControllerInterface
{
    public function help($command = null)
    {
        $this->out('Syntax usage: cli migrations search [dev]');
    }

    public function exec()
    {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        $this->out('Start re-index?', $this::OUTPUT_INLINE);
        $answer = trim(readline('[y/N] '));

        if ($answer != 'y') {
            throw new Exceptions\CliException('Cancelled by user');
        }

        /** @var Client $db */
        $cql = Di::_()->get('Database\Cassandra\Cql');
        /** @var EventsDispatcher $dispatcher */
        $dispatcher = Di::_()->get('EventsDispatcher');

        $pageSize = $this->getOpt('page-size') ?? 1000;
        $token = '';

        $template = "SELECT * FROM entities WHERE column1 = ? AND value = ? ALLOW FILTERING;";
        $prepared = new Custom();
        $prepared->query($template, ['banned', 'yes']);

        while ($token !== null) {
            try {
                $prepared->setOpts([
                    'page_size' => (int) $pageSize,
                    'paging_state_token' => $token,
                ]);

                /** @var Rows $result */
                $result = $cql->request($prepared);

                if (!$result || $result->count() === 0) {
                    break;
                }

                $token = $result->pagingStateToken();

                foreach ($result as $row) {
                    $guid = $row['key'];

                    $dispatcher->trigger('search:index', 'all', [
                        'entity' => new User($guid),
                        'immediate' => true,
                    ]);
                    $this->out("Sent a re-index event for {$guid}");
                }
            } catch (\Exception $e) {
                error_log($e->getMessage());
            }
            usleep(500 * 1000);
        }
    }
}
