<?php

namespace Spec\Minds\Core\Referrals;

use Minds\Common\Repository\Response;
use Minds\Core\Referrals\Referral;
use Minds\Core\Referrals\Repository;
use Minds\Core\Data\Cassandra\Client;
use Minds\Common\Urn;
use Cassandra\Bigint;
use Cassandra\Timestamp;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Spec\Minds\Mocks\Cassandra\Rows;

class RepositorySpec extends ObjectBehavior
{
    private $client;
    protected $urn;

    public function let(Client $client, Urn $urn)
    {
        $this->beConstructedWith($client, $urn);
        $this->client = $client;
        $this->urn = $urn;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Repository::class);
    }

    public function it_should_add_a_referral(Referral $referral)
    {
        $referral->getReferrerGuid()
            ->shouldBeCalled()
            ->willReturn(123);

        $referral->getProspectGuid()
            ->shouldBeCalled()
            ->willReturn(456);

        $referral->getRegisterTimestamp()
            ->shouldBeCalled()
            ->willReturn(78);

        $this->client->request(Argument::that(function ($prepared) {
            $values = $prepared->build()['values'];
            $template = $prepared->build()['string'];
            return strpos($template, 'INSERT INTO referrals') !== false
                && $values[0]->value() == 123
                && $values[1]->value() == 456
                && $values[2]->value() == 78;
        }))
            ->shouldBeCalled()
            ->willReturn(true);

        $this->add($referral)
            ->shouldBe(true);
    }

    public function it_should_update_a_referral()
    {
        $referral = new Referral();
        $referral->setReferrerGuid(123)
            ->setProspectGuid(456)
            ->setJoinTimestamp(789);

        $this
            ->update($referral)
            ->shouldReturn(true);
    }

    public function it_should_return_a_single_referral()
    {
        $this->urn->setUrn('urn:referral:123-456')
            ->shouldBeCalled()
            ->willReturn($this->urn);

        $this->urn->getNss()
            ->shouldBeCalled()
            ->willReturn('123-456');


        $this->client->request(Argument::that(function ($prepared) {
            return true;
        }))
            ->shouldBeCalled()
            ->willReturn(new Rows([
                [
                    'referrer_guid' => new Bigint(123),
                    'prospect_guid' => new Bigint(456),
                    'register_timestamp' => new Timestamp(1545451597777),
                    'join_timestamp' => null,
                    'ping_timestamp' => null,
                ],
            ], 'my-cool-paging-token'));

        $response = $this->get('urn:referral:123-456');

        $response->getReferrerGuid()
            ->shouldBe('123');
        $response->getProspectGuid()
            ->shouldBe('456');
        $response->getRegisterTimestamp()
            ->shouldBe(1545451597777);
        $response->getJoinTimestamp()
            ->shouldBe(null);
        $response->getPingTimestamp()
            ->shouldBe(null);
    }

    public function it_should_return_a_list_of_referrals()
    {
        $this->client->request(Argument::that(function ($prepared) {
            return true;
        }))
            ->shouldBeCalled()
            ->willReturn(new Rows([
                [
                    'referrer_guid' => new Bigint(123),
                    'prospect_guid' => new Bigint(456),
                    'register_timestamp' => new Timestamp(1545451597777),
                    'join_timestamp' => new Timestamp(1545451597778),
                    'ping_timestamp' => null,
                ],
                [
                    'referrer_guid' => new Bigint(123),
                    'prospect_guid' => new Bigint(567),
                    'register_timestamp' => new Timestamp(1545451598888),
                    'join_timestamp' => new Timestamp(1545451598889),
                    'ping_timestamp' => null,
                ],
            ], 'my-cool-paging-token'));

        $response = $this->getList([
            'referrer_guid' => 123,
        ]);


        $response->shouldHaveCount(2);
        $response[0]->getProspectGuid()
            ->shouldBe('456');
        $response[0]->getRegisterTimestamp()
            ->shouldBe(1545451597777);
        $response[0]->getJoinTimestamp()
            ->shouldBe(1545451597778);
    }

    public function it_should_throw_if_no_referrer_guid_during_get_list()
    {
        $opts = [
            'limit' => 1000,
            'offset' => 2000,
        ];

        $this->shouldThrow(new \Exception('Referrer GUID is required'))
            ->duringGetList($opts);
    }

    public function it_should_throw_if_no_prospect_guid_during_add()
    {
        $referral = new Referral();
        $referral->setReferrerGuid(123);
        $referral->setRegisterTimestamp(456);

        $this->shouldThrow(new \Exception('Prospect GUID is required'))
                ->duringAdd($referral);
    }

    public function it_should_throw_if_no_referrer_guid_during_add()
    {
        $referral = new Referral();
        $referral->setProspectGuid(123);
        $referral->setRegisterTimestamp(456);

        $this->shouldThrow(new \Exception('Referrer GUID is required'))
                ->duringAdd($referral);
    }

    public function it_should_throw_if_no_register_timestamp_during_add()
    {
        $referral = new Referral();
        $referral->setReferrerGuid(123);
        $referral->setProspectGuid(456);

        $this->shouldThrow(new \Exception('Register timestamp is required'))
                ->duringAdd($referral);
    }

    public function it_should_throw_if_no_join_timestamp_during_update()
    {
        $referral = new Referral();
        $referral->setReferrerGuid(123);
        $referral->setProspectGuid(456);

        $this->shouldThrow(new \Exception('Join timestamp is required'))
                ->duringUpdate($referral);
    }
}
