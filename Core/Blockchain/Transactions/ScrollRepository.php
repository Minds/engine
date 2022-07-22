<?php

namespace Minds\Core\Blockchain\Transactions;

use Minds\Core\Di\Di;
use Minds\Core\Data\Cassandra\Scroll;
use Minds\Core\Data\Cassandra\Prepared\Custom as CustomQuery;

/**
 * Scroll repository for blockchain transactions.
 */
class ScrollRepository
{
    /**
     * Constructor.
     * @param Scroll|null $scroll - cql scroll.
     */
    public function __construct(
        private ?Scroll $scroll = null
    ) {
        $this->scroll ??= Di::_()->get('Database\Cassandra\Cql\Scroll');
    }

    /**
     * Gets a list of distinct user guids.
     * @return \Generator - generator containing distinct user guids.
     */
    public function getDistinctOffchainUserGuids(): \Generator
    {
        $statement = "SELECT DISTINCT user_guid from minds.blockchain_transactions_mainnet";
        $prepared = new CustomQuery();
        $prepared->query($statement);
        return $this->scroll->request($prepared);
    }
}
