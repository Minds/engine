<?php

namespace Spec\Minds\Core\Search\SortingAlgorithms;

use Minds\Core\Experiments\Manager as ExperimentsManager;
use Minds\Core\Search\SortingAlgorithms\TopV2;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;

class TopV2Spec extends ObjectBehavior
{
    private Collaborator $experimentsManager;

    public function let(
        ExperimentsManager $experimentsManager
    ) {
        $this->experimentsManager = $experimentsManager;
        $this->beConstructedWith($experimentsManager);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(TopV2::class);
    }

    public function it_should_get_function_scores_with_no_video_weight_variation()
    {
        $this->getFunctionScores()
            ->shouldBe([
                [
                    'filter' => [
                        'term' => [
                            'custom_type' => 'video',
                        ],
                    ],
                    'weight' => 0,
                ],
                [
                    'field_value_factor' => [
                        'field' => 'votes:up',
                        'factor' => 1,
                        'modifier' => 'sqrt',
                        'missing' => 0,
                    ],
                ],
                [
                    'filter' => [
                        'range' => [
                            '@timestamp' => [
                                'gte' => 'now-12h',
                            ]
                        ],
                    ],
                    'weight' => 4,
                ],
                [
                    'filter' => [
                        'range' => [
                            '@timestamp' => [
                                'lt' => 'now-12h',
                                'gte' => 'now-36h',
                            ]
                        ],
                    ],
                    'weight' => 2,
                ],
                [
                    'gauss' => [
                        '@timestamp' => [
                            'offset' => '12h',
                            'scale' => '24h',
                            'decay' => 0.9
                        ],
                    ],
                    'weight' => 20,
                ]
            ]);
    }
}
