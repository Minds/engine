<?php
namespace Minds\Core\Analytics\Iterators;

use Minds\Core;
use Minds\Core\Analytics\Timestamps;
use Minds\Core\Data;

/**
 * Iterator that loops through all signups
 */
class SignupsIterator implements \Iterator
{
    private $cursor = -1;

    private $period;
    private $limit = 200;
    private $offset = "";
    private $data = [];

    private $valid = true;

    /** @var Data\Call */
    private $db;
    /** @var Core\EntitiesBuilder */
    private $entitiesBuilder;

    private $position;

    public function __construct($db = null, $entitiesBuilder = null)
    {
        $this->db = $db ?: new Data\Call('entities_by_time');
        $this->entitiesBuilder = $entitiesBuilder?: Core\Di\Di::_()->get('EntitiesBuilder');
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

    /**
     * Fetch all the users who signed up
     */
    protected function getUsers(): void
    {
        //$this->cursor = -1;
        //$this->item = null;

        $timestamps = array_reverse(Timestamps::span(30, 'day'));

        $guids = $this->db->getRow("analytics:signup:day:{$timestamps[$this->period]}", ['limit' => $this->limit, 'offset'=> $this->offset]);
        $guids = array_keys($guids);
        if ($this->offset) {
            array_shift($guids);
        }

        if (empty($guids)) {
            $this->valid = false;
            return;
        }
        $this->valid = true;
        $users = $this->entitiesBuilder->get(['guids' => $guids]);

        foreach ($users as $user) {
            array_push($this->data, $user);
        }

        if ($this->offset == end($guids)) {
            $this->valid = false;
            return;
        }

        $this->offset = end($guids);
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
