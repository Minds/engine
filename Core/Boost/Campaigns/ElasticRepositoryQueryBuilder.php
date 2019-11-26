<?php

namespace Minds\Core\Boost\Campaigns;

class ElasticRepositoryQueryBuilder
{
    protected $opts;
    protected $must;
    protected $mustNot;
    protected $sort;

    public function setOpts(array $opts): self
    {
        $this->opts = $opts;
        return $this;
    }

    public function reset()
    {
        $this->opts = [
            'type' => null,
            'guid' => null,
            'owner_guid' => null,
            'entity_urn' => null,
            'state' => null,
            'rating' => null,
            'quality' => null,
            'offset' => null,
            'sort' => 'asc'
        ];
        $this->must = [];
        $this->mustNot = [];
        $this->sort = [];
    }

    public function query(): array
    {
        $this->reset();

        $this->parseType();
        $this->parseGuid();
        $this->parseEntityUrn();
        $this->parseState();
        $this->parseRating();
        $this->parseQuality();
        $this->parseOffset();
        $this->parseSort();

        return $this->body();
    }

    public function parseType(): void
    {
        if ($this->opts['type']) {
            $this->must[] = [
                'term' => [
                    'type' => $this->opts['type'],
                ],
            ];
        }
    }

    public function parseGuid(): void
    {
        if ($this->opts['guid']) {
            $this->must[] = [
                'term' => [
                    '_id' => (string)$this->opts['guid'],
                ],
            ];
        } elseif ($this->opts['owner_guid']) {
            $this->must[] = [
                'term' => [
                    'owner_guid' => (string)$this->opts['owner_guid'],
                ],
            ];
        }
    }

    public function parseEntityUrn(): void
    {
        if ($this->opts['entity_urn']) {
            $this->must[] = [
                'term' => [
                    'entity_urn' => $this->opts['entity_urn'],
                ],
            ];
        }
    }

    public function parseState(): void
    {
        if ($this->opts['state'] === 'approved') {
            $this->must[] = [
                'exists' => [
                    'field' => '@reviewed',
                ],
            ];
        } elseif ($this->opts['state'] === 'in_review') {
            $this->mustNot[] = [
                'exists' => [
                    'field' => '@reviewed',
                ],
            ];
        }

        if ($this->opts['state'] === 'approved' || $this->opts['state'] === 'in_review') {
            $this->mustNot[] = [
                'exists' => [
                    'field' => '@completed',
                ],
            ];

            $this->mustNot[] = [
                'exists' => [
                    'field' => '@rejected',
                ],
            ];

            $this->mustNot[] = [
                'exists' => [
                    'field' => '@revoked',
                ],
            ];
        }
    }

    public function parseRating(): void
    {
        if ($this->opts['rating']) {
            $this->must[] = [
                'range' => [
                    'rating' => [
                        'lte' => $this->opts['rating'],
                    ],
                ],
            ];
        }
    }

    public function parseQuality(): void
    {
        if ($this->opts['quality']) {
            $this->must[] = [
                'range' => [
                    'quality' => [
                        'gte' => $this->opts['quality'],
                    ],
                ],
            ];
        }
    }

    public function parseOffset(): void
    {
        if ($this->opts['offset']) {
            $rangeKey = $this->opts['sort'] === 'asc' ? 'gt' : 'lt';

            $this->must[] = [
                'range' => [
                    '@timestamp' => [
                        $rangeKey => $this->opts['offset'],
                    ],
                ],
            ];
        }
    }

    public function parseSort(): void
    {
        $this->sort = [
            '@timestamp' => $this->opts['sort'] ?? 'asc',
        ];
    }

    public function body(): array
    {
        return [
            'query' => [
                'bool' => [
                    'must' => $this->must,
                    'must_not' => $this->mustNot,
                ],
            ],
            'sort' => $this->sort,
        ];
    }
}
