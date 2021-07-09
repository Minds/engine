<?php

namespace Spec\Minds\Core\Notifications\EmailDigests;

use Minds\Core\Data\Cassandra\Client;
use Minds\Core\Data\Cassandra\Scroll;
use Minds\Core\Notifications\EmailDigests\EmailDigestMarker;
use Minds\Core\Notifications\EmailDigests\EmailDigestOpts;
use Minds\Core\Notifications\EmailDigests\Repository;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class RepositorySpec extends ObjectBehavior
{
    /** @var Client */
    protected $cql;

    /** @var Scroll */
    protected $scroll;
    
    public function let(Client $cql, Scroll $scroll)
    {
        $this->beConstructedWith($cql, $scroll);
        $this->cql = $cql;
        $this->scroll = $scroll;
    }
    
    public function it_is_initializable()
    {
        $this->shouldHaveType(Repository::class);
    }

    public function it_should_add_to_db(EmailDigestMarker $marker)
    {
        $this->cql->request(Argument::that(function ($prepeared) {
            return true;
        }))
            ->willReturn(true);

        $this->add($marker)
            ->shouldBe(true);
    }

    public function it_should_get_list_from_db()
    {
        $this->scroll->request(Argument::that(function ($prepeared) {
            return true;
        }), 'paging-token')
            ->willReturn(true);

        $opts = new EmailDigestOpts();
        $this->getList($opts);
    }
}
