<?php
namespace Minds\Core\Wire\SupportTiers;

use Cassandra\Bigint;
use Cassandra\Decimal;
use Minds\Common\Repository\Response;
use Minds\Core\Data\Cassandra\Client;
use Minds\Core\Data\Cassandra\Prepared\Custom;
use Minds\Core\Di\Di;

/**
 * Wire Support Tiers Repository
 * @package Minds\Core\Wire\SupportTiers
 */
class Repository
{
    /** @var Client */
    protected $db;

    /**
     * Repository constructor.
     * @param $db
     */
    public function __construct(
        $db = null
    ) {
        $this->db = $db ?: Di::_()->get('Database\Cassandra\Cql');
    }

    /**
     * Gets a list of all support tiers that match filter options
     * @param RepositoryGetListOptions $opts
     * @return Response<SupportTier>
     */
    public function getList(RepositoryGetListOptions $opts): Response
    {
        $cql = 'SELECT * FROM wire_support_tier';
        $where = [];
        $values = [];

        if ($opts->getEntityGuid()) {
            $where[] = 'entity_guid = ?';
            $values[] = new Bigint((string) $opts->getEntityGuid());
        }

        if ($opts->getGuid()) {
            $where[] = 'guid = ?';
            $values[] = new Bigint((string) $opts->getGuid());
        }

        if ($where) {
            $cql .= ' WHERE ' . implode(' AND ', $where);
        }

        $cqlOpts = [
            'paging_state_token' => base64_decode((string) $opts->getOffset(), true),
            'page_size' => (int) $opts->getLimit(),
        ];

        $prepared = new Custom();
        $prepared->query($cql, $values);
        $prepared->setOpts($cqlOpts);

        $rows = $this->db->request($prepared);
        $response = new Response();

        foreach ($rows ?: [] as $row) {
            $supportTier = new SupportTier();

            $supportTier
                ->setEntityGuid((string) $row['entity_guid']->value())
                ->setGuid((string) $row['guid']->value())
                ->setPublic($row['public'])
                ->setName($row['name'])
                ->setDescription($row['description'])
                ->setUsd($row['usd']->toDouble())
                ->setHasUsd($row['has_usd'])
                ->setHasTokens($row['has_tokens']);

            $response[] = $supportTier;
        }

        if ($rows) {
            $response->setPagingToken(base64_encode($rows->pagingStateToken()));
            $response->setLastPage($rows->isLastPage());
        }

        return $response;
    }

    /**
     * Creates a new support tier
     * @param SupportTier $supportTier
     * @return bool
     */
    public function add(SupportTier $supportTier): bool
    {
        $cql = 'INSERT INTO wire_support_tier (entity_guid, guid, public, name, description, usd, has_usd, has_tokens) VALUES (?, ?, ?, ?, ?, ?, ?, ?)';
        $values = [
            new Bigint($supportTier->getEntityGuid()),
            new Bigint($supportTier->getGuid()),
            (bool) $supportTier->isPublic(),
            (string) $supportTier->getName(),
            (string) $supportTier->getDescription(),
            new Decimal((string) $supportTier->getUsd()),
            (bool) $supportTier->hasUsd(),
            (bool) $supportTier->hasTokens(),
        ];

        $prepared = new Custom();
        $prepared->query($cql, $values);

        return (bool) $this->db->request($prepared, true);
    }

    /**
     * Updates a support tier
     * @param SupportTier $supportTier
     * @return bool
     */
    public function update(SupportTier $supportTier): bool
    {
        return $this->add($supportTier);
    }

    /**
     * Deletes a single support tier
     * @param SupportTier $supportTier
     * @return bool
     */
    public function delete(SupportTier $supportTier): bool
    {
        $cql = 'DELETE FROM wire_support_tier WHERE entity_guid = ? AND guid = ?';
        $values = [
            new Bigint($supportTier->getEntityGuid()),
            new Bigint($supportTier->getGuid()),
        ];

        $prepared = new Custom();
        $prepared->query($cql, $values);

        return (bool) $this->db->request($prepared, true);
    }

    /**
     * Deletes all support tiers from an entity
     * @param SupportTier $supportTier
     * @return bool
     */
    public function deleteAll(SupportTier $supportTier): bool
    {
        $cql = 'DELETE FROM wire_support_tier WHERE entity_guid = ?';
        $values = [
            new Bigint($supportTier->getEntityGuid()),
        ];

        $prepared = new Custom();
        $prepared->query($cql, $values);

        return (bool) $this->db->request($prepared, true);
    }
}
