<?php
/**
 * This iterator differs from SignupsIterator in that it goes through all signups after a set period
 */
namespace Minds\Core\Analytics\Iterators;

use Minds\Core;
use Minds\Core\Entities;
use Minds\Core\Data;
use Minds\Core\Analytics\Timestamps;

class SignupsOffsetIterator implements \Iterator
{

    private $cursor = -1;
    private $item;

    private $limit = 200;
    private $offset = "";
    private $data = [];

    private $valid = true;

    public function __construct($db = null) {
        $this->db = $db ?: new Data\Call('entities_by_time');
        $this->position = 0;
    }

    public function setPeriod($period = NULL)
    {
        $this->period = $period;
        $this->getUsers();
    }

    protected function getUsers(){
        $this->cursor = -1;
        $this->item = null;

        $timestamps = array_reverse(Timestamps::span($this->period+1, 'day'));

        $guids = $this->db->getRow("user", ['limit' => $this->limit, 'offset'=> $this->offset]);
        if(empty($guids)){
            $this->valid = false;
            return;
        }
        $this->valid = true;
        $this->data = [];
        $users = Entities::get(['guids' => array_keys($guids)]);

        foreach($users as $user){
            if($user->time_created < $timestamps[$this->period]){
                $this->data[] = $user;
            }
        }
        $this->offset = end($this->data)->guid;
    }

    public function rewind() {
        if ($this->cursor >= 0){
            $this->getUsers();
        }
        $this->next();
    }

    public function current() {
        return $this->data[$this->cursor];
    }

    public function key() {
        return $this->cursor;
    }

    public function next() {
        $this->cursor++;
        if(!isset($this->data[$this->cursor])){
            $this->valid = false;
        }
    }

    public function valid() {
        return $this->valid;
    }

}
