<?php

namespace Minds\Controllers\Cli;

use Minds\Core;
use Minds\Core\Di\Di;
use Minds\Cli;
use Minds\Core\Data\Cassandra\Prepared\Custom;
use Minds\Core\Feeds\Elastic\V2\QueryOpts;
use Minds\Core\Reports\Enums\ReportReasonEnum;
use Minds\Core\Security\Spam;
use Minds\Interfaces;
use Minds\Entities;
use Minds\Exceptions\ProhibitedDomainException;

class Search extends Cli\Controller implements Interfaces\CliControllerInterface
{
    public function __construct()
    {
    }

    public function help($command = null)
    {
        $this->out('TBD');
    }
    
    public function exec()
    {
        $this->out('Usage: cli search [set_mappings]');
    }

    public function set_mappings()
    {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        $this->out('Setting up mappings…');

        $provisioner = Di::_()->get('Search\Provisioner');
        $provisioner->setUp();

        $this->out('Done!');
    }

    public function index()
    {
    }

    public function sync_interactions()
    {
        $mins = $this->getOpt('minutes') ?: 30;

        $events = new Core\Analytics\Iterators\EventsIterator();
        $events->setType('action');
        $events->setPeriod(time() - ($mins * 60));
        $events->setTerms([ 'entity_guid.keyword' ]);

        $sync = new Core\Search\InteractionsSync();

        foreach ($events as $guid) {
            $entity = @Entities\Factory::build($guid);
            if (!$entity) {
                $this->out("$guid [not found]");
                continue;
            }
            $sync->sync($entity);
            $this->out("$entity->guid [done]");
        }
    }

    public function sync_single()
    {
        $entity = @Entities\Factory::build($this->getOpt('guid'));
        $sync = new Core\Search\InteractionsSync();
        $sync->sync($entity);
    }

    /**
     * Will restore pulsar state
     */
    public function sync_from_entities_by_time()
    {
        $statement = "SELECT * FROM entities_by_time  where key = 'activity' and column1>'1503960793568847892'";
        $scroll = Di::_()->get('Database\Cassandra\Cql\Scroll');

        $prepared = new Custom();
        $prepared->query($statement);

        foreach ($scroll->request($prepared) as $row) {
            $urn = 'urn:activity:' . $row['value'];
            \Minds\Core\Events\Dispatcher::trigger('entities-ops', 'create', [
                'entityUrn' => $urn
            ]);
            $this->out($urn);
        }
    }

    public function cleanup_spam()
    {
        /** @var Core\Feeds\Elastic\V2\Manager */
        $manager = Di::_()->get(Core\Feeds\Elastic\V2\Manager::class);

        $entitiesBuilder = Di::_()->get('EntitiesBuilder');

        /** @var Core\Channels\Ban */
        $channelsBanManager = Di::_()->get('Channels\Ban');

        $spam = new Spam();

        $opts = new QueryOpts(
            limit: 9999,
            query: null,
        );

        $i = 0;
        foreach ($manager->getLatest($opts) as $activity) {
            ++$i;
            try {
                $spam->check($activity);
                $this->out($i);
            } catch (ProhibitedDomainException $e) {
                $this->out('Ban ' . $activity->getOwnerGuid());

                $user = $entitiesBuilder->single($activity->getOwnerGuid());

                $channelsBanManager
                    ->setUser($user)
                    ->ban(implode('.', [ ReportReasonEnum::SPAM->value, 0 ]));
            } finally {
            }
        }
    }
}
