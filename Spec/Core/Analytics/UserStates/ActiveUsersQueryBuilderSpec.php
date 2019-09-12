<?php

namespace Spec\Minds\Core\Analytics\UserStates;

use Minds\Core\Analytics\UserStates\ActiveUsersQueryBuilder;
use PhpSpec\ObjectBehavior;

class ActiveUsersQueryBuilderSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(ActiveUsersQueryBuilder::class);
    }

    public function it_should_produce_a_valid_query()
    {
        $from = strtotime('midnight -2 days');
        $to = strtotime('midnight');
        $this->setPartitions(10)->setPage(2)->setFrom($from)->setTo($to)->query()->shouldBe($this->exampleQuery());
    }

    private function exampleQuery(): array
    {
        $query = '{
  "index": "minds-metrics-*",
  "size": "0",
  "body": {
    "query": {
      "bool": {
        "must": [
          {
            "match_phrase": {
              "action.keyword": {
                "query": "active"
              }
            }
          },
          {
            "range": {
              "@timestamp": {
                "from": 1568073600000,
                "to": 1568246400000,
                "format": "epoch_millis"
              }
            }
          }
        ]
      }
    },
    "aggs": {
      "users": {
        "terms": {
          "field": "user_guid.keyword",
          "size": 5000,
          "include": {
            "partition": 2,
            "num_partitions": 10
          }
        },
        "aggs": {
          "1568160000": {
            "date_range": {
              "field": "@timestamp",
              "ranges": [
                {
                  "from": 1568160000000,
                  "to": 1568246400000
                }
              ]
            }
          },
          "count-1568160000": {
            "sum_bucket": {
              "buckets_path": "1568160000>_count"
            }
          },
          "1568246400": {
            "date_range": {
              "field": "@timestamp",
              "ranges": [
                {
                  "from": 1568246400000,
                  "to": 1568332800000
                }
              ]
            }
          },
          "count-1568246400": {
            "sum_bucket": {
              "buckets_path": "1568246400>_count"
            }
          }
        }
      }
    }
  }
}';
        return json_decode($query, true);
    }
}
