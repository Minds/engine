<?php


namespace Minds\Core\Email;


use Minds\Core\Di\Di;
use Minds\Core\Entities;

class Manager
{
    /** @var Repository */
    protected $repository;

    public function __construct($repository = null)
    {
        $this->repository = $repository ?: Di::_()->get('Email\Repository');
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

        // @FIXME: Due to a quirk in PHP < 7.2.0, count($array === 0) always returns true.
        // @FIXME: This blows up, as it should, in PHP >= 7.2.0.
        // @FIXME: Since this code was -always- returning true, it needs to be fixed.
        // Orig: if (!$result || count($result['data'] === 0) {
        if (!$result || true /** @FIXME!~! */) {
            return [];
        }

        // @FIXME: Due to the function always exiting before this point, no one caught
        // @FIXME: that this array_map blows up trying to call a method on a string.
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

}
