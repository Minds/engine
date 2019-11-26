<?php

namespace Spec\Minds\Core\Boost\Campaigns;

use Minds\Common\Repository\Response;
use Minds\Core\Boost\Campaigns\Campaign;
use Minds\Core\Boost\Campaigns\CampaignException;
use Minds\Core\Boost\Campaigns\Delegates\CampaignUrnDelegate;
use Minds\Core\Boost\Campaigns\Delegates\NormalizeDatesDelegate;
use Minds\Core\Boost\Campaigns\Delegates\NormalizeEntityUrnsDelegate;
use Minds\Core\Boost\Campaigns\Delegates\NormalizeHashtagsDelegate;
use Minds\Core\Boost\Campaigns\Delegates\PaymentsDelegate;
use Minds\Core\Boost\Campaigns\ElasticRepository;
use Minds\Core\Boost\Campaigns\Manager;
use Minds\Core\Boost\Campaigns\Metrics;
use Minds\Core\Boost\Campaigns\Payments\Payment;
use Minds\Core\Boost\Campaigns\Repository;
use Minds\Core\Boost\Campaigns\Payments\Repository as PaymentsRepository;
use Minds\Core\Queue\Interfaces\QueueClient;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ManagerSpec extends ObjectBehavior
{
    /** @var Repository */
    protected $repository;
    /** @var ElasticRepository */
    protected $elasticRepository;
    /** @var Metrics */
    protected $metrics;
    /** @var PaymentsRepository */
    protected $paymentsRepository;
    /** @var QueueClient */
    protected $queueClient;
    /** @var CampaignUrnDelegate */
    protected $campaignUrnDelegate;
    /** @var NormalizeDatesDelegate */
    protected $normalizeDatesDelegate;
    /** @var NormalizeEntityUrnsDelegate */
    protected $normalizeEntityUrnsDelegate;
    /** @var NormalizeHashtagsDelegate */
    protected $normalizeHashtagsDelegate;
    /** @var PaymentsDelegate */
    protected $paymentsDelegate;
    /** @var User */
    protected $user;

    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    public function let(
        Repository $repository,
        ElasticRepository $elasticRepository,
        Metrics $metrics,
        PaymentsRepository $paymentsRepository,
        QueueClient $queueClient,
        CampaignUrnDelegate $campaignUrnDelegate,
        NormalizeDatesDelegate $normalizeDatesDelegate,
        NormalizeEntityUrnsDelegate $normalizeEntityUrnsDelegate,
        NormalizeHashtagsDelegate $normalizeHashtagsDelegate,
        PaymentsDelegate $paymentsDelegate,
        User $user
    ) {
        $this->beConstructedWith(
            $repository,
            $elasticRepository,
            $metrics,
            $paymentsRepository,
            $queueClient,
            $campaignUrnDelegate,
            $normalizeDatesDelegate,
            $normalizeEntityUrnsDelegate,
            $normalizeHashtagsDelegate,
            $paymentsDelegate
        );

        $this->repository = $repository;
        $this->elasticRepository = $elasticRepository;
        $this->metrics = $metrics;
        $this->paymentsRepository = $paymentsRepository;
        $this->queueClient = $queueClient;
        $this->campaignUrnDelegate = $campaignUrnDelegate;
        $this->normalizeDatesDelegate = $normalizeDatesDelegate;
        $this->normalizeEntityUrnsDelegate = $normalizeEntityUrnsDelegate;
        $this->normalizeHashtagsDelegate = $normalizeHashtagsDelegate;
        $this->paymentsDelegate = $paymentsDelegate;
        $this->user = $user;

        $this->setActor($user);
    }

    public function it_should_get_a_list_of_campaigns_from_elastic_repository(Response $response)
    {
        $this->elasticRepository->getCampaigns(Argument::type('array'))->shouldBeCalled()->willReturn($response);
        $response->map(Argument::type('Callable'))->willReturn($response);
        $this->getCampaigns()->shouldReturn($response);
    }

    public function it_should_get_a_list_of_campaigns_from_cassandra_repository(Response $response)
    {
        $this->repository->getCampaignByGuid(Argument::type('array'))->shouldBeCalled()->willReturn($response);
        $response->map(Argument::type('Callable'))->willReturn($response);
        $this->getCampaigns(['useElastic' => false])->shouldReturn($response);
    }

    public function it_should_get_a_list_of_boosts_and_campaigns_from_elastic_repository(Response $response)
    {
        $this->elasticRepository->getCampaignsAndBoosts(Argument::type('array'))->shouldBeCalled()->willReturn($response);
        $response->map(Argument::type('Callable'))->willReturn($response);
        $this->getCampaignsAndBoosts()->shouldReturn($response);
    }

    public function it_should_get_a_campaign_by_urn(Response $response, Campaign $campaign1)
    {
        $this->repository->getCampaignByGuid(Argument::type('array'))->shouldBeCalled()->willReturn($response);
        $response->map(Argument::type('Callable'))->willReturn($response);
        $response->toArray()->shouldBeCalled()->willReturn([
            $campaign1
        ]);
        $this->getCampaignByUrn('urn:campaign:1234')->shouldReturn($campaign1);
    }

    public function it_should_throw_exception_if_no_owner_on_create_campaign(Campaign $campaign)
    {
        $this->campaignUrnDelegate->onCreate($campaign)->shouldBeCalled()->willReturn($campaign);
        $campaign->setOwner($this->user)->shouldBeCalled();
        $campaign->getOwnerGuid()->shouldBeCalled()->willReturn(null);
        $this->shouldThrow(CampaignException::class)->duringCreateCampaign($campaign);
    }

    public function it_should_throw_exception_if_no_name_on_create_campaign(Campaign $campaign)
    {
        $this->campaignUrnDelegate->onCreate($campaign)->shouldBeCalled()->willReturn($campaign);
        $campaign->setOwner($this->user)->shouldBeCalled();
        $campaign->getOwnerGuid()->shouldBeCalled()->willReturn(1234);
        $campaign->getName()->shouldBeCalled()->willReturn(null);
        $this->shouldThrow(CampaignException::class)->duringCreateCampaign($campaign);
    }

    public function it_should_throw_exception_if_not_valid_campaign_type_on_create_campaign(Campaign $campaign)
    {
        $this->campaignUrnDelegate->onCreate($campaign)->shouldBeCalled()->willReturn($campaign);
        $campaign->setOwner($this->user)->shouldBeCalled();
        $campaign->getOwnerGuid()->shouldBeCalled()->willReturn(1234);
        $campaign->getName()->shouldBeCalled()->willReturn('Test Campaign');
        $campaign->getType()->shouldBeCalled()->willReturn(null);
        $this->shouldThrow(CampaignException::class)->duringCreateCampaign($campaign);
    }

    public function it_should_throw_exception_if_invalid_checksum_on_create_campaign(Campaign $campaign)
    {
        $this->campaignUrnDelegate->onCreate($campaign)->shouldBeCalled()->willReturn($campaign);
        $campaign->setOwner($this->user)->shouldBeCalled();
        $campaign->getOwnerGuid()->shouldBeCalled()->willReturn(1234);
        $campaign->getName()->shouldBeCalled()->willReturn('Test Campaign');
        $campaign->getType()->shouldBeCalled()->willReturn('newsfeed');
        //$campaign->getEntityUrns()->shouldBeCalled()->willReturn(['urn:activity:12345']);
        //$campaign->getGuid()->shouldBeCalled()->willReturn(12345);
        $campaign->getChecksum()->shouldBeCalled()->willReturn(null);
        $this->shouldThrow(CampaignException::class)->duringCreateCampaign($campaign);
    }

    public function it_should_create_campaign(Campaign $campaign)
    {
        $this->campaignUrnDelegate->onCreate($campaign)->shouldBeCalled()->willReturn($campaign);
        $campaign->setOwner($this->user)->shouldBeCalled();
        $campaign->getOwnerGuid()->shouldBeCalled()->willReturn(1234);
        $campaign->getName()->shouldBeCalled()->willReturn('Test Campaign');
        $campaign->getType()->shouldBeCalled()->willReturn('newsfeed');
        $campaign->getChecksum()->shouldBeCalled()->willReturn('0x1234abcd');
        $this->normalizeDatesDelegate->onCreate($campaign)->shouldBeCalled()->willReturn($campaign);
        $this->normalizeEntityUrnsDelegate->onCreate($campaign)->shouldBeCalled()->willReturn($campaign);
        $this->normalizeHashtagsDelegate->onCreate($campaign)->shouldBeCalled()->willReturn($campaign);
        $this->paymentsDelegate->onCreate($campaign, null)->shouldBeCalled()->willReturn($campaign);
        $this->createCampaign($campaign);
    }

    public function it_should_throw_exception_if_not_owner_or_admin_on_update(Campaign $campaignRef, Campaign $campaign, Response $response)
    {
        $urn = 'urn:campaign:1234';
        $campaignRef->getUrn()->shouldBeCalled()->willReturn($urn);

        $this->repository->getCampaignByGuid(Argument::type('array'))->shouldBeCalled()->willReturn($response);
        $response->map(Argument::type('Callable'))->willReturn($response);
        $response->toArray()->shouldBeCalled()->willReturn([
            $campaign
        ]);

        $this->user->get('guid')->shouldBeCalled()->willReturn('1234');
        $campaign->getOwnerGuid()->shouldBeCalled()->willReturn('5678');
        $this->user->isAdmin()->shouldBeCalled()->willReturn(false);

        $this->shouldThrow(CampaignException::class)->duringUpdateCampaign($campaignRef);
    }

    public function it_should_throw_exception_if_not_an_editable_state_on_update(Campaign $campaignRef, Campaign $campaign, Response $response)
    {
        $urn = 'urn:campaign:1234';
        $campaignRef->getUrn()->shouldBeCalled()->willReturn($urn);

        $this->repository->getCampaignByGuid(Argument::type('array'))->shouldBeCalled()->willReturn($response);
        $response->map(Argument::type('Callable'))->willReturn($response);
        $response->toArray()->shouldBeCalled()->willReturn([
            $campaign
        ]);

        $this->user->get('guid')->shouldBeCalled()->willReturn('1234');
        $campaign->getOwnerGuid()->shouldBeCalled()->willReturn('1234');
        $this->user->isAdmin()->shouldBeCalled()->willReturn(false);

        $campaign->getDeliveryStatus()->shouldBeCalled()->willReturn(Campaign::STATUS_COMPLETED);

        $this->shouldThrow(CampaignException::class)->duringUpdateCampaign($campaignRef);
    }

    public function it_should_throw_an_exception_if_no_name_on_update(Campaign $campaignRef, Campaign $campaign, Response $response)
    {
        $urn = 'urn:campaign:1234';
        $campaignRef->getUrn()->shouldBeCalled()->willReturn($urn);

        $this->repository->getCampaignByGuid(Argument::type('array'))->shouldBeCalled()->willReturn($response);
        $response->map(Argument::type('Callable'))->willReturn($response);
        $response->toArray()->shouldBeCalled()->willReturn([
            $campaign
        ]);

        $this->user->get('guid')->shouldBeCalled()->willReturn('1234');
        $campaign->getOwnerGuid()->shouldBeCalled()->willReturn('1234');
        $this->user->isAdmin()->shouldBeCalled()->willReturn(false);

        $campaign->getDeliveryStatus()->shouldBeCalled()->willReturn(Campaign::STATUS_CREATED);
        $campaignRef->getName()->shouldBeCalled()->willReturn('');

        $this->shouldThrow(CampaignException::class)->duringUpdateCampaign($campaignRef);
    }

    public function it_should_update_campaign(Campaign $campaignRef, Campaign $campaign, Response $response)
    {
        $urn = 'urn:campaign:1234';
        $campaignRef->getUrn()->shouldBeCalled()->willReturn($urn);

        $this->repository->getCampaignByGuid(Argument::type('array'))->shouldBeCalled()->willReturn($response);
        $response->map(Argument::type('Callable'))->willReturn($response);
        $response->toArray()->shouldBeCalled()->willReturn([
            $campaign
        ]);

        $this->user->get('guid')->shouldBeCalled()->willReturn('1234');
        $campaign->getOwnerGuid()->shouldBeCalled()->willReturn('1234');
        $this->user->isAdmin()->shouldBeCalled()->willReturn(false);

        $campaign->getDeliveryStatus()->shouldBeCalled()->willReturn(Campaign::STATUS_CREATED);
        $campaignRef->getName()->shouldBeCalled()->willReturn('New Campaign Name');

        $campaign->setName('New Campaign Name')->shouldBeCalled();
        $this->normalizeDatesDelegate->onUpdate($campaign, $campaignRef)->shouldBeCalled()->willReturn($campaign);
        $this->normalizeHashtagsDelegate->onUpdate($campaign, $campaignRef)->shouldBeCalled()->willReturn($campaign);
        $this->paymentsDelegate->onUpdate($campaign, $campaignRef, null)->shouldBeCalled()->willReturn($campaign);

        $this->repository->putCampaign($campaign)->shouldBeCalled();
        $this->elasticRepository->putCampaign($campaign)->shouldBeCalled();
        $this->queueClient->setQueue('BoostCampaignDispatcher')->shouldBeCalled()->willReturn($this->queueClient);
        $this->queueClient->send(Argument::type('array'))->shouldBeCalled()->willReturn($this->queueClient);

        $this->updateCampaign($campaignRef);
    }

    public function it_should_sync_to_repositories(Campaign $campaign)
    {
        $this->repository->putCampaign($campaign)->shouldBeCalled();
        $this->elasticRepository->putCampaign($campaign)->shouldBeCalled();

        $this->sync($campaign);
    }

    public function it_should_throw_exception_if_actor_present_on_start(Campaign $campaignRef)
    {
        $this->shouldThrow(CampaignException::class)->duringStart($campaignRef);
    }

    public function it_should_throw_exception_if_delivery_status_invalid_on_start(Campaign $campaignRef, Campaign $campaign, Response $response)
    {
        $this->setActor();
        $urn = 'urn:campaign:1234';
        $campaignRef->getUrn()->shouldBeCalled()->willReturn($urn);

        $this->repository->getCampaignByGuid(Argument::type('array'))->shouldBeCalled()->willReturn($response);
        $response->map(Argument::type('Callable'))->willReturn($response);
        $response->toArray()->shouldBeCalled()->willReturn([
            $campaign
        ]);

        $campaign->getDeliveryStatus()->shouldBeCalled()->willReturn(Campaign::STATUS_PENDING);
        $this->shouldThrow(CampaignException::class)->duringStart($campaignRef);
    }

    public function it_should_start_campaign(Campaign $campaignRef, Campaign $campaign, Response $response)
    {
        $this->setActor();
        $urn = 'urn:campaign:1234';
        $campaignRef->getUrn()->shouldBeCalled()->willReturn($urn);

        $this->repository->getCampaignByGuid(Argument::type('array'))->shouldBeCalled()->willReturn($response);
        $response->map(Argument::type('Callable'))->willReturn($response);
        $response->toArray()->shouldBeCalled()->willReturn([
            $campaign
        ]);

        $campaign->getDeliveryStatus()->shouldBeCalled()->willReturn(Campaign::STATUS_CREATED);
        $campaign->setReviewedTimestamp(Argument::approximate(time() * 1000))->shouldBeCalled();

        $this->repository->putCampaign($campaign)->shouldBeCalled();
        $this->elasticRepository->putCampaign($campaign)->shouldBeCalled();

        $this->start($campaignRef)->shouldReturn($campaign);
    }

    public function it_should_cancel_campaign(Campaign $campaignRef, Campaign $campaign, Response $response)
    {
        $urn = 'urn:campaign:1234';
        $campaignRef->getUrn()->shouldBeCalled()->willReturn($urn);

        $this->repository->getCampaignByGuid(Argument::type('array'))->shouldBeCalled()->willReturn($response);
        $response->map(Argument::type('Callable'))->willReturn($response);
        $response->toArray()->shouldBeCalled()->willReturn([
            $campaign
        ]);

        $this->user->get('guid')->shouldBeCalled()->willReturn('1234');
        $campaign->getOwnerGuid()->shouldBeCalled()->willReturn('1234');
        $this->user->isAdmin()->shouldBeCalled()->willReturn(false);

        $campaign->getDeliveryStatus()->shouldBeCalled()->willReturn(Campaign::STATUS_CREATED);

        $this->metrics->setCampaign($campaign)->shouldBeCalled()->willReturn($this->metrics);
        $this->metrics->getImpressionsMet()->shouldBeCalled()->willReturn(500);
        $campaign->setImpressions(500)->shouldBeCalled();
        $campaign->setRevokedTimestamp(Argument::approximate(time() * 1000))->shouldBeCalled();
        $this->paymentsDelegate->onStateChange($campaign)->shouldBeCalled();

        $this->repository->putCampaign($campaign)->shouldBeCalled();
        $this->elasticRepository->putCampaign($campaign)->shouldBeCalled();

        $this->cancelCampaign($campaignRef)->shouldReturn($campaign);
    }

    public function it_should_reject_campaign(Campaign $campaignRef, Campaign $campaign, Response $response)
    {
        $urn = 'urn:campaign:1234';
        $campaignRef->getUrn()->shouldBeCalled()->willReturn($urn);

        $this->repository->getCampaignByGuid(Argument::type('array'))->shouldBeCalled()->willReturn($response);
        $response->map(Argument::type('Callable'))->willReturn($response);
        $response->toArray()->shouldBeCalled()->willReturn([
            $campaign
        ]);

        $this->user->isAdmin()->shouldBeCalled()->willReturn(true);

        $campaign->getDeliveryStatus()->shouldBeCalled()->willReturn(Campaign::STATUS_CREATED);

        $campaign->setRejectedTimestamp(Argument::approximate(time() * 1000))->shouldBeCalled();
        $this->paymentsDelegate->onStateChange($campaign)->shouldBeCalled();

        $this->repository->putCampaign($campaign)->shouldBeCalled();
        $this->elasticRepository->putCampaign($campaign)->shouldBeCalled();

        $this->rejectCampaign($campaignRef)->shouldReturn($campaign);
    }

    public function it_should_complete_campaign(Campaign $campaignRef, Campaign $campaign, Response $response)
    {
        $this->setActor(); // No User Involved

        $urn = 'urn:campaign:1234';
        $campaignRef->getUrn()->shouldBeCalled()->willReturn($urn);

        $this->repository->getCampaignByGuid(Argument::type('array'))->shouldBeCalled()->willReturn($response);
        $response->map(Argument::type('Callable'))->willReturn($response);
        $response->toArray()->shouldBeCalled()->willReturn([
            $campaign
        ]);

        $campaign->getDeliveryStatus()->shouldBeCalled()->willReturn(Campaign::STATUS_APPROVED);

        $campaign->setCompletedTimestamp(Argument::approximate(time() * 1000))->shouldBeCalled();
        $this->paymentsDelegate->onStateChange($campaign)->shouldBeCalled();

        $this->repository->putCampaign($campaign)->shouldBeCalled();
        $this->elasticRepository->putCampaign($campaign)->shouldBeCalled();

        $this->completeCampaign($campaignRef)->shouldReturn($campaign);
    }

    public function it_should_perform_actions_on_payment_success(Payment $payment, Campaign $campaign, Response $response)
    {
        $payment->getCampaignGuid()->shouldBeCalled()->willReturn(1234);

        $this->repository->getCampaignByGuid(Argument::type('array'))->shouldBeCalled()->willReturn($response);
        $response->map(Argument::type('Callable'))->willReturn($response);
        $response->toArray()->shouldBeCalled()->willReturn([
            $campaign
        ]);

        $this->paymentsDelegate->onConfirm($campaign, $payment)->shouldBeCalled()->willReturn($campaign);

        $this->repository->putCampaign($campaign)->shouldBeCalled();
        $this->elasticRepository->putCampaign($campaign)->shouldBeCalled();

        $this->queueClient->setQueue('BoostCampaignDispatcher')->shouldBeCalled()->willReturn($this->queueClient);
        $this->queueClient->send(Argument::type('array'))->shouldBeCalled();

        $this->onPaymentSuccess($payment);
    }
}
