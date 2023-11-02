<?php
namespace Minds\Core\Groups\Delegates;

use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\Data\ElasticSearch;
use Minds\Core\Data\ElasticSearch\Prepared;
use Minds\Entities\Group;
use Minds\Helpers\SuggestCompleter;

class ElasticSearchDelegate
{
    /** @var ElasticSearch\Client */
    protected $es;

    /** @var SuggestCompleter */
    protected $suggestCompleter;

    public function __construct($es = null, protected ?Config $config = null)
    {
        $this->es = $es ?? Di::_()->get('Database\ElasticSearch');
        $this->suggestCompleter = new SuggestCompleter();
        $this->config ??= Di::_()->get(Config::class);
    }

    public function onSave(Group $group): void
    {

        $doc = [
            'guid' => (int) $group->getGuid(),
            'name' => (string) $group->getName(),
            'briefdescription' => (string) $group->getBriefDescription(),
            'members:count' => (int) $group->getMembersCount(),
            '@timestamp' => $group->getTimeCreated() * 1000,
            'nsfw' => $group->getNsfw(),
        ];

        if ($tenantId = $this->config->get('tenant_id')) {
            $doc['suggest_v2'] = array_merge(
                $this->suggestCompleter->build([ $group->getName() ]),
                [
                    'weight' => (int) round($group->getMembersCount() / 10),
                    'contexts' => [
                        'tenant_id' => (string) $tenantId,
                    ]
                ]
            );
        } else {
            $doc['suggest'] = array_merge(
                $this->suggestCompleter->build([ $group->getName() ]),
                [ 'weight' => (int) round($group->getMembersCount() / 10) ]
            );
        }

        $query = [
            'index' => 'minds-search-group',
            'type' => '_doc',
            'id' => $group->getGuid(),
            'body' => [
                'doc' => $doc,
                'doc_as_upsert' => true,
            ],
        ];
        $prepared = new Prepared\Update();
        $prepared->query($query);

        $result = (bool) $this->es->request($prepared);
    }

    public function onDelete(Group $group): void
    {
    }
}
