<?php
namespace Minds\Core\Notifications;

class NotificationTypes
{
    /**
     * Votes
     */

    /** @var string */
    const TYPE_VOTE_UP = 'vote_up';

    /** @var string */
    const TYPE_VOTE_DOWN = 'vote_down';

    /** @var string[] */
    const GROUP_VOTES = [ self::TYPE_VOTE_UP, self::TYPE_VOTE_DOWN ];

    /** @var string */
    const GROUP_TYPE_VOTES = 'votes';

    /**
     * Tags/mentions
     */

    /** @var string */
    const TYPE_TAG = 'tag';

    /** @var string[] */
    const GROUP_TAGS = [ self::TYPE_TAG ];

    /** @var string */
    const GROUP_TYPE_TAGS = 'tags';

    /**
     * Subscriptions
     */

    /** @var string */
    const TYPE_SUBSCRIBE = 'subscribe';

    /**
     * Referrals
     */

    /** @var string */
    const TYPE_REFERRAL_PING = 'referral_ping';

    /** @var string */
    const TYPE_REFERRAL_PENDING = 'referral_pending';

    /** @var string */
    const TYPE_REFERRAL_COMPLETE = 'referral_complete';

    /** @var string[] */
    const GROUP_SUBSCRIPTIONS = [
        self::TYPE_SUBSCRIBE,
        self::TYPE_REFERRAL_PING,
        self::TYPE_REFERRAL_PENDING,
        self::TYPE_REFERRAL_COMPLETE,
    ];

    /** @var string */
    const GROUP_TYPE_SUBSCRIPTIONS = 'subscriptions';

    /**
     * Comments
     */

    /** @var string */
    const TYPE_COMMENT = 'comment';

    /** @var string[] */
    const GROUP_COMMENTS = [ self::TYPE_COMMENT ];

    /** @var string */
    const GROUP_TYPE_COMMENTS = 'comments';

    /**
     * Remind
     */

    /** @var string */
    const TYPE_REMIND = 'remind';

    /** @var string */
    const TYPE_QUOTE = 'quote';

    /** @var string[] */
    const GROUP_REMINDS = [ self::TYPE_REMIND, self::TYPE_QUOTE ];

    /** @var string */
    const GROUP_TYPE_REMINDS = 'reminds';

    /**
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
    const GROUP_BOOSTS = [
        self::TYPE_BOOST_COMPLETED,
        self::TYPE_BOOST_REJECTED,
        self::TYPE_BOOST_PEER_REQUEST,
        self::TYPE_BOOST_PEER_ACCEPTED,
        self::TYPE_BOOST_PEER_REJECTED,
    ];

    /** @var string */
    const GROUP_TYPE_BOOSTS = 'boosts';

    /**
     * Tokens
     */

    /** @var string */
    const TYPE_TOKEN_REWARDS_SUMMARY = 'token_rewards_summary';

    /** @var string */
    const TYPE_TOKEN_WITHDRAW_REQUEST = 'token_withdraw_request';

    /** @var string */
    const TYPE_TOKEN_WITHDRAW_ACCEPTED = 'token_withdraw_accepted';

    /** @var string */
    const TYPE_TOKEN_WITHDRAW_REJECTED = 'token_withdraw_rejected';

    /** @var string[] */
    const GROUP_TOKENS = [
        self::TYPE_TOKEN_REWARDS_SUMMARY,
        self::TYPE_TOKEN_WITHDRAW_REQUEST,
        self::TYPE_TOKEN_WITHDRAW_ACCEPTED,
        self::TYPE_TOKEN_WITHDRAW_REJECTED,
    ];

    /** @var string */
    const GROUP_TYPE_TOKENS = 'tokens';

    /**
     * All notifications
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
        self::TYPE_TOKEN_WITHDRAW_REQUEST,
        self::TYPE_TOKEN_WITHDRAW_ACCEPTED,
        self::TYPE_TOKEN_WITHDRAW_REJECTED,
    ];

    /** @var array */
    const TYPES_GROUPS = [
        self::GROUP_TYPE_VOTES => self::GROUP_VOTES,
        self::GROUP_TYPE_TAGS => self::GROUP_TAGS,
        self::GROUP_TYPE_SUBSCRIPTIONS => self::GROUP_SUBSCRIPTIONS,
        self::GROUP_TYPE_COMMENTS => self::GROUP_COMMENTS,
        self::GROUP_TYPE_REMINDS => self::GROUP_REMINDS,
        self::GROUP_TYPE_BOOSTS => self::GROUP_BOOSTS,
        self::GROUP_TYPE_TOKENS => self::GROUP_TOKENS,
    ];
}
