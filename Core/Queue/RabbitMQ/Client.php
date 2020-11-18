<?php
namespace Minds\Core\Queue\RabbitMQ;

use Minds\Core\Di\Di;
use Minds\Core\Queue\Interfaces;
use Minds\Core\Queue\Message;
use Minds\Core\Config;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Channel\AMQPChannel;

/**
 * Messaging queue
 */

class Client implements Interfaces\QueueClient
{
    /** @var Config */
    private $config;

    /** @var AMQPStreamConnection */
    private $connection;

    /** @var AMQPChannel */
    private $channel;

    /** @var string*/
    private $queue;

    /** @var string */
    private $exchange;

    /** @var string */
    private $binder = "";

    public function __construct($config, AMQPStreamConnection $connection = null)
    {
        $this->config = $config;
        $this->connection = $connection;
    }

    /**
     * Setup connection
     */
    protected function setup(): void
    {
        if (!$this->connection) {
            $this->connection = AMQPStreamConnection::create_connection([
                [
                    'host' => $this->config->rabbitmq['host'] ?: 'localhost',
                    'port' => $this->config->rabbitmq['port'] ?: 5672,
                    'user' =>$this->config->rabbitmq['username'] ?: 'guest',
                    'password' => $this->config->rabbitmq['password'] ?: 'guest',
                ],
            ]);
        }

        if (!$this->channel) {
            $this->channel = $this->connection->channel();
            register_shutdown_function(function ($channel, $connection) {
                $channel->close();
                $connection->close();
            //error_log("SHUTDOWN RABBITMQ CONNECTIONS");
            }, $this->channel, $this->connection);
        }
    }

    /**
     * @param string $name
     * @param string $type
     * @return self
     */
    public function setExchange($name = "default_exchange", $type = "direct"): self
    {
        // Setup channel and connection
        $this->setup();

        $this->exchange = $name;
        //also create exchange if doesn't exist
        //name/type/passive/durable/auto_delete
        $this->channel->exchange_declare($this->exchange, $type, false, true, false);

        return $this;
    }

    /**
     * @param string $name
     * @param string $binder
     * @return self
     */
    public function setQueue($name = "", $binder = ""): self
    {
        if (!$this->exchange) {
            $this->setExchange(
                Di::_()->get('Config')->get('queue')['exchange'] ?: 'mindsqueue'
            );
        }

        if (!$binder) {
            $binder = $name;
        }

        $this->queue = $name;
        $this->binder = $binder;

        //this is idempotent.. it will only be created if it doesn't exist
        //name/passive/durable/exclusive/auto_delete
        list($this->queue, , ) = $this->channel->queue_declare($name, false, true, false, false);
        $this->channel->queue_bind($this->queue, $this->exchange, $this->binder);

        return $this;
    }

    /**
     * @param mixed $message
     * @return self
     */
    public function send($message): self
    {
        $msg = new Message();
        //error_log("\n === NEW MESSAGE FROM MINDS ===");
        $msg = new AMQPMessage($msg->setData($message));
        //error_log("=== AMPQ MESSAGE CONTRUCTED === \n");
        if ($this->connection->isConnected()) {
            $this->channel->basic_publish($msg, $this->exchange, $this->binder);
        } else {
            error_log("Not connected.. but tried to send message to channel");
        }
        return $this;
    }

    /**
     * @param \callable $callback
     * @return self
     */
    public function receive($callback): self
    {
        $this->channel->basic_consume($this->queue, '', false, true, false, false, function ($message) use ($callback) {
            $callback(new Message($message->body));
        });

        while (count($this->channel->callbacks) && $this->connection->isConnected()) {
            $this->channel->wait();
        }

        return $this;
    }

    public function close(): void
    {
        $this->channel->close();
        $this->connection->close();
    }
}
