<?php
namespace Minds\Core\Email\Mautic\MarketingAttributes;

use Minds\Core\Di\Di;
use Minds\Core\Email\Repository as EmailRepository;

/**
 * This is a helper class to collect and return the email settings
 * that is overdue a refactor
 */
class EmailPreferenceLists
{
    public function __construct(private ?EmailRepository $emailRepository = null)
    {
        $this->emailRepository ??= Di::_()->get('Email\Repository');
    }

    /**
     * @param string $userGuid
     * @return array
     */
    public function getList(string $userGuid): array
    {
        $campaigns = [ 'when', 'with', 'global' ];

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

        /** @var Core\Email\Repository $rpository */
        $result = $this->emailRepository->getList([
            'campaigns' => $campaigns,
            'topics' => $topics,
            'user_guid' => $userGuid,
        ]);

        return $result['data'];
    }
}

