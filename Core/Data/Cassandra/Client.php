<?php
/**
 * Cassandra client
 */
namespace Minds\Core\Data\Cassandra;

use Cassandra as Driver;
use Minds\Core\Data\Interfaces;
use Minds\Core\Config;

class Client implements Interfaces\ClientInterface
{
    private $cluster;
    private $session;
    private $prepared;

    public function __construct(array $options = array())
    {
        $this->cluster = Driver::cluster()
           //->withContactPoints(Config::_()->cassandra->cql_servers)
           ->withPort(9042)
           ->build();
        $this->session = $this->cluster->connect(Config::_()->cassandra->keyspace);
    }

    public function request(Interfaces\PreparedInterface $request)
    {
        $cql = $request->build();
        try{
            $statement = $this->session->prepare($cql['string']);
            $future = $this->session->executeAsync(
              $statement,
              new Driver\ExecutionOptions([
                  'arguments' => $cql['values']
              ])
            );

        }catch(\Exception $e){
            return false;
        }
        return $response = $future->get();
    }

    public function batchRequest($requests = array())
    {
        $batch = new Driver\BatchStatement(Driver::BATCH_COUNTER);

        foreach ($requests as $request) {
            $cql = $request;
            $statement = $this->session->prepare($cql['string']);
            $batch->add($statement, $cql['values']);
        }

        return $session->execute($batch);
    }

    public function getPrefix()
    {
        return Config::_()->get('multi')['prefix'];
    }
}
