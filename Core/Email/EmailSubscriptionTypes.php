<?php
namespace Minds\Core\Email;

class EmailSubscriptionTypes
{
    const TYPES_GROUPINGS = [
        'when' => [
            'boost_completed',
            'unread_notifications',
            'wire_received'
        ],
        'with' => [
            'channel_improvement_tips',
            'new_channels',
            'posts_missed_since_login',
            // 'top_posts'
        ],
        'global' => [
            'exclusive_promotions',
            'minds_news',
            'minds_tips',
        ]
    ];
}
