<?php
/**
 * Cassandra client
 */
namespace Minds\Core\Data\Cassandra;

use Cassandra as Driver;
use Minds\Core;
use Minds\Core\Data\Interfaces;
use Minds\Core\Config;
use Minds\Core\Di\Di;
use Minds\Core\Events\Dispatcher;

class Client implements Interfaces\ClientInterface
{
    /** @var Config */
    private $config;

    /** @var array */
    private $options;

    /** @var Driver\Cluster */
    private $cluster;
    
    /** @var Driver\Session */
    private $session;

    public function __construct(array $options = [], $config = null)
    {
        $this->config = $config ?? Di::_()->get('Config');
        $this->options = $options;
    }

    public function request(Interfaces\PreparedInterface $request, $silent = false)
    {
        $cql = $request->build();
        try {
            $statement = $this->getSession()->prepare($cql['string'], []);
            $future = $this->getSession()->executeAsync(
                $statement,
                array_merge(
                    [
                        'arguments' => $cql['values']
                    ],
                    $request->getOpts()
                )
            );
            if ($silent) {
                return $future;
            } else {
                return $response = $future->get();
            }
        } catch (\Exception $e) {
            if ($this->isDebug()) {
                error_log('[CQL Error: ' . get_class($e) . '] ' . $e->getMessage());
                error_log(json_encode($cql));
            }
            return false;
        }

        return true;
    }

    /**
     * Run a synchronous query
     * @param string $statement
     * @return mixed
     */
    public function execute($statement)
    {
        return $this->getSession()->execute($statement);
    }

    public function batchRequest($requests = [], $batchType = Driver::BATCH_COUNTER, $silent = false)
    {
        $batch = new Driver\BatchStatement($batchType);

        foreach ($requests as $request) {
            $cql = $request;
            $statement = $this->getSession()->prepare($cql['string']);
            $batch->add($statement, $cql['values']);
        }

        if ($silent) {
            return $this->getSession()->executeAsync($batch);
        }

        return $this->getSession()->execute($batch);
    }

    public function getPrefix()
    {
        return Config::_()->get('multi')['prefix'];
    }


    /**
     * Get performance and diagnostic metrics.
     * @return array Performance/Diagnostic metrics.
     */
    public function metrics(): array | null
    {
        try {
            return $this->getSession()?->metrics();
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Returns the session for the cassandra connection
     * @return Driver
     */
    private function getSession(): Driver\Session
    {
        if (!$this->session) {
            $options = array_merge((array) $this->config->get('cassandra'), $this->options);
            $retry_policy = new Driver\RetryPolicy\DowngradingConsistency();

            $this->cluster = Driver::cluster()
                ->withContactPoints(... $options['cql_servers'])
                ->withCredentials($options['username'], $options['password'])
                ->withLatencyAwareRouting(true)
                ->withDefaultConsistency(Driver::CONSISTENCY_LOCAL_QUORUM)
                ->withRetryPolicy(new Driver\RetryPolicy\Logging($retry_policy))
                ->withTokenAwareRouting(false) // makes initial connect fast
                ->withPort(9042)
                ->build();

            $this->session = $this->cluster->connect($options['keyspace']);
        }

        return $this->session;
    }

    /** @return bool */
    private function isDebug(): bool
    {
        return (bool) $this->config->get('minds_debug');
    }
}
