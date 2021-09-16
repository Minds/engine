<?php
namespace Minds\Core\Security\Block;

use Minds\Common\Repository\Response;
use Minds\Core\Data\Cassandra\Client;
use Minds\Core\Data\Cassandra\Prepared;
use Minds\Core\Di\Di;

class Repository
{
    /** @var Client */
    protected $db;

    public function __construct($db = null)
    {
        $this->db = $db ?? Di::_()->get('Database\Cassandra\Cql');
    }

    /**
     * Adds a block to the database
     * @param Block $block
     * @return bool
     */
    public function add(BlockEntry $block): bool
    {
        $statement = "INSERT INTO entities_by_time (key, column1, value) VALUES (?,?,?)";
        $values = [
            "acl:blocked:{$block->getActorGuid()}",
            (string) $block->getSubjectGuid(),
            (string) time()
        ];

        $prepared = new Prepared\Custom();
        $prepared->query($statement, $values);

        return (bool) $this->db->request($prepared);
    }

    /**
     * Removes a block to the database
     * @param Block $block
     * @return bool
     */
    public function delete(BlockEntry $block): bool
    {
        $statement = "DELETE FROM entities_by_time WHERE key= ? and column1 = ?";
        $values = [
            "acl:blocked:{$block->getActorGuid()}",
            (string) $block->getSubjectGuid(),
        ];

        $prepared = new Prepared\Custom();
        $prepared->query($statement, $values);

        return (bool) $this->db->request($prepared);
    }

    /**
     * Return a list of blocked users
     * @return Response
     */
    public function getList(BlockListOpts $opts): Response
    {
        $statement = "SELECT * FROM entities_by_time WHERE key= ?";
        $values = [ "acl:blocked:{$opts->getUserGuid()}" ];

        $prepared = new Prepared\Custom();
        $prepared->query($statement, $values);
        $prepared->setOpts([
            'page_size' => $opts->getLimit(),
            'paging_state_token' => base64_decode($opts->getPagingToken(), true),
        ]);

        $response = new Response();

        $result = $this->db->request($prepared);

        if (!$result) {
            return $response;
        }

        foreach ($result as $row) {
            $response[] = (new BlockEntry)
                ->setSubjectGuid((string) $row['column1'])
                ->setActorGuid((string) $opts->getUserGuid());
        }

        $response->setPagingToken(base64_encode($result->pagingStateToken()));

        return $response;
    }

    /**
     * Count blocks
     * @param string $userGuid
     * @return int
     */
    public function countList(string $userGuid): int
    {
        $statement = "SELECT COUNT(*) FROM entities_by_time WHERE key= ?";
        $values = [ "acl:blocked:{$userGuid}" ];

        $prepared = new Prepared\Custom;

        $prepared->query($statement, $values);

        $result = $this->db->request($prepared);

        return (int) $result[0]['count'] ?? 0;
    }
}
