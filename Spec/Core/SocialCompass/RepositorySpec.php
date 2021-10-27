<?php

namespace Spec\Minds\Core\SocialCompass;

use Cassandra\Bigint;
use Minds\Core\Data\Cassandra\Client;
use Minds\Core\Data\Cassandra\Prepared\Custom;
use Minds\Core\Data\Interfaces\PreparedInterface;
use Minds\Core\SocialCompass\Repository;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Spec\Minds\Mocks\Cassandra\Rows;

class RepositorySpec extends ObjectBehavior
{
    protected $cql;

    public function let(Client $cql)
    {
        $this->beConstructedWith($cql);
        $this->cql = $cql;
    }
    public function it_is_initializable()
    {
        $this->shouldHaveType(Repository::class);
    }

    public function it_should_get_answers_and_return_iterable_if_entries_found()
    {
//        $this->cql
//            ->request(Argument::type(Custom::class))
//            ->shouldBeCalled()
//            ->willReturn(new Rows([], ""));

        $result = $this->getAnswers(1);
        $result->shouldBeIterable();
    }

    public function getMatchers(): array
    {
        return [
            "beIterable" => function ($subject) {
                return is_iterable($subject);
            }
        ];
    }
}
