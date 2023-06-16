<?php

namespace Minds\Core\Analytics\Iterators;

use Minds\Core;
use Minds\Core\Analytics\Timestamps;
use Minds\Core\Data;

/**
 * Iterator that loops through all signups after a set period
 */
class SignupsOffsetIterator implements \Iterator
{
    private $cursor = -1;
    private $period = 0;

    private $limit = 400;
    public $token = "";
    private $offset = "";
    private $data = [];

    private $valid = true;

    /** @var Data\Cassandra\Client */
    private $db;
    /** @var Core\EntitiesBuilder */
    private $entitiesBuilder;

    private $position;

    public function __construct($db = null, $entitiesBuilder = null)
    {
        $this->db = $db ?: Core\Di\Di::_()->get('Database\Cassandra\Cql');
        $this->entitiesBuilder = $entitiesBuilder ?: Core\Di\Di::_()->get('EntitiesBuilder');
        $this->position = 0;
    }

    /**
     * Sets the period to cycle through
     * @param string|null $period
     */
    public function setPeriod(?string $period = null): void
    {
        $this->period = $period;
        $this->getUsers();
    }

    public function setOffset(mixed $offset = ''): void
    {
        $this->offset = $offset;
        $this->getUsers();
    }

    /**
     * Fetch all the users who signed up in a certain period
     */
    protected function getUsers(): void
    {
        $timestamps = array_reverse(Timestamps::span($this->period + 1, 'day'));

        $prepared = new Data\Cassandra\Prepared\Custom;
        $prepared->query("SELECT * from entities_by_time where key='user' and column1>?", [
            (string) $this->offset
        ]);
        $prepared->setOpts([
            'page_size' => $this->limit,
            'paging_state_token' => base64_decode($this->token, true)
        ]);

        $rows = $this->db->request($prepared);
        if (!$rows) {
            $this->valid = false;
            return;
        }

        $this->token = base64_encode($rows->pagingStateToken());

        $guids = [];
        foreach ($rows as $row) {
            $guids[] = $row['column1'];
        }

        $this->valid = true;
        $users = $this->entitiesBuilder->get(['guids' => $guids]);

        $pushed = 0;
        foreach ($users as $user) {
            if ($user->time_created < $timestamps[$this->period]) {
                array_push($this->data, $user);
                $pushed++;
            }
        }

        if ($rows->isLastPage()) {
            $this->valid = false;
            return;
        }

        if (!$pushed) {
            error_log("no users past period " . date('d-m-Y', end($users)->time_created));
            $this->getUsers();
        }
    }

    /**
     * Rewind the array cursor
     * @return void
     */
    public function rewind(): void
    {
        if ($this->cursor >= 0) {
            $this->getUsers();
        }
        $this->next();
    }

    /**
     * Get the current cursor's data
     * @return mixed
     */
    public function current(): mixed
    {
        return $this->data[$this->cursor];
    }

    /**
     * Get cursor's key
     * @return int
     */
    public function key(): int
    {
        return $this->cursor;
    }

    /**
     * Goes to the next cursor
     * @return void
     */
    public function next(): void
    {
        $this->cursor++;
        if (!isset($this->data[$this->cursor])) {
            $this->getUsers();
        }
    }

    /**
     * Checks if the cursor is valid
     * @return bool
     */
    public function valid(): bool
    {
        return $this->valid && isset($this->data[$this->cursor]);
    }
}
