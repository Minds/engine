<?php

namespace Spec\Minds\Core\Analytics\Metrics;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

use Minds\Core\EntitiesBuilder;
use Minds\Core\Data\ElasticSearch\Client;
use Minds\Core\Data\ElasticSearch\Prepared\Index;
use Minds\Core\AccountQuality\ManagerInterface as AccountQualityManagerInterface;
use Minds\Core\Analytics\PostHog\PostHogService;
use Minds\Entities\Enums\FederatedEntitySourcesEnum;
use Minds\Entities\User;
use PhpSpec\Wrapper\Collaborator;

class EventSpec extends ObjectBehavior
{
    /** @var Client */
    protected $es;

    protected Collaborator $postHogServiceMock;

    /** @var EntitiesBuilder */
    protected $entitiesBuilder;

    /** @var AccountQualityManagerInterface */
    private $accountQualityManager;

    public function let(
        Client $es,
        EntitiesBuilder $entitiesBuilder,
        PostHogService $postHogServiceMock,
        AccountQualityManagerInterface $accountQualityManager
    ) {
        $this->beConstructedWith(
            $es,
            $postHogServiceMock,
            $entitiesBuilder,
            $accountQualityManager
        );
        $this->es = $es;
        $this->postHogServiceMock = $postHogServiceMock;
        $this->entitiesBuilder = $entitiesBuilder;
        $this->accountQualityManager = $accountQualityManager;
        $_COOKIE['minds_pseudoid'] = '';
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType('Minds\Core\Analytics\Metrics\Event');
    }

    public function it_should_set_a_variable()
    {
        $this->setType('hello')->shouldReturn($this);
        $this->getData()->shouldReturn([
            'type' => 'hello'
        ]);
    }

    public function it_should_set_camel_case()
    {
        $this->setOwnerGuid('hello')->shouldReturn($this);
        $this->setNotcamelcase('boo')->shouldReturn($this);
        $this->setSnake_Case('woo')->shouldReturn($this);
        $this->getData()->shouldReturn([
            'owner_guid' => 'hello',
            'notcamelcase' => 'boo',
            'snake__case' => 'woo'
        ]);
    }

    public function it_should_push(Index $prepared)
    {
        /*$prepared->query([
            'body' => $this->getData(),
            'index' => "minds-metrics-" . date('m-Y', time()),
            'type' => 'action',
            'client' => [
                'timeout' => 2,
                'connect_timeout' => 1
            ]
        ])->shouldBeCalled();
        $prepared->build()->shouldBeCalled();*/

        $this->es->request(Argument::type('Minds\Core\Data\ElasticSearch\Prepared\Index'))
            ->shouldBeCalled()
            ->willReturn(true);

        $this->setType('action');
        $this->push()->shouldBe(true);
        $this->getData()->shouldHaveKey('@timestamp');
    }

    public function it_should_push_with_account_quality_score(User $user)
    {
        $userGuid = '123';

        $this->es->request(Argument::type('Minds\Core\Data\ElasticSearch\Prepared\Index'))
            ->shouldBeCalled()
            ->willReturn(true);

        $user->isPlus()->shouldBeCalled()->willReturn(true);
        $user->getGuid()->shouldBeCalled()->willReturn($userGuid);
        $user->getSource()->shouldBeCalled()->willReturn(FederatedEntitySourcesEnum::LOCAL);

        $this->setUser($user);
        $this->setType('action');
        $this->setAction('vote:up');
        
        $this->accountQualityManager->getAccountQualityScoreAsFloat("123")
            ->willReturn((float) 1);

        $this->entitiesBuilder->single('123')->willReturn($user);
        
        $this->postHogServiceMock->capture(Argument::any(), Argument::any(), Argument::any())->shouldBeCalled();

        $this->push()->shouldBe(true);
        $this->getData()->shouldHaveKey('@timestamp');
        $this->getData()->shouldHaveKey('account_quality_score');
    }

    public function it_should_post_action_to_posthog(User $user)
    {
        $user->getGuid()->willReturn('123');
        $user->isPlus()->willReturn(false);
        $user->getSource()->shouldBeCalled()->willReturn(FederatedEntitySourcesEnum::LOCAL);
        $this->entitiesBuilder->single('123')->willReturn($user);

        $this->accountQualityManager->getAccountQualityScoreAsFloat("123")
            ->willReturn((float) 1);
        
        $this->postHogServiceMock->capture(
            'activity_vote_up',
            $user,
            [
                'entity_type' => 'activity',
            ],
        )
            ->shouldBeCalled()
            ->willReturn(true);

        $this->es->request(Argument::type('Minds\Core\Data\ElasticSearch\Prepared\Index'))
            ->shouldBeCalled()
            ->willReturn(true);

        $this->setType('action');
        $this->setAction('vote:up');
        $this->setUserGuid('123');
        $this->setEntityType('activity');

        $this->push()->shouldBe(true);
    }


    public function it_should_post_action_to_posthog_with_custom_event_name(User $user)
    {
        $user->getGuid()->willReturn('123');
        $user->isPlus()->willReturn(false);
        $user->getSource()->shouldBeCalled()->willReturn(FederatedEntitySourcesEnum::LOCAL);
        $this->entitiesBuilder->single('123')->willReturn($user);

        $this->accountQualityManager->getAccountQualityScoreAsFloat("123")
            ->willReturn((float) 1);
        
        $this->postHogServiceMock->capture(
            'object_verb',
            $user,
            [
                'entity_type' => 'activity',
            ],
        )
            ->shouldBeCalled()
            ->willReturn(true);

        $this->es->request(Argument::type('Minds\Core\Data\ElasticSearch\Prepared\Index'))
            ->shouldBeCalled()
            ->willReturn(true);

        $this->setType('action');
        $this->setAction('vote:up');
        $this->setUserGuid('123');
        $this->setEntityType('activity');
        $this->setEventName('object', 'verb');

        $this->push()->shouldBe(true);
    }

    public function it_should_post_action_to_posthog_with_remapped_client_meta(User $user)
    {
        $user->getGuid()->willReturn('123');
        $user->isPlus()->willReturn(false);
        $user->getSource()->shouldBeCalled()->willReturn(FederatedEntitySourcesEnum::LOCAL);
        $this->entitiesBuilder->single('123')->willReturn($user);

        $this->accountQualityManager->getAccountQualityScoreAsFloat("123")
            ->willReturn((float) 1);
        
        $this->postHogServiceMock->capture(
            'object_verb',
            $user,
            [
                'entity_type' => 'activity',
                'cm_platform' => 'mobile',
                'cm_source' => 'boost',
                'cm_medium' => 'feed',
                'cm_campaign' => 'urn:boost:123',
                'cm_delta' => 12,
                'cm_position' => 1,
                'cm_served_by_guid' => '123',
            ],
        )
            ->shouldBeCalled()
            ->willReturn(true);

        $this->es->request(Argument::type('Minds\Core\Data\ElasticSearch\Prepared\Index'))
            ->shouldBeCalled()
            ->willReturn(true);

        $this->setType('action');
        $this->setAction('vote:up');
        $this->setUserGuid('123');
        $this->setEntityType('activity');
        $this->setEventName('object', 'verb');
        $this->setClientMeta([
            'platform' => 'mobile',
            'source' => 'boost',
            'medium' => 'feed',
            'campaign' => 'urn:boost:123',
            'delta' => 12,
            'position' => 1,
            'served_by_guid' => '123',
        ]);

        $this->push()->shouldBe(true);
    }
}
