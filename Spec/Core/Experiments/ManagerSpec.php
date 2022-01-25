<?php

namespace Spec\Minds\Core\Experiments;

use GuzzleHttp\Client;
use Minds\Core\Config\Config;
use Minds\Core\Experiments\Manager;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

use Minds\Core\Experiments\Sampler;
use Minds\Core\Experiments\Bucket;
use Minds\Core\Experiments\Hypotheses\Homepage121118;
use Zend\Diactoros\Response\JsonResponse;

class ManagerSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    public function it_should_return_a_list_of_growthbook_features(Client $httpClient, Config $config)
    {
        $this->beConstructedWith(null, null, $httpClient, $config);

        $config->get('growthbook')
            ->willReturn([
                'features_endpoint' => 'https://growthbook-api.phpspec.test/api/features/key_stub',
            ]);

        $httpClient->request('GET', 'https://growthbook-api.phpspec.test/api/features/key_stub', Argument::any())
            ->willReturn(new JsonResponse([
                'features' => [
                    'discovery-homepage' => [
                        'defaultValue' => false,
                    ]
                ],
            ]));

        $this->getFeatures(false)
            ->shouldBe([
                'discovery-homepage' => [
                    'defaultValue' => false,
                ]
            ]);
        //$this->setUser()->getExperiments()->shouldHaveCount(4);
    }

    // public function it_should_return_bucket_for_experiment(
    //     Sampler $sampler,
    //     Homepage121118 $hypothesis
    // ) {
    //     $this->beConstructedWith($sampler);

    //     $sampler->setUser(null)
    //         ->shouldBeCalled();

    //     $sampler->setHypothesis(new Homepage121118)
    //         ->shouldBeCalled()
    //         ->willReturn($sampler);

    //     $bucket = new Bucket();
    //     $bucket->setId('variant1')
    //         ->setWeight(10);

    //     $sampler->getBucket()
    //         ->shouldBeCalled()
    //         ->willReturn($bucket);

    //     $this->getBucketForExperiment('Homepage121118')
    //         ->shouldReturn($bucket);
    // }
}
