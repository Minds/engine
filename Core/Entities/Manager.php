<?php
namespace Minds\Core\Entities;

use Minds\Core\Di\Di;
use Minds\Core\Data\Cassandra\Scroll;
use Minds\Core\Data\Cassandra\Prepared;

class Manager
{
    /** @var Scroll */
    protected $scroll;

    /** @var EntitiesBuilder */
    protected $entitiesBuilder;

    public function __construct($scroll = null, $entitiesBuilder = null)
    {
        $this->scroll = $scroll ?? Di::_()->get('Database\Cassandra\Cql\Scroll');
        $this->entitiesBuilder = $entitiesBuilder ?? Di::_()->get('EntitiesBuilder');
    }

    /**
     * Return an iterable of all entities
     * @param array
     * @return iterable
     */
    public function getList(array $opts = []): iterable
    {
        $opts = array_merge([
            'type' => null,
        ], $opts);

        $statement = "SELECT * FROM entities_by_time WHERE key = ?";
        $values = [ $opts['type'] ];
        
        $query = new Prepared\Custom();
        $query->query($statement, $values);

        $rows = $this->scroll->request($query);

        foreach ($rows as $row) {
            yield $this->entitiesBuilder->single($row['column1']);
        }
    }
}
