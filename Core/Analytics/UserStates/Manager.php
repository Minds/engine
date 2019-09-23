<?php

namespace Minds\Core\Analytics\UserStates;

use Minds\Core\Data\ElasticSearch\Client;
use Minds\Core\Di\Di;
use Minds\Core\Queue;

class Manager
{
    /** @var Queue\RabbitMQ\Client */
    private $queue;

    /** @var int $referenceTimestamp */
    private $referenceTimestamp;

    /** @var int $numberOfIntervals */
    private $numberOfIntervals = 7;

    /** @var string $userStateIndex */
    private $userStateIndex;

    /** @var ActiveUsersIterator $activeUsersIterator */
    private $activeUsersIterator;

    /** @var UserStateIterator */
    private $userStateIterator;

    /** @var array $pendingBulkInserts * */
    private $pendingBulkInserts = [];

    /** @var Client */
    private $es;

    private $debug = false;

    public function __construct($client = null, $index = null, $queue = null, $activeUsersIterator = null, $userStateIterator = null)
    {
        $this->es = $client ?: Di::_()->get('Database\ElasticSearch');
        $this->userStateIndex = $index ?: 'minds-kite';
        $this->queue = $queue ?: Queue\Client::build();
        $this->activeUsersIterator = $activeUsersIterator ?: new ActiveUsersIterator();
        $this->userStateIterator = $userStateIterator ?: new UserStateIterator();
    }

    public function setReferenceTimestamp($referenceDate): self
    {
        $this->referenceTimestamp = $referenceDate;
        return $this;
    }

    public function setNumberOfIntervals($numberOfIntervals): self
    {
        $this->$numberOfIntervals = $numberOfIntervals;
        return $this;
    }

    public function setDebug(bool $debug): self
    {
        $this->debug = $debug;
        return $this;
    }

    public function sync()
    {
        $this->activeUsersIterator->setReferenceTimestamp($this->referenceTimestamp);
        $this->activeUsersIterator->setNumberOfIntervals($this->numberOfIntervals);

        foreach ($this->activeUsersIterator as $activeUser) {
            $userState = (new UserState())
                ->setUserGuid($activeUser->getUserGuid())
                ->setReferenceDateMs($activeUser->getReferenceDateMs())
                ->setState($activeUser->getState())
                ->setActivityPercentage($activeUser->getActivityPercentage());
            $this->index($userState);
        }
        $this->bulk();
    }

    public function emitStateChanges(bool $estimate = false)
    {
        $this->userStateIterator->setReferenceTimestamp($this->referenceTimestamp);

        $this->queue->setQueue('UserStateChanges');
        foreach ($this->userStateIterator as $userState) {
            $this->debugLog($userState);
            if (!empty($userState->getPreviousState())) {
                $this->index($userState);
            }

            $payload = [
                'user_state_change' => $userState->export()
            ];

            if ($estimate) {
                $payload['estimate'] = true;
            }

            $this->queue->send($payload);
        }
        $this->bulk();
    }

    /**
     * Index a user user state (queues to batch).
     *
     * @param UserState $userState
     *
     * @return bool
     */
    public function index(UserState $userState)
    {
        $this->pendingBulkInserts[] = [
            'update' => [
                '_id' => "{$userState->getUserGuid()}-{$userState->getReferenceDateMs()}",
                '_index' => $this->userStateIndex,
                '_type' => 'active_user',
            ],
        ];

        $this->pendingBulkInserts[] = [
            'doc' => $userState->export(false),
            'doc_as_upsert' => true,
        ];

        if (count($this->pendingBulkInserts) > 2000) { //1000 inserts
            $this->bulk();
        }

        return true;
    }

    /**
     * Run a bulk insert job (quicker).
     */
    public function bulk()
    {
        if (count($this->pendingBulkInserts) > 0) {
            $result = $this->es->bulk(['body' => $this->pendingBulkInserts]);
            $this->pendingBulkInserts = [];
        }
    }

    private function debugLog($var): void
    {
        if ($this->debug) {
            $caller = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'];
            error_log($caller . ':' . print_r($var, true));
        }
    }
}
