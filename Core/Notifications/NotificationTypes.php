<?php
namespace Minds\Core\Notifications;

class NotificationTypes
{
    /**
     * *******************************************
     * Votes
     */

    /** @var string */
    const TYPE_VOTE_UP = 'vote_up';

    /** @var string */
    const TYPE_VOTE_DOWN = 'vote_down';

    /** @var string[] */
    const GROUPING_VOTES = [ self::TYPE_VOTE_UP, self::TYPE_VOTE_DOWN ];

    /** @var string */
    const GROUPING_TYPE_VOTES = 'votes';

    /**
     * *******************************************
     * Tags/mentions
     */

    /** @var string */
    const TYPE_TAG = 'tag';

    /** @var string[] */
    const GROUPING_TAGS = [ self::TYPE_TAG ];

    /** @var string */
    const GROUPING_TYPE_TAGS = 'tags';

    /**
     * *******************************************
     * Subscriptions/Referrals
     */

    /** @var string */
    const TYPE_SUBSCRIBE = 'subscribe';

    /** @var string */
    const TYPE_REFERRAL_PING = 'referral_ping';

    /** @var string */
    const TYPE_REFERRAL_PENDING = 'referral_pending';

    /** @var string */
    const TYPE_REFERRAL_COMPLETE = 'referral_complete';

    /** @var string[] */
    const GROUPING_SUBSCRIPTIONS = [
        self::TYPE_SUBSCRIBE,
        self::TYPE_REFERRAL_PING,
        self::TYPE_REFERRAL_PENDING,
        self::TYPE_REFERRAL_COMPLETE,
    ];

    /** @var string */
    const GROUPING_TYPE_SUBSCRIPTIONS = 'subscriptions';

    /**
     * *******************************************
     * Comments
     */

    /** @var string */
    const TYPE_COMMENT = 'comment';

    /** @var string[] */
    const GROUPING_COMMENTS = [ self::TYPE_COMMENT ];

    /** @var string */
    const GROUPING_TYPE_COMMENTS = 'comments';

    /**
     * *******************************************
     * Remind
     */

    /** @var string */
    const TYPE_REMIND = 'remind';

    /** @var string */
    const TYPE_QUOTE = 'quote';

    /** @var string[] */
    const GROUPING_REMINDS = [ self::TYPE_REMIND, self::TYPE_QUOTE ];

    /** @var string */
    const GROUPING_TYPE_REMINDS = 'reminds';

    /**
     * *******************************************
     * Boosts
     */

    /** @var string */
    const TYPE_BOOST_COMPLETED = 'boost_completed';

    /** @var string */
    const TYPE_BOOST_REJECTED = 'boost_rejected';

    /** @var string */
    const TYPE_BOOST_PEER_REQUEST = 'boost_peer_request';

    /** @var string */
    const TYPE_BOOST_PEER_ACCEPTED = 'boost_peer_accepted';

    /** @var string */
    const TYPE_BOOST_PEER_REJECTED = 'boost_peer_rejected';

    /** @var string[] */
    const GROUPING_BOOSTS = [
        self::TYPE_BOOST_COMPLETED,
        self::TYPE_BOOST_REJECTED,
        self::TYPE_BOOST_PEER_REQUEST,
        self::TYPE_BOOST_PEER_ACCEPTED,
        self::TYPE_BOOST_PEER_REJECTED,
    ];

    /** @var string */
    const GROUPING_TYPE_BOOSTS = 'boosts';

    /**
     * *******************************************
     * Tokens
     */

    /** @var string */
    const TYPE_TOKEN_REWARDS_SUMMARY = 'token_rewards_summary';

    /** @var string */
    const TYPE_TOKEN_WITHDRAW_ACCEPTED = 'token_withdraw_accepted';

    /** @var string */
    const TYPE_TOKEN_WITHDRAW_REJECTED = 'token_withdraw_rejected';

    /** @var string[] */
    const GROUPING_TOKENS = [
        self::TYPE_TOKEN_REWARDS_SUMMARY,
        self::TYPE_TOKEN_WITHDRAW_ACCEPTED,
        self::TYPE_TOKEN_WITHDRAW_REJECTED,
    ];

    /** @var string */
    const GROUPING_TYPE_TOKENS = 'tokens';

    /**
     * *******************************************
     * Chat
     */

    /** @var string */
    const TYPE_CHAT_INVITE = 'chat_invite';

    /** @var string[] */
    const GROUPING_CHATS = [ self::TYPE_CHAT_INVITE];

    /** @var string */
    const GROUPING_TYPE_CHATS = 'chats';

    /**
     * *******************************************
     * Groups
     */

    /** @var string */
    const TYPE_GROUP_INVITE = 'group_invite';

    /** @var string */
    const TYPE_GROUP_QUEUE_ADD = 'group_queue_add';

    /** @var string */
    const TYPE_GROUP_QUEUE_APPROVE = 'group_queue_approve';

    /** @var string */
    const TYPE_GROUP_QUEUE_REJECT = 'group_queue_reject';

    /** @var string[] */
    const GROUPING_GROUPS = [
        self::TYPE_GROUP_INVITE,
        self::TYPE_GROUP_QUEUE_ADD,self::TYPE_GROUP_QUEUE_APPROVE,self::TYPE_GROUP_QUEUE_REJECT, ];

    /** @var string */
    const GROUPING_TYPE_GROUPS = 'groups';

    /**
     * *******************************************
     * Reports
     */

    /** @var string */
    const TYPE_REPORT_ACTIONED = 'report_actioned';

    /** @var string[] */
    const GROUPING_REPORTS = [ self::TYPE_REPORT_ACTIONED];

    /** @var string */
    const GROUPING_TYPE_REPORTS = 'reports';


    /**
     * *******************************************
     * Top Posts
     */

    /** @var string */
    const TYPE_TOP_POSTS = 'top_posts';

    /** @var string */
    const GROUPING_TOP_POSTS = [ self::TYPE_TOP_POSTS ];

    /** @var string */
    const GROUPING_TYPE_TOP_POSTS = 'top_posts';


    /**
     * *******************************************
     * Community Updates
     */

    /** @var string */
    const TYPE_COMMUNITY_UPDATES = 'community_updates';

    /** @var string */
    const GROUPING_COMMUNITY_UPDATES = [ self::TYPE_COMMUNITY_UPDATES ];

    /** @var string */
    const GROUPING_TYPE_COMMUNITY_UPDATES = 'community_updates';


    /**
     * *******************************************
     * Wires
     */

    // For Plus & Pro
    /** @var string */
    const TYPE_WIRE_PAYOUT = 'wire_payout';

    // For p2p tips
    /** @var string */
    const TYPE_WIRE_RECEIVED = 'wire_received';

    /** @var string[] */
    const GROUPING_WIRES = [ self::TYPE_WIRE_PAYOUT, self::TYPE_WIRE_RECEIVED ];

    /** @var string */
    const GROUPING_TYPE_WIRES = 'wires';

    /**
     * *******************************************
     * *******************************************
     * All notifications
     * *******************************************
     * *******************************************
     *
     */

    /** @var string[] */
    const TYPES = [
        self::TYPE_VOTE_UP,
        self::TYPE_VOTE_DOWN,
        //
        self::TYPE_TAG,
        //
        self::TYPE_SUBSCRIBE,
        //
        self::TYPE_REFERRAL_PING,
        self::TYPE_REFERRAL_PENDING,
        self::TYPE_REFERRAL_COMPLETE,
        //
        self::TYPE_COMMENT,
        //
        self::TYPE_REMIND,
        self::TYPE_QUOTE,
        //
        self::TYPE_BOOST_COMPLETED,
        self::TYPE_BOOST_REJECTED,
        self::TYPE_BOOST_PEER_REQUEST,
        self::TYPE_BOOST_PEER_ACCEPTED,
        self::TYPE_BOOST_PEER_REJECTED,
        //
        self::TYPE_TOKEN_REWARDS_SUMMARY,
        self::TYPE_TOKEN_WITHDRAW_ACCEPTED,
        self::TYPE_TOKEN_WITHDRAW_REJECTED,
        //
        self::TYPE_CHAT_INVITE,
        //
        self::TYPE_GROUP_INVITE,
        self::TYPE_GROUP_QUEUE_ADD,
        self::TYPE_GROUP_QUEUE_APPROVE,
        self::TYPE_GROUP_QUEUE_REJECT,
        //
        self::TYPE_WIRE_PAYOUT,
        self::TYPE_WIRE_RECEIVED,
        //
        self::TYPE_REPORT_ACTIONED,
    ];

    /** @var array */
    const TYPES_GROUPINGS = [
        self::GROUPING_TYPE_VOTES => self::GROUPING_VOTES,
        self::GROUPING_TYPE_TAGS => self::GROUPING_TAGS,
        self::GROUPING_TYPE_SUBSCRIPTIONS => self::GROUPING_SUBSCRIPTIONS,
        self::GROUPING_TYPE_COMMENTS => self::GROUPING_COMMENTS,
        self::GROUPING_TYPE_REMINDS => self::GROUPING_REMINDS,
        self::GROUPING_TYPE_BOOSTS => self::GROUPING_BOOSTS,
        self::GROUPING_TYPE_TOKENS => self::GROUPING_TOKENS,
        self::GROUPING_TYPE_CHATS => self::GROUPING_CHATS,
        self::GROUPING_TYPE_GROUPS => self::GROUPING_GROUPS,
        self::GROUPING_TYPE_WIRES => self::GROUPING_WIRES,
        self::GROUPING_TYPE_REPORTS => self::GROUPING_REPORTS,
        self::GROUPING_TYPE_TOP_POSTS => self::GROUPING_TOP_POSTS,
        self::GROUPING_TYPE_COMMUNITY_UPDATES => self::GROUPING_COMMUNITY_UPDATES,
    ];
}
