<?php

namespace Spec\Minds\Core\Analytics\Views;

use Minds\Core\Analytics\Views\Repository;
use Minds\Core\Analytics\Views\View;
use Minds\Core\Data\Cassandra\Client as CassandraClient;
use Minds\Core\Data\Cassandra\Prepared\Custom;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class RepositorySpec extends ObjectBehavior
{
    /** @var CassandraClient */
    protected $db;

    public function let(
        CassandraClient $db
    ) {
        $this->beConstructedWith($db);
        $this->db = $db;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Repository::class);
    }

    // function it_should_get_list()
    // {
    // }

    public function it_should_add(View $view)
    {
        $now = strtotime('2019-05-29 12:00:00+0000');

        $view->getTimestamp()->shouldBeCalled()->willReturn($now);
        $view->getYear()->shouldBeCalled()->willReturn(2019);
        $view->getMonth()->shouldBeCalled()->willReturn(5);
        $view->getDay()->shouldBeCalled()->willReturn(29);
        //$view->getUuid()->shouldBeCalled()->willReturn('8a7675d2-f1a7-11e9-8dea-b8aeed731cad');
        $view->getEntityUrn()->shouldBeCalled()->willReturn('urn:test:123123');
        $view->getOwnerGuid()->shouldBeCalled()->wilLReturn('789');
        $view->getPageToken()->shouldBeCalled()->willReturn('95e82e24-f1a7-11e9-8dea-b8aeed731cad');
        $view->getPosition()->shouldBeCalled()->willReturn(5);
        $view->getPlatform()->shouldBeCalled()->willReturn('php');
        $view->getSource()->shouldBeCalled()->willReturn('phpspec');
        $view->getMedium()->shouldBeCalled()->willReturn('test');
        $view->getCampaign()->shouldBeCalled()->willReturn('urn:phpspec:234234');
        $view->getDelta()->shouldBeCalled()->willReturn(100);
        $view->isExternal()->shouldBeCalled()->willReturn(false);

        $this->db->request(Argument::that(function (Custom $prepared) {
            $statement = $prepared->build();

            return stripos($statement['string'], 'insert into views') === 0 &&
                $statement['values'][0] === 2019 &&
                $statement['values'][1]->toInt() === 5 &&
                $statement['values'][2]->toInt() === 29 &&
                $statement['values'][3] === 'urn:test:123123' &&
                $statement['values'][4] === '789' &&
                $statement['values'][5] === '95e82e24-f1a7-11e9-8dea-b8aeed731cad' &&
                $statement['values'][6] === 5 &&
                $statement['values'][7] === 'php' &&
                $statement['values'][8] === 'phpspec' &&
                $statement['values'][9] === 'test' &&
                $statement['values'][10] === 'urn:phpspec:234234' &&
                $statement['values'][11] === 100;
        }), true)->shouldBeCalled()->willReturn(true);

        $this->add($view)->shouldReturn(true);
    }

    public function it_should_add_with_a_timestamp(View $view)
    {
        $now = strtotime('2019-05-29 12:00:00+0000');

        $view->getTimestamp()->shouldBeCalled()->willReturn($now);
        $view->getYear()->shouldBeCalled()->willReturn(null);
        $view->getMonth()->shouldBeCalled()->willReturn(null);
        $view->getDay()->shouldBeCalled()->willReturn(null);
        //$view->getUuid()->shouldBeCalled()->willReturn(null);
        $view->getEntityUrn()->shouldBeCalled()->willReturn('urn:test:123123');
        $view->getOwnerGuid()->shouldBeCalled()->willReturn(789);
        $view->getPageToken()->shouldBeCalled()->willReturn('95e82e24-f1a7-11e9-8dea-b8aeed731cad');
        $view->getPosition()->shouldBeCalled()->willReturn(5);
        $view->getPlatform()->shouldBeCalled()->willReturn('php');
        $view->getSource()->shouldBeCalled()->willReturn('phpspec');
        $view->getMedium()->shouldBeCalled()->willReturn('test');
        $view->getCampaign()->shouldBeCalled()->willReturn('urn:phpspec:234234');
        $view->getDelta()->shouldBeCalled()->willReturn(100);
        $view->isExternal()->shouldBeCalled()->willReturn(false);

        $this->db->request(Argument::that(function (Custom $prepared) use ($now) {
            $statement = $prepared->build();

            return stripos($statement['string'], 'insert into views') === 0 &&
                $statement['values'][0] === 2019 &&
                $statement['values'][1]->toInt() === 5 &&
                $statement['values'][2]->toInt() === 29 &&
                $statement['values'][3] === 'urn:test:123123' &&
                $statement['values'][4] === '789' &&
                $statement['values'][5] === '95e82e24-f1a7-11e9-8dea-b8aeed731cad' &&
                $statement['values'][6] === 5 &&
                $statement['values'][7] === 'php' &&
                $statement['values'][8] === 'phpspec' &&
                $statement['values'][9] === 'test' &&
                $statement['values'][10] === 'urn:phpspec:234234' &&
                $statement['values'][11] === 100;
        }), true)->shouldBeCalled()->willReturn(true);

        //$this->db->request(Argument::type(Custom::class), true)->shouldBeCalled();

        $this->add($view)->shouldReturn(true);
    }
}
