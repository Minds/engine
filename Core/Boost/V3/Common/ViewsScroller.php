<?php
namespace Minds\Core\Boost\V3\Common;

use Minds\Core\Data\Cassandra\Scroll;
use Minds\Core\Data\Cassandra\Prepared;
use Minds\Core\Di\Di;
use Minds\Exceptions\ServerErrorException;

class ViewsScroller
{
    public function __construct(
        protected ?Scroll $scroll = null,
    ) {
        $this->scroll ??= Di::_()->get('Database\Cassandra\Cql\Scroll');
    }

    /**
     * Scrolls through views based on timeuuid cursors
     * @param \Cassandra\Timeuuid $gtTimeuuid
     * @param \Cassandra\Timeuuid $ltTimeuuid
     * @return iterable<array>
     */
    public function scroll(
        \Cassandra\Timeuuid $gtTimeuuid = null,
        \Cassandra\Timeuuid $ltTimeuuid = null
    ): iterable {
        $prepared = $this->prepareQuery($gtTimeuuid, $ltTimeuuid);
        return $this->scroll->request($prepared);
    }

    /**
     * Prepares the query for our scans
     * TODO: support for overlapping partitions. ie. midnight should include previous day partition
     * @param null|Timeuuid $gtTimeuuid
     * @param null|Timeuuid $ltTimeuuid
     * @return Custom
     * @throws ServerErrorException
     */
    protected function prepareQuery(
        \Cassandra\Timeuuid $gtTimeuuid = null,
        \Cassandra\Timeuuid $ltTimeuuid = null
    ): Prepared\Custom {
        $statement = "SELECT * FROM views WHERE ";
        $values = [];

        if (!($gtTimeuuid || $ltTimeuuid)) {
            throw new ServerErrorException("You must provide at least one timeuuid");
        }

        $dateTime = $gtTimeuuid ? $gtTimeuuid->toDateTime() : $ltTimeuuid->toDateTime();
    
        // Year implode(', ', array_fill(0, count($years), '?'));
        
        $statement .= 'year=? ';
        $values[] = (int) $dateTime->format('Y');

        // Month
        $statement .= 'AND month=? ';
        $values[] = new \Cassandra\Tinyint((int) $dateTime->format('m'));

        // Day
        $statement .= 'AND day=? ';
        $values[] = new \Cassandra\Tinyint((int) $dateTime->format('d'));
        
        // Timeuuid

        if ($gtTimeuuid) {
            $statement .= 'AND uuid>? ';
            $values[] = $gtTimeuuid;
        }
        if ($ltTimeuuid) {
            $statement .= 'AND uuid<? ';
            $values[] = $ltTimeuuid;
        }

        $statement .= "ORDER BY month,day,uuid ASC";

        $query = new Prepared\Custom();
        $query->query($statement, $values);

        $query->setOpts([
            'page_size' => 2500,
            'consistency' => \Cassandra::CONSISTENCY_ONE,
        ]);

        return $query;
    }
}
