<?php
/**
 * EntityCentric Repository
 * @author Mark
 */

namespace Minds\Core\Analytics\EntityCentric;

use DateTime;
use DateTimeZone;
use Exception;
use Minds\Common\Repository\Response;
use Minds\Core\Data\ElasticSearch\Client as ElasticClient;
use Minds\Core\Di\Di;

class Repository
{
    /** @var ElasticClient */
    protected $es;

    /** @var array $pendingBulkInserts * */
    private $pendingBulkInserts = [];

    /**
     * Repository constructor.
     * @param ElasticClient $es
     */
    public function __construct(
        $es = null
    ) {
        $this->es = $es ?: Di::_()->get('Database\ElasticSearch');
    }

    /**
     * @param array $opts
     * @return Response
     */
    public function getList(array $opts = [])
    {
        $response = new Response();

        return $response;
    }

    /**
     * @param EntityCentricRecord $record
     * @return bool
     * @throws Exception
     */
    public function add(EntityCentricRecord $record)
    {
        $index = 'minds-entitycentric-' . date('m-Y', $record->getTimestamp());

        $body = [
            'resolution' => $record->getResolution(),
            '@timestamp' => $record->getTimestamp() * 1000,
            'entity_urn' => $record->getEntityUrn(),
            'owner_guid' => $record->getOwnerGuid(),
        ];

        $body = array_merge($body, $record->getSums());

        $body = array_filter($body, function ($val) {
            if ($val === '' || $val === null) {
                return false;
            }
            return true;
        });

        $this->pendingBulkInserts[] = [
            'update' => [
                '_id' => (string) implode('-', [ $record->getEntityUrn(), $record->getResolution(), $record->getTimestamp() ]),
                '_index' => $index,
                '_type' => '_doc',
            ],
        ];

        $this->pendingBulkInserts[] = [
            'doc' => $body,
            'doc_as_upsert' => true,
        ];

        if (count($this->pendingBulkInserts) > 2000) { //1000 inserts
            $this->bulk();
        }

        return true;
    }

    /**
     * Bulk insert results
     */
    public function bulk()
    {
        if (count($this->pendingBulkInserts) > 0) {
            $res = $this->es->bulk(['body' => $this->pendingBulkInserts]);
            $this->pendingBulkInserts = [];
        }
    }
}
