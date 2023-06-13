<?php
namespace Minds\Core\Analytics\Iterators;

use Minds\Core;
use Minds\Core\Di\Di;

/**
 * Iterator that loops through all signups after a set period
 */
class EventsIterator implements \Iterator
{
    private $cursor = -1;

    private $item;

    private $limit = 10000;
    private $offset = "";
    private $data = [];

    private $type;
    private $distinct;
    private $body;
    private $terms = [];

    private $valid = true;


    private $elastic;
    private $index;
    private $position;
    private $period;

    public function __construct($elastic = null, $index = null)
    {
        $this->elastic = $elastic ?: Di::_()->get('Database\ElasticSearch');
        $this->index = $index ?: Di::_()->get('Config')->get('elasticsearch')['metrics_index'] . '-*';
        $this->position = 0;
    }

    public function setType(mixed $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function setTerms(mixed $terms): self
    {
        $this->terms = $terms;
        return $this;
    }

    /**
     * Sets the period to cycle through
     * @param string|null $period
     */
    public function setPeriod(?string $period = null): void
    {
        $this->period = $period;
    }

    /**
     * Fetch all the users who signed up in a certain period
     * @return array
     */
    protected function getList(): array
    {
        $body = [
            'query' => [
                'range' => [
                    '@timestamp' => [
                        'gte' => $this->period * 1000
                        ]
                    ]
            ]
        ];

        if ($this->terms) {
            foreach ($this->terms as $term) {
                $body['aggs'][$term] = [
                    'terms' => [
                        'field' => $term,
                        'size' => $this->limit
                    ]
                ];
            }
        }


        $prepared = new Core\Data\ElasticSearch\Prepared\Search();
        $prepared->query([
            'body' => $body,
            'index' => $this->index,
            'type' => $this->type,
            'size' => $this->limit,
            'from' => (int) $this->offset,
            'client' => [
                'timeout' => 2,
                'connect_timeout' => 1
            ]
        ]);

        $result = $this->elastic->request($prepared);

        if ($this->terms) {
            foreach ($this->terms as $term) {
                foreach ($result['aggregations'][$term]['buckets'] as $item) {
                    $this->data[] = $item['key'];
                }
            }
        }

        $this->offset = count($this->data);

        return $this->data;
    }

    /**
     * Rewind the array cursor
     * @return void
     */
    public function rewind(): void
    {
        if ($this->cursor >= 0) {
            $this->getList();
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
        if (!isset($this->data[$this->cursor]) && !($this->data && $this->terms)) {
            $this->getList();
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
