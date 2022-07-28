<?php

namespace Spec\Minds\Core\Reports\Jury;

use Cassandra\Bigint;
use Cassandra\Float_;
use Cassandra\Timestamp;
use Cassandra\Type;
use Cassandra\Type\Map;
use Cassandra\Type\Set;
use Minds\Core\Data\Cassandra\Client;
use Minds\Core\Reports\Jury\Decision;
use Minds\Core\Reports\Jury\Repository;
use Minds\Core\Reports\Report;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Spec\Minds\Mocks;

class RepositorySpec extends ObjectBehavior
{
    private $cql;
    private $reportsRepository;

    public function let(Client $cql)
    {
        $this->beConstructedWith($cql);
        $this->cql = $cql;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Repository::class);
    }

    
    public function it_should_return_reports_we_can_use_in_jury()
    {
        $user = new User();
        $user->set('guid', 123);
        $user->setPhoneNumberHash('phoneHash');
        
        $this->cql->request(Argument::that(function ($prepared) {
            $query = $prepared->build();
            $values = $query['values'];

            return $values[0] === 'reported';
        }))
            ->shouldBeCalled()
            ->willReturn(new Mocks\Cassandra\Rows([
                [
                    'user_hashes' => (new Set(Type::text()))
                        ->add('hash'),
                    'entity_urn' => 'urn:activity:123',
                    'entity_owner_guid' => new Bigint(456),
                    'reason_code' => new Float_(2),
                    'sub_reason_code' => new Float_(5),
                    'timestamp' => new Timestamp(time() * 1000),
                    'state' => 'reported',
                    'state_changes' => (new Map(Type::text(), Type::timestamp()))
                        ->set('reported', time() * 1000),
                    'reports' => (new Set(Type::bigint()))
                        ->add(789),
                ],
                [
                    'user_hashes' => (new Set(Type::text()))
                        ->add('hash'),
                    'entity_urn' => 'urn:activity:456',
                    'entity_owner_guid' => new Bigint(456),
                    'reason_code' => new Float_(2),
                    'sub_reason_code' => new Float_(5),
                    'timestamp' => new Timestamp(time() * 1000),
                    'state' => 'reported',
                    'state_changes' => (new Map(Type::text(), Type::timestamp()))
                        ->set('reported', time() * 1000),
                    'reports' => (new Set(Type::bigint()))
                        ->add(789),
                ],
            ], ''));
        
        $response = $this->getList([
            'user' => $user,
            'juryType' => 'initial',
        ]);
        $response->shouldHaveCount(2);
    }

    public function it_should_add_to_initial_jury(Decision $decision)
    {
        $decision->getJurorGuid()
            ->shouldBeCalled()
            ->willReturn(456);

        $decision->getJurorHash()
            ->shouldBeCalled()
            ->willReturn('0xqj1');

        $decision->isUpheld()
            ->shouldBeCalled()
            ->willReturn(true);

        $decision->isAppeal()
            ->shouldBeCalled()
            ->willReturn(false);

        $decision->getReport()
            ->willReturn(
                (new Report)
                ->setEntityUrn('urn:activity:123')
                ->setReasonCode(2)
                ->setSubReasonCode(5)
            );

        $this->cql->request(Argument::that(function ($prepared) {
            $values = $prepared->build()['values'];
            $statement = $prepared->build()['string'];
            return strpos($statement, 'SET initial_jury') !== false
                && $values[0]->values()[456]->value() == true
                && $values[1]->values()[0]->value() === '0xqj1'
                && $values[2] === null
                && $values[3] === 'urn:activity:123'
                && $values[4]->value() == 2
                && $values[5]->value() == 5;
        }))
            ->shouldBeCalled()
            ->willReturn(true);

        $this->add($decision)
            ->shouldBe(true);
    }

    public function it_should_add_to_appeal_jury(Decision $decision)
    {
        $decision->getJurorGuid()
            ->shouldBeCalled()
            ->willReturn(456);

        $decision->getJurorHash()
            ->shouldBeCalled()
            ->willReturn('0xqj1');

        $decision->isUpheld()
            ->shouldBeCalled()
            ->willReturn(true);

        $decision->isAppeal()
            ->shouldBeCalled()
            ->willReturn(true);

        $decision->getReport()
            ->willReturn(
                (new Report)
                ->setEntityUrn('urn:activity:123')
                ->setReasonCode(2)
                ->setSubReasonCode(5)
            );

        $this->cql->request(Argument::that(function ($prepared) {
            $values = $prepared->build()['values'];
            $statement = $prepared->build()['string'];
            return strpos($statement, 'SET appeal_jury') !== false
                && $values[0]->values()[456]->value() == true
                && $values[1]->values()[0]->value() === '0xqj1'
                && $values[2] === null
                && $values[3] === 'urn:activity:123'
                && $values[4]->value() == 2
                && $values[5]->value() == 5;
        }))
            ->shouldBeCalled()
            ->willReturn(true);

        $this->add($decision)
            ->shouldBe(true);
    }

    public function it_should_count_reports()
    {
        $this->cql->request(Argument::that(function ($prepared) {
            $query = $prepared->build();
            $values = $query['values'];

            return $values[0] === 'reported';
        }))
            ->shouldBeCalled()
            ->willReturn([
                [
                    'count' => 100
                ]
            ]);

        $response = $this->count([
            'juryType' => 'reported',
        ]);

        $response->shouldBe(100);
    }
}
