<?php

namespace Spec\Minds\Core\Search;

use Minds\Core\Data\ElasticSearch\Client;
use Minds\Core\Data\ElasticSearch\Prepared\Match;
use Minds\Core\Data\ElasticSearch\Prepared\Suggest;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class SearchSpec extends ObjectBehavior
{
    protected $_client;
    protected $_index = 'phpspec';

    public function let(
        Client $client
    ) {
        $this->_client = $client;

        $this->beConstructedWith($client, $this->_index);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType('Minds\Core\Search\Search');
    }

    public function it_should_suggest()
    {
        $this->_client->request(Argument::that(function ($prepared) {
            if (!($prepared instanceof Suggest)) {
                return false;
            }

            $query = $prepared->build();
            return $query['index'] == 'phpspec' && $query['body'];
        }))
            ->shouldBeCalled()
            ->willReturn([ 'suggest' => [ 'autocomplete' => [
                [ 'options' => [
                    [ '_source' => [ 'guid' => 5000 ] ],
                    [ '_source' => [ 'guid' => 5001 ] ],
                ]]
            ]]]);

        $this
            ->suggest('user', 'phpspec', 2)
            ->shouldReturn([
                [ 'guid' => 5000 ],
                [ 'guid' => 5001 ],
            ]);
    }
}
