<?php


namespace Minds\Core\Email;

use Minds\Core\Di\Di;
use Minds\Core\Email\EmailSubscription;
use Minds\Core\Entities;
use Minds\Entities\User;
use Minds\Core\Email\Repository;
use Minds\Core\Email\CampaignLogs\Manager as CampaignLogsManager;
use Minds\Core\Email\CampaignLogs\CampaignLog;
use Minds\Common\Repository\Response;

class Manager
{
    /** @var Repository */
    protected $repository;

    /** @var CampaignLogsManager */
    protected $campaignLogsManager;


    public function __construct(Repository $repository = null, CampaignLogsManager $campaignLogsManager = null)
    {
        $this->repository = $repository ?: Di::_()->get('Email\Repository');
        $this->campaignLogsManager = $campaignLogsManager ?? Di::_()->get('Email\CampaignLogs\Manager');
    }

    public function getSubscribers($options = [])
    {
        $options = array_merge([
            'campaign' => null,
            'topic' => null,
            'value' => false,
            'limit' => 2000,
            'offset' => ''
        ], $options);

        $result = $this->repository->getList($options);

        if (!$result || count($result['data']) === 0) {
            return [];
        }

        /*$guids = [];
        foreach($result['data'] as $subscription) {
            $guids[] = $subscription->getUserGuid();
        }*/
        $guids = array_map(function ($item) {
            return $item->getUserGuid();
        }, $result['data']);

        return [
            'users' => Entities::get(['guids' => $guids]),
            'token' => $result['token']
        ];
    }

    public function isSubscribed(EmailSubscription $subscription)
    {
        $result = $this->repository->getList([
            'user_guid' => $subscription->getUserGuid(),
            'campaign' => $subscription->getCampaign(),
            'topic' => $subscription->getTopic(),
            'value' => $subscription->getValue(),
        ]);

        return count($result['data']) > 0 && $result['data'][0]->getValue() !== '0' && $result['data'][0]->getValue() !== '';
    }

    /**
     * Unsubscribe from emails
     * @param User $user
     * @param array $campaigns
     * @param array $topics
     * @return bool
     */
    public function unsubscribe($user, $campaigns = [], $topics = [])
    {
        if (!$campaigns) {
            $campaigns = [ 'when', 'with', 'global' ];
        }

        if (!$topics) {
            $topics = [
                'unread_notifications',
                'wire_received',
                'boost_completed',
                'top_posts',
                'channel_improvement_tips',
                'posts_missed_since_login',
                'new_channels',
                'minds_news',
                'minds_tips',
                'exclusive_promotions',
            ];
        }

        //We can skip the read here
        if (count($campaigns) == 1 && count($topics) >= 1) {
            $subscriptions = [];
            foreach ($topics as $topic) {
                $subscriptions[] = (new EmailSubscription)
                    ->setUserGuid($user->guid)
                    ->setCampaign($campaigns[0])
                    ->setTopic($topic);
            }
        } else {
            $subscriptions = $this->repository->getList([
                'campaigns' => $campaigns,
                'topics' => $topics,
                'user_guid' => $user->guid,
            ]);
        }

        foreach ($subscriptions as $subscription) {
            $this->repository->delete($subscription);
        }

        return true;
    }

    /**
     * Saves a log when we send a user a campaign email
     * Used to select subsequent mailings and send different emails
     * @param CampaignLog $campaignLog the receiver, time and campaign class name
     * @return boolean the add result
     */
    public function saveCampaignLog(CampaignLog $campaignLog)
    {
        $this->campaignLogsManager->add($campaignLog);
    }

    public function getCampaignLogs(User $receiver): Response
    {
        $options = [
            'receiver_guid' => $receiver->getGuid()
        ];
        return $this->campaignLogsManager->getList($options);
    }
}
