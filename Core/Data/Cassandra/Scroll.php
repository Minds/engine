<?php
/**
 * Scroll
 * @author edgebal
 */

namespace Minds\Core\Data\Cassandra;

use Cassandra as Driver;
use Minds\Core\Data\Interfaces\PreparedInterface;
use Minds\Core\Di\Di;

class Scroll
{
    /** @var Client */
    protected $db;

    /**
     * Scroll constructor.
     * @param Client $db
     */
    public function __construct(
        $db = null
    ) {
        $this->db = $db ?: Di::_()->get('Database\Cassandra\Cql');
    }

    /**
     * @param PreparedInterface $prepared
     * @param string $pagingToken
     * @return \Generator
     */
    public function request(PreparedInterface $prepared, &$pagingToken = null)
    {
        $request = clone $prepared;
        $cqlOpts = $request->getOpts() ?: [];

        if (!isset($cqlOpts['page_size']) || !$cqlOpts['page_size']) {
            $cqlOpts['page_size'] = 500;
        }

        while (true) {
            $request->setOpts($cqlOpts);

            /** @var Driver\Rows $rows */
            $rows = $this->db->request($request);

            // Update pass by reference
            if ($rows) {
                //$pagingToken = $rows->pagingStateToken();
            }

            foreach ($rows as $row) {
                $pagingToken = $rows->pagingStateToken();
                yield $row;
            }

            if (!$rows || $rows->isLastPage()) {
                break;
            }

            $cqlOpts['paging_state_token'] = $rows->pagingStateToken();
        }
    }
}
