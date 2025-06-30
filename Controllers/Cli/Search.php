<?php

namespace Minds\Controllers\Cli;

use Minds\Core;
use Minds\Core\Di\Di;
use Minds\Cli;
use Minds\Core\Data\Cassandra\Prepared\Custom;
use Minds\Core\Feeds\Elastic\V2\QueryOpts;
use Minds\Core\MultiTenant\Services\MultiTenantBootService;
use Minds\Core\MultiTenant\Services\MultiTenantDataService;
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

    public function reindex_group_posts()
    {
        foreach (Di::_()->get(MultiTenantDataService::class)->getTenants(limit: 9999999) as $tenant) {
            Di::_()->get(MultiTenantBootService::class)->bootFromTenantId($tenant->id);
            try {
                /** @var Core\Feeds\Elastic\V2\Manager */
                $manager = Di::_()->get(Core\Feeds\Elastic\V2\Manager::class);


                $opts = new QueryOpts(
                    limit: 9999,
                    query: "",
                );

                $i = 0;
                foreach ($manager->getLatest($opts) as $activity) {
                    ++$i;
                    try {
                        $this->out('Synced ' . $activity->getGuid());

                        \Minds\Core\Events\Dispatcher::trigger('entities-ops', 'update', [
                            'entityUrn' => $activity->getUrn(),
                        ]);
                    } catch (\Exception $e) {

                        
                    } finally {
                    }
                }

            } catch (\Exception $e) {
                $this->out("Error reindexing for tenant_id: $tenant->id");
                $this->out($e->getMessage());
            }
        }
    }

}
