<?php
namespace Minds\Core\Metrics;

use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\Response\JsonResponse;
use Prometheus;

class Controller
{
    /** @var Manager */
    protected $manager;

    public function __construct($manager = null)
    {
        $this->manager = $manager ?? new Manager();
    }

    /**
     * Returns metrics for external services
     * @param ServerRequest $request
     * @return JsonResponse
     */
    public function getMetrics(ServerRequest $request): JsonResponse
    {
        $registry = new Prometheus\CollectorRegistry(new Prometheus\Storage\InMemory());

        $metrics = $this->manager->getMetrics();

        $registry->getOrRegisterGauge('engine', 'cassandra_connections_total', 'Cassandra Total connections')->incBy($metrics['cassandra'] ? $metrics['cassandra']['stats']['total_connections'] : 0);
        if ($metrics['cassandra']) {
            $registry->getOrRegisterGauge('engine', 'cassandra_available_connections_total', 'Cassandra Available connections')->incBy($metrics['cassandra']['stats']['available_connections']);
            $registry->getOrRegisterGauge('engine', 'cassandra_connection_timeouts_total', 'Cassandra Connection timeouts')->incBy($metrics['cassandra']['errors']['connection_timeouts']);
            $registry->getOrRegisterGauge('engine', 'cassandra_pending_request_timeouts_total', 'Cassandra Pending request timeouts')->incBy($metrics['cassandra']['errors']['pending_request_timeouts']);
            $registry->getOrRegisterGauge('engine', 'cassandra_request_timeouts_total', 'Cassandra Request timeouts')->incBy($metrics['cassandra']['errors']['request_timeouts']);
        }
        
        $registry->getOrRegisterGauge('engine', 'redis_connections_total', 'Redis Connections')->incBy($metrics['redis'] ? 1 : 0);
        if ($metrics['redis']) {
            $registry->getOrRegisterGauge('engine', 'redis_connected_clients_total', 'Redis Connected clients')->incBy($metrics['redis']['connected_clients']);
            $registry->getOrRegisterGauge('engine', 'redis_connected_slaves_total', 'Redis Connected slaves')->incBy($metrics['redis']['connected_slaves']);
            $registry->getOrRegisterGauge('engine', 'redis_total_error_replies_total', 'Redis Total error replies')->incBy($metrics['redis']['total_error_replies']);
            $registry->getOrRegisterGauge('engine', 'redis_rejected_connections_total', 'Redis Rejected connections')->incBy($metrics['redis']['rejected_connections']);
            $registry->getOrRegisterGauge('engine', 'redis_cluster_connections_total', 'Redis Cluster connections')->incBy($metrics['redis']['cluster_connections']);
        }

        $registry->getOrRegisterGauge('engine', 'elasticsearch_connections_total', 'ElasticSearch connections', ['status'])->incBy($metrics['es'] && ($metrics['es']['status'] === 'green' || $metrics['es']['status'] === 'yellow') ? 1 : 0, $metrics['es'] ? [$metrics['es']['status']] : []);
        $registry->getOrRegisterGauge('engine', 'sendGrid_connections_total', 'SendGrid connections')->incBy($metrics['sendGrid'] ? 1 : 0);
        $registry->getOrRegisterGauge('engine', 'web3_connections_total', 'Web3 connections')->incBy($metrics['web3'] ? 1 : 0);
        $registry->getOrRegisterGauge('engine', 'permaweb_connections_total', 'Permaweb connections')->incBy($metrics['permaweb'] ? 1 : 0);
        $registry->getOrRegisterGauge('engine', 'sqs_connections_total', 'SQS connections')->incBy($metrics['sqs'] ? 1 : 0);

        $renderer = new Prometheus\RenderTextFormat();
        $result = $renderer->render($registry->getMetricFamilySamples());

        header('Content-type: ' . Prometheus\RenderTextFormat::MIME_TYPE);
        echo $result;

        return new JsonResponse([]);
    }
}
