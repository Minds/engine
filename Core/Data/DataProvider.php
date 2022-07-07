<?php
/**
 * Minds dataroot Provider
 */

namespace Minds\Core\Data;

use Minds\Core\Data\Locks;
use Minds\Core\Di\Provider;
use PDO;

class DataProvider extends Provider
{
    public function register()
    {
        /**
         * Cache bindings
         */
        $this->di->bind('Cache', function ($di) {
            return cache\factory::build('Redis');
        }, ['useFactory'=>true]);
        $this->di->bind('Cache\Redis', function ($di) {
            return new cache\Redis();
        }, ['useFactory'=>true]);
        $this->di->bind('Cache\Apcu', function ($di) {
            return new cache\apcu();
        }, ['useFactory'=>true]);
        $this->di->bind('Cache\PsrWrapper', function ($di) {
            return new cache\PsrWrapper();
        }, ['useFactory'=>true]);
        $this->di->bind('Cache\Cassandra', function ($di) {
            return new cache\Cassandra();
        }, ['useFactory'=>true]);
        /**
         * Database bindings
         */
        $this->di->bind('Database', function ($di) {
            return $di->get('Database\Cassandra');
        }, ['useFactory'=>true]);
        $this->di->bind('Database\Cassandra', function ($di) {
            return new Call();
        }, ['useFactory'=>true]);
        $this->di->bind('Database\Cassandra\Cql', function ($di) {
            return new Cassandra\Client();
        }, ['useFactory'=>true]);
        $this->di->bind('Database\Cassandra\Cql\Scroll', function ($di) {
            return new Cassandra\Scroll();
        }, ['useFactory'=>true]);
        $this->di->bind('Database\Cassandra\Entities', function ($di) {
            return new Call('entities');
        }, ['useFactory'=>false]);
        $this->di->bind('Database\Cassandra\UserIndexes', function ($di) {
            return new Call('user_index_to_guid');
        }, ['useFactory'=>false]);
        $this->di->bind('Database\Cassandra\Indexes', function ($di) {
            return new Cassandra\Thrift\Indexes(new Call('entities_by_time'));
        }, ['useFactory'=>false]);
        $this->di->bind('Database\Cassandra\Lookup', function ($di) {
            return new Cassandra\Thrift\Lookup(new Call('user_index_to_guid'));
        }, ['useFactory'=>false]);
        $this->di->bind('Database\Cassandra\Data\Lookup', function ($di) {
            return new lookup();
        }, ['useFactory'=>false]);
        $this->di->bind('Database\Cassandra\Relationships', function ($di) {
            return new Cassandra\Thrift\Relationships(new Call('relationships'));
        }, ['useFactory'=>false]);
        $this->di->bind('Database\MongoDB', function ($di) {
            return new MongoDB\Client();
        }, ['useFactory'=>true]);
        $this->di->bind('Database\ElasticSearch', function ($di) {
            return new ElasticSearch\Client();
        }, ['useFactory'=>true]);
        $this->di->bind('Database\ElasticSearch\Scroll', function ($di) {
            return new ElasticSearch\Scroll();
        }, ['useFactory'=>true]);
        $this->di->bind('Database\MySQL\Client', function ($di) {
            return new MySQL\Client();
        }, ['useFactory'=>true]);
        /**
         * Locks
         */
        $this->di->bind('Database\Locks\Cassandra', function ($di) {
            return new Locks\Cassandra();
        }, ['useFactory' => false]);
        $this->di->bind('Database\Locks\Redis', function ($di) {
            return new Locks\Redis();
        }, ['useFactory' => false]);
        $this->di->bind('Database\Locks', function ($di) {
            return $di->get('Database\Locks\Redis');
        }, ['useFactory' => false]);
        /**
         * PubSub bindings
         */
        $this->di->bind('PubSub\Redis', function ($di) {
            return new PubSub\Redis\Client();
        }, ['useFactory'=>true]);
        /**
         * Redis
         */
        $this->di->bind('Redis', function ($di) {
            $master = ($di->get('Config')->redis ?? null)['master'] ?? null;
            $client = new Redis\Client();
            $client->connect($master);
            return $client;
        }, ['useFactory'=>true]);
        $this->di->bind('Redis\Slave', function ($di) {
            $slave = ($di->get('Config')->redis ?? null)['slave'] ?? null;
            $client = new Redis\Client();
            $client->connect($slave);
            return $client;
        }, ['useFactory'=>true]);
        /**
         * Prepared statements
         */
        $this->di->bind('Prepared\MonetizationLedger', function ($di) {
            return new Cassandra\Prepared\MonetizationLedger();
        });

        $this->di->bind('BigQuery', function ($di) {
            return new BigQuery\Client();
        }, ['useFactory'=>true]);
    }
}
