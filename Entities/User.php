<?php

namespace Minds\Entities;

use Minds\Common\ChannelMode;
use Minds\Core;
use Minds\Core\Di\Di;
use Minds\Entities\Enums\FederatedEntitySourcesEnum;
use Minds\Core\Monetization\Demonetization\Strategies\Interfaces\DemonetizableEntityInterface;
use Minds\Core\Supermind\Settings\Models\Settings;
use Minds\Core\Subscriptions;
use Minds\Helpers;
use Minds\Helpers\StringLengthValidators\BriefDescriptionLengthValidator;

/**
 * User Entity.
 *
 * @todo Do not inherit from ElggUser
 * @property int $boost_rating
 * @property int $mature
 * @property int $mature_content
 * @property int $mature_lock
 * @property int $is_mature
 * @property int $spam
 * @property int $deleted
 * @property array $social_profiles
 * @property string $ban_monetization
 * @property array $tags
 * @property int $onboarding_shown
 * @property string $onboarding_interest
 * @property User $last_avatar_upload
 * @property int $toaster_notifications
 * @property int $onchain_booster
 * @property int $theme
 * @property int $canary
 * @property int $opted_in_hashtags
 * @property int $last_accepted_tos
 * @property array $supermind_settings
 * @property int $creator_frequency
 * @property string $phone_number
 * @property string $phone_number_hash
 * @property array $wire_rewards
 * @property int $icontime
 * @property array $pinned_posts
 * @property array $feature_flags
 * @property array $programs
 * @property string $eth_wallet
 * @property int $eth_incentive
 * @property array $categories
 * @property int $plus_expires
 * @property string $plus_method;
 * @property int $pro_expires
 * @property string $pro_method;
 * @property int $disabled_boost
 * @property int $founder
 * @property array $merchant
 * @property array $monetization_settings
 * @property array $group_membership
 * @property int $boost_autorotate
 * @property string $fb
 * @property int $verified
 * @property int $mode
 * @property string $btc_address
 * @property bool $initial_onboarding_completed
 * @property string $email_confirmation_token
 * @property int $email_confirmed_at
 * @property int $allow_unsubscribed_contact
 * @property bool $hide_share_buttons
 * @property array $dismissed_widgets
 * @property int $partner_rpm
 * @property int $liquidity_spot_opt_out
 * @property string $public_dob
 * @property string $dob
 * @property string $surge_token
 * @property int $disable_autoplay_videos
 * @property string $twofactor
 * @property string $briefdescription
 * @property string $source
 * @property string $canonical_url
 * @property int $opt_out_analytics;
 * @property int $bot
 */
class User extends \ElggUser implements DemonetizableEntityInterface, FederatedEntityInterface
{
    public $fullExport = true;
    public $exportCounts = false;

    public const PLUS_PRO_VALID_METHODS = [
        'tokens',
        'usd',
        'iap_google',
        'iap_apple',
    ];

    protected function initializeAttributes()
    {
        $this->attributes['boost_rating'] = 1;
        $this->attributes['mature'] = 0;
        $this->attributes['mature_content'] = 0;
        $this->attributes['spam'] = 0;
        $this->attributes['deleted'] = 0;
        $this->attributes['social_profiles'] = [];
        $this->attributes['ban_monetization'] = 'no';
        $this->attributes['programs'] = [];
        $this->attributes['monetization_settings'] = [];
        $this->attributes['group_membership'] = [];
        $this->attributes['tags'] = [];
        $this->attributes['partner_rpm'] = 0;
        $this->attributes['plus'] = 0; //TODO: REMOVE
        $this->attributes['plus_expires'] = 0;
        $this->attributes['plus_method'] = 'tokens';
        $this->attributes['pro_expires'] = 0;
        $this->attributes['pro_method'] = 'tokens';
        $this->attributes['verified'] = 0;
        $this->attributes['founder'] = 0;
        $this->attributes['disabled_boost'] = 0;
        $this->attributes['boost_autorotate'] = 1;
        $this->attributes['categories'] = [];
        $this->attributes['wire_rewards'] = '';
        $this->attributes['pinned_posts'] = [];
        $this->attributes['eth_wallet'] = '';
        $this->attributes['eth_incentive'] = '';
        $this->attributes['btc_address'] = '';
        $this->attributes['phone_number'] = null;
        $this->attributes['phone_number_hash'] = null;
        $this->attributes['icontime'] = 0;
        $this->attributes['briefdescription'] = '';
        $this->attributes['rating'] = 1;
        $this->attributes['is_mature'] = 0;
        $this->attributes['mature_lock'] = 0;
        $this->attributes['opted_in_hashtags'] = 0;
        $this->attributes['last_accepted_tos'] = Core\Config::_()->get('last_tos_update');
        $this->attributes['onboarding_shown'] = 0;
        $this->attributes['onboarding_interest'] = '';
        $this->attributes['initial_onboarding_completed'] = 0;
        $this->attributes['creator_frequency'] = null;
        $this->attributes['last_avatar_upload'] = 0;
        $this->attributes['canary'] = 0;
        $this->attributes['onchain_booster'] = null;
        $this->attributes['toaster_notifications'] = 1;
        $this->attributes['mode'] = ChannelMode::OPEN;
        $this->attributes['email_confirmation_token'] = null;
        $this->attributes['email_confirmed_at'] = null;
        $this->attributes['surge_token'] = '';
        $this->attributes['hide_share_buttons'] = 0;
        $this->attributes['allow_unsubscribed_contact'] = 0;
        $this->attributes['kite_ref_ts'] = 0;
        $this->attributes['kite_state'] = 'unknown';
        $this->attributes['disable_autoplay_videos'] = 0;
        $this->attributes['dob'] = 0;
        $this->attributes['yt_channels'] = [];
        $this->attributes['public_dob'] = 0;
        $this->attributes['dismissed_widgets'] = [];
        $this->attributes['liquidity_spot_opt_out'] = 0;
        $this->attributes['supermind_settings'] = [];
        $this->attributes['language'] = 'en';
        $this->attributes['source'] = FederatedEntitySourcesEnum::LOCAL->value;
        $this->attributes['canonical_url'] = null;
        $this->attributes['opt_out_analtytics'] = 0;
        $this->attributes['bot'] = 0;

        parent::initializeAttributes();
    }

    /**
     * Returns the username
     * @return string
     */
    public function getUsername(): string
    {
        return $this->username ?: '';
    }

    /**
     * Returns the display name
     * @return string
     */
    public function getName(): string
    {
        return $this->name ?: '';
    }

    /**
     * Set the name
     * @param string $name
     * @return self
     */
    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Set the briefdescription
     * @param string $briefdescription
     * @return self
     */
    public function setBriefDescription(string $briefdescription): self
    {
        $this->briefdescription = $briefdescription;
        return $this;
    }

    /**
     * Gets `tags`.
     *
     * @return mixed
     */
    public function getHashtags()
    {
        if (is_string($this->tags)) {
            return json_decode($this->tags);
        }

        return $this->tags ?: [];
    }

    /**
     * Sets all `tags` - to set an individual tag
     * use addHashtag or removeHashtag.
     *
     * @return array
     */
    public function setHashtags(array $tags)
    {
        $this->tags = $tags;

        return $this;
    }

    /**
     * Adds a hashtag to the tags array.
     * @param string $hashtag - string of the hashtag e.g. #OpenSource.
     * @return User allows chaining.
     */
    public function addHashtag(string $hashtag): User
    {
        $this->setHashtags(
            array_merge($this->getHashtags(), [$hashtag])
        );
        return $this;
    }

    /**
     * Removes a hashtag to the tags array by string content.
     * @param string $hashtag - string of the hashtag e.g. #OpenSource.f
     * @return User allows chaining.
     */
    public function removeHashtag($hashtag): User
    {
        $this->setHashtags(
            array_values(
                array_diff($this->getHashtags(), [$hashtag])
            )
        );
        return $this;
    }

    /**
     * Gets `onboarding_shown`.
     *
     * @return bool
     */
    public function wasOnboardingShown()
    {
        return (bool) $this->onboarding_shown;
    }

    /**
     * Sets `onboarding_shown`.
     *
     * @return $this
     */
    public function setOnboardingShown($onboardingShown)
    {
        $this->onboarding_shown = $onboardingShown ? 1 : 0;

        return $this;
    }

    /**
     * Sets the interest that a user signalled that they are
     * interested in during onboarding.
     * @return self
     */
    public function setOnboardingInterest(string $onboardingInterest): self
    {
        $this->onboarding_interest = $onboardingInterest;
        return $this;
    }

    /**
     * Gets the interest that a user signalled that they are
     * interested in during onboarding.
     * @return string a users onboarding interest.
     */
    public function getOnboardingInterest(): string
    {
        return (bool) $this->onboarding_shown;
    }

    /**
     * Sets `initial_onboarding_completed`.
     * @param int $ts - timestamp in seconds
     * @return $this
     */
    public function setInitialOnboardingCompleted(int $ts): self
    {
        $this->initial_onboarding_completed = $ts;
        return $this;
    }

    /**
     * Return the time initial onboarding was completed
     * @return int
     */
    public function getInitialOnboardingCompleted(): int
    {
        return $this->initial_onboarding_completed;
    }

    /**
     * @return int
     */
    public function getLastAvatarUpload()
    {
        return $this->last_avatar_upload;
    }

    /**
     * @param int $lastAvatarUpload
     *
     * @return $this
     */
    public function setLastAvatarUpload($lastAvatarUpload)
    {
        $this->last_avatar_upload = $lastAvatarUpload;

        return $this;
    }

    /**
     * Gets `creator_frequency`.
     *
     * @return bool
     */
    public function getCreatorFrequency()
    {
        return $this->creator_frequency;
    }

    /**
     * Sets `creator_frequency`.
     *
     * @param $creatorFrequency
     *
     * @return $this
     */
    public function setCreatorFrequency($creatorFrequency)
    {
        $this->creator_frequency = $creatorFrequency;

        return $this;
    }

    /**
     * Sets the `boost rating` flag.
     *
     * @param int $value
     *
     * @return $this
     */
    public function setBoostRating($value)
    {
        $this->boost_rating = $value;

        return $this;
    }

    /**
     * Gets the `boost rating` flag.
     *
     * @return int
     */
    public function getBoostRating()
    {
        return $this->boost_rating;
    }

    /**
     * Gets the `mature` flag.
     *
     * @return bool|int
     */
    public function getViewMature()
    {
        return $this->attributes['mature'];
    }

    /**
     * Sets the `mature` flag.
     *
     * @param bool|int $value
     *
     * @return $this
     */
    public function setViewMature($value)
    {
        $this->mature = $value ? 1 : 0;

        return $this;
    }

    /**
     * Sets the `mature_content` flag.
     *
     * @param bool|int $value
     *
     * @return $this
     */
    public function setMatureContent($value)
    {
        $this->mature_content = $value ? 1 : 0;

        return $this;
    }

    /**
     * Gets the `mature_content` flag.
     *
     * @return bool|int
     */
    public function getMatureContent()
    {
        return $this->mature_content;
    }

    /**
     * Sets the `spam` flag.
     *
     * @param bool|int $value
     *
     * @return $this
     */
    public function setSpam($value)
    {
        $this->spam = $value ? 1 : 0;

        return $this;
    }

    /**
     * Gets the `spam` flag.
     *
     * @return bool|int
     */
    public function getSpam()
    {
        if (is_string($this->spam)) {
            return json_decode($this->spam);
        }

        return $this->spam;
    }

    /**
     * Sets the `deleted` flag.
     *
     * @param bool|int $value
     *
     * @return $this
     */
    public function setDeleted($value)
    {
        $this->deleted = $value ? 1 : 0;

        return $this;
    }

    /**
     * Gets the `deleted` flag.
     *
     * @return bool|int
     */
    public function getDeleted()
    {
        if (is_string($this->deleted)) {
            return json_decode($this->deleted);
        }

        return $this->deleted;
    }

    /**
     * Sets the `language` flag.
     *
     * @param string $value
     *
     * @return $this
     */
    public function setLanguage($value)
    {
        $this->language = $value;

        return $this;
    }

    /**
     * Gets the `language` flag.
     *
     * @return string
     */
    public function getLanguage()
    {
        return $this->language ?? "en";
    }

    /**
     * Sets and encrypts a users email address.
     *
     * @param string $email
     *
     * @return $this
     */
    public function setEmail($email)
    {
        global $CONFIG; //@todo use object config instead
        if (base64_decode($email, true)) {
            return $this;
        }
        $this->email = $email;

        return $this;
    }

    /**
     * Returns and decrypts an email address.
     *
     * @return string
     */
    public function getEmail()
    {
        global $CONFIG; //@todo use object config instead
        if ($this->email && !base64_decode($this->email, true)) {
            return $this->email;
        }

        return Helpers\OpenSSL::decrypt(base64_decode($this->email, true), file_get_contents($CONFIG->encryptionKeys['email']['private']));
    }

    /**
     * Sets and encrypts a users phone number.
     *
     * @param string $phone
     *
     * @return $this
     */
    public function setPhoneNumber($phone)
    {
        global $CONFIG; //@todo use object config instead
        $this->phone_number = base64_encode(Helpers\OpenSSL::encrypt($phone, file_get_contents($CONFIG->encryptionKeys['phone-number']['public'])));

        return $this;
    }

    /**
     * Returns and decrypts an phone number.
     *
     * @return $this
     */
    public function getPhoneNumber()
    {
        global $CONFIG; //@todo use object config instead
        if ($this->phone_number && !base64_decode($this->phone_number, true)) {
            return $this->phone_number;
        }

        return Helpers\OpenSSL::decrypt(base64_decode($this->phone_number, true), file_get_contents($CONFIG->encryptionKeys['phone-number']['private']));
    }

    public function setPhoneNumberHash($hash)
    {
        $this->phone_number_hash = $hash;

        return $this;
    }

    public function getPhoneNumberHash()
    {
        return $this->phone_number_hash;
    }

    /**
     * Sets (overrides) social profiles information.
     *
     * @return $this
     */
    public function setSocialProfiles(array $social_profiles)
    {
        $this->social_profiles = $social_profiles;

        return $this;
    }

    /**
     * Sets (or clears) a single social profile.
     *
     * @return $this
     */
    public function setSocialProfile($key, $value = null)
    {
        if ($value === null || $value === '') {
            if (isset($this->social_profiles[$key])) {
                unset($this->social_profiles[$key]);
            }
        } else {
            $this->social_profiles[$key] = $value;
        }

        return $this;
    }

    /**
     * Returns all set social profiles.
     *
     * @return array
     */
    public function getSocialProfiles()
    {
        return $this->social_profiles ?: [];
    }

    /**
     * Sets (overrides) wire rewards information.
     *
     * @return $this
     */
    public function setWireRewards(array $wire_rewards)
    {
        $this->wire_rewards = $wire_rewards ?: '';

        return $this;
    }

    /**
     * Returns all set wire rewards.
     *
     * @return array
     */
    public function getWireRewards()
    {
        return $this->wire_rewards ?: '';
    }

    /**
     * @param string $guid
     */
    public function addPinned($guid)
    {
        $pinned = $this->getPinnedPosts();

        if (!$pinned) {
            $pinned = [];
        }

        if (array_search($guid, $pinned, true) === false) {
            $pinned[] = (string) $guid;
        }

        $this->setPinnedPosts($pinned);
    }

    /**
     * @param string $guid
     *
     * @return bool
     */
    public function removePinned($guid)
    {
        $pinned = $this->getPinnedPosts();
        if ($pinned && count($pinned) > 0) {
            $index = array_search((string) $guid, $pinned, true);
            if (is_numeric($index)) {
                array_splice($pinned, $index, 1);
                $this->pinned_posts = $pinned;
            }
        }

        return false;
    }

    /**
     * Sets the channel's pinned posts.
     *
     * @param array $pinned
     *
     * @return $this
     */
    public function setPinnedPosts($pinned)
    {
        $maxPinnedPosts = $this->isPro() ? 12 : 3;

        $this->pinned_posts = array_slice($pinned, -$maxPinnedPosts, null, false);

        return $this;
    }

    /**
     * Gets the channel's pinned posts.
     *
     * @return array
     */
    public function getPinnedPosts()
    {
        if (is_string($this->pinned_posts)) {
            return json_decode($this->pinned_posts);
        }

        return $this->pinned_posts;
    }

    /**
     * Sets (overrides) experimental feature flags.
     *
     * @return $this
     */
    public function setFeatureFlags(array $feature_flags)
    {
        $this->feature_flags = $feature_flags;

        return $this;
    }

    /**
     * Returns all set feature flags.
     *
     * @return array
     */
    public function getFeatureFlags()
    {
        return $this->feature_flags ?: [];
    }

    /**
     * Sets (overrides) programs participations.
     *
     * @return array
     */
    public function setPrograms(array $programs)
    {
        $this->programs = $programs;

        return $this;
    }

    /**
     * Returns all set programs participations.
     *
     * @return array
     */
    public function getPrograms()
    {
        if (is_string($this->programs)) {
            return json_decode($this->programs, true) ?: [];
        }

        return $this->programs ?: [];
    }

    /**
     * Sets (overrides) monetization settings.
     *
     * @return array
     */
    public function setMonetizationSettings(array $monetization_settings)
    {
        $this->monetization_settings = $monetization_settings;

        return $this;
    }

    /**
     * Returns all set monetization settings.
     *
     * @return array
     */
    public function getMonetizationSettings()
    {
        if (is_string($this->monetization_settings)) {
            return json_decode($this->monetization_settings, true) ?: [];
        }

        return $this->monetization_settings ?: [];
    }

    /**
     * Sets (overrides) group membership.
     *
     * @return array
     */
    public function setGroupMembership(array $group_membership)
    {
        $this->group_membership = $group_membership;

        return $this;
    }

    /**
     * Returns all set group membership.
     *
     * @return array
     */
    public function getGroupMembership()
    {
        if (is_string($this->group_membership)) {
            return json_decode($this->group_membership, true) ?: [];
        }

        return $this->group_membership ?: [];
    }

    /**
     * Sets the `boost autorotate` flag.
     *
     * @param bool $value
     *
     * @return $this
     */
    public function setBoostAutorotate($value)
    {
        $this->boost_autorotate = (bool) $value;

        return $this;
    }

    /**
     * Gets the `boost autorotate` flag.
     *
     * @return bool
     */
    public function getBoostAutorotate()
    {
        return (bool) $this->boost_autorotate;
    }

    /**
     * @param string $token
     * @return User
     */
    public function setEmailConfirmationToken(string $token): User
    {
        $this->email_confirmation_token = $token;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getEmailConfirmationToken(): ?string
    {
        return ((string) $this->email_confirmation_token) ?: null;
    }

    /**
     * @param int $time
     * @return User
     */
    public function setEmailConfirmedAt(?int $time): User
    {
        $this->email_confirmed_at = $time;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getEmailConfirmedAt(): ?int
    {
        return ((int) $this->email_confirmed_at) ?: null;
    }

    /**
     * Whether a user has an `email_confirmed_at` time.
     * Consider calling `isTrusted()` rather than calling this function directly
     * as isTrusted contains a polyfill for legacy accounts.
     * @return bool true if user has an `email_confirmed_at` time.
     */
    public function isEmailConfirmed(): bool
    {
        return (bool) $this->email_confirmed_at;
    }

    /**
     * @return bool
     */
    public function getHideShareButtons(): bool
    {
        return (bool) $this->hide_share_buttons;
    }

    /**
     * @param bool $value
     * @return User
     */
    public function setHideShareButtons(bool $value): User
    {
        $this->hide_share_buttons = $value;
        return $this;
    }

    /**
     * @return bool
     */
    public function getAllowUnsubscribedContact(): bool
    {
        return (bool) $this->allow_unsubscribed_contact;
    }

    /**
     * @param bool $value
     * @return User
     */
    public function setAllowUnsubscribedContact(bool $value): User
    {
        $this->allow_unsubscribed_contact = $value;
        return $this;
    }

    /**
     * It returns true if the user is verified or if the user is older than the new email confirmation feature
     * @return bool
     */
    public function isTrusted(): bool
    {
        return
            (!$this->getEmailConfirmationToken() && !$this->getEmailConfirmedAt()) || // Old users poly-fill
            $this->isEmailConfirmed();
    }

    /**
     * Subscribes user to another user.
     *
     * @param mixed $guid
     * @param array $data - metadata
     *
     * @return mixed
     */
    public function subscribe($guid, $data = [])
    {
        return \Minds\Helpers\Subscriptions::subscribe($this->guid, $guid, $data);
    }

    /**
     * Unsubscribes from another user.
     *
     * @param mixed $guid
     *
     * @return mixed
     */
    public function unSubscribe($guid)
    {
        return \Minds\Helpers\Subscriptions::unSubscribe($this->guid, $guid);
    }

    /**
     * Checks if subscribed to another user.
     *
     * @param mixed $guid - the user to check subscription to
     *
     * @return bool
     */
    public function isSubscriber($guid)
    {
        $cacher = Core\Data\cache\factory::build();

        if ($cacher->get("$this->guid:isSubscriber:$guid")) {
            return true;
        }
        if ($cacher->get("$this->guid:isSubscriber:$guid") === 0) {
            return false;
        }

        /** @var Subscriptions\Manager */
        $manager = Di::_()->get(Subscriptions\Manager::class);
        $return = $manager->setSubscriber((new User)->set('guid', $guid))->isSubscribed($this);

        $cacher->set("$this->guid:isSubscriber:$guid", $return);

        return $return;
    }

    /**
     * Checks if subscribed to another user in a
     * reversed way than isSubscribed().
     *
     * @param mixed $guid - the user to check subscription to
     *
     * @return bool
     */
    public function isSubscribed($guid)
    {
        $cacher = Core\Data\cache\factory::build();

        if ($cacher->get("$this->guid:isSubscribed:$guid")) {
            return true;
        }
        if ($cacher->get("$this->guid:isSubscribed:$guid") === 0) {
            return false;
        }

        /** @var Subscriptions\Manager */
        $manager = Di::_()->get(Subscriptions\Manager::class);
        $return = $manager->setSubscriber($this)->isSubscribed((new User)->set('guid', $guid));

        $cacher->set("$this->guid:isSubscribed:$guid", $return, 604800);

        return $return;
    }

    public function getSubscribersCount()
    {
        /** @var Subscriptions\Manager */
        $manager = Di::_()->get(Subscriptions\Manager::class);
        return $manager->setSubscriber($this)->getSubscribersCount();
    }

    /**
     * Gets the number of subscriptions.
     *
     * @return int
     */
    public function getSubscriptionsCount()
    {
        /** @var Subscriptions\Manager */
        $manager = Di::_()->get(Subscriptions\Manager::class);
        return $manager->setSubscriber($this)->getSubscriptionsCount();
    }

    public function getMerchant()
    {
        if ($this->merchant && !is_array($this->merchant)) {
            return json_decode($this->merchant, true);
        }

        return $this->merchant;
    }

    public function setMerchant($merchant)
    {
        $this->merchant = $merchant;

        return $this;
    }

    /**
     * Exports to an array.
     *
     * @return array
     */
    public function export()
    {
        $export = parent::export();
        $export['guid'] = (string) $this->guid;

        if (!isset($export['name']) || !$export['name']) {
            $export['name'] = $this->username;
        }

        // $export['name'] = htmlspecialchars_decode($export['name']);
        // $export['name'] = addslashes($export['name']);

        if ($this->fullExport) {
            if (Core\Session::isLoggedIn()) {
                $export['subscribed'] = Core\Session::getLoggedinUser()->isSubscribed($this->guid);
                $export['subscriber'] = Core\Session::getLoggedinUser()->isSubscriber($this->guid);
            }
        }
        if ($this->exportCounts) {
            if ($this->username != 'minds') {
                $export['subscribers_count'] = $this->getSubscribersCount();
            }
            $export['subscriptions_count'] = $this->getSubscriptionsCount();
            $export['impressions'] = $this->getImpressions();
        }
        $export['boost_rating'] = $this->getBoostRating();
        if ($this->fb && is_string($this->fb)) {
            $export['fb'] = json_decode($this->fb, true);
        }

        $export['merchant'] = $this->getMerchant() ?: false;
        $export['programs'] = $this->getPrograms();
        $export['plus'] = (bool) $this->isPlus();
        $export['pro'] = (bool) $this->isPro();
        if ($this->isPlus()) {
            $export['plus_method'] = $this->getPlusMethod();
        }
        if ($this->isPro()) {
            $export['pro_method'] = $this->getProMethod();
        }
        $export['verified'] = (bool) $this->verified;
        $export['founder'] = (bool) $this->founder;
        $export['disabled_boost'] = (bool) $this->disabled_boost;
        $export['boost_autorotate'] = (bool) $this->getBoostAutorotate();
        $export['categories'] = $this->getCategories();
        $export['pinned_posts'] = $this->getPinnedPosts();

        $export['tags'] = $this->getHashtags();
        $export['rewards'] = (bool) $this->getPhoneNumberHash();
        $export['is_mature'] = $this->isMature();
        $export['mature_lock'] = $this->getMatureLock();
        $export['mature'] = (int) $this->getViewMature();
        $export['last_accepted_tos'] = (int) $this->getLastAcceptedTOS();
        $export['supermind_settings'] = $this->getSupermindSettings();
        $export['opted_in_hashtags'] = (int) $this->getOptedInHashtags();
        $export['canary'] = (bool) $this->isCanary();
        $export['is_admin'] = $this->attributes['admin'] == 'yes';
        $export['theme'] = $this->getTheme();
        $export['onchain_booster'] = $this->getOnchainBooster();
        $export['toaster_notifications'] = $this->getToasterNotifications();
        $export['mode'] = $this->getMode();

        $export['briefdescription'] = (new BriefDescriptionLengthValidator())->validateMaxAndTrim((string) $export['briefdescription']);

        if (is_string($export['social_profiles'])) {
            $export['social_profiles'] = json_decode($export['social_profiles']);
        }

        if (is_string($export['wire_rewards'])) {
            $export['wire_rewards'] = json_decode($export['wire_rewards']);
        }

        if (is_string($export['feature_flags'])) {
            $export['feature_flags'] = json_decode($export['feature_flags']);
        }

        if ($this->isContext('search')) {
            $export['group_membership'] = $this->getGroupMembership();
        }

        if (Helpers\Flags::shouldDiscloseStatus($this)) {
            $export['spam'] = $this->getSpam();
            $export['deleted'] = $this->getDeleted();
        }

        $export['email_confirmed'] = $this->isTrusted();

        $export['eth_wallet'] = $this->getEthWallet() ?: '';
        $export['rating'] = $this->getRating();

        $export['hide_share_buttons'] = $this->getHideShareButtons();
        $export['allow_unsubscribed_contact'] = $this->getAllowUnsubscribedContact();
        $export['disable_autoplay_videos'] = $this->getDisableAutoplayVideos();
        $export['dismissed_widgets'] = $this->getDismissedWidgets();

        $export['yt_channels'] = $this->getYouTubeChannels();

        $export['liquidity_spot_opt_out'] = $this->getLiquiditySpotOptOut();
        $export['language'] = $this->getLanguage();

        $export['icon_url'] = $this->getIcon();

        $export['source'] = $this->getSource();
        $export['canonical_url'] = $this->getCanonicalUrl();
        $export['bot'] = $this->isBot();

        return $export;
    }

    /**
     * Get the number of impressions for the user.
     *
     * @return int
     */
    public function getImpressions()
    {
        $app = Core\Analytics\App::_()
            ->setMetric('impression')
            ->setKey($this->guid);

        return $app->total();
    }

    /**
     * Get the plus variable.
     *
     * @return int
     */
    public function getPlus()
    {
        return $this->isPlus();
    }

    /**
     * Is the user a plus user.
     *
     * @return bool
     */
    public function isPlus()
    {
        return $this->isPro() || ((int) $this->plus_expires > time());
    }

    /**
     * Set plus expires.
     *
     * @var int
     */
    public function setPlusExpires($expires)
    {
        $this->plus_expires = $expires;

        return $this;
    }

    /**
     * Set plus payment method.
     *
     * @param string $paymentMethod
     * @return self
     */
    public function setPlusMethod(string $paymentMethod): self
    {
        if (!in_array($paymentMethod, self::PLUS_PRO_VALID_METHODS, false)) {
            throw new \Exception("Invalid payment method '$paymentMethod' on User->setPlusMethod");
        }
        $this->plus_method = $paymentMethod;
        return $this;
    }

    /**
     * Get plus payment method
     * @return string
     */
    public function getPlusMethod(): string
    {
        return $this->plus_method ?: 'tokens';
    }

    /**
     * Get plus expires.
     *
     * @var int
     */
    public function getPlusExpires(): int
    {
        return $this->plus_expires;
    }

    /**
     * @param int $proExpires
     * @return User
     */
    public function setProExpires($proExpires)
    {
        $this->pro_expires = $proExpires;
        return $this;
    }

    /**
     * @return int
     */
    public function getProExpires()
    {
        return $this->pro_expires ?: 0;
    }

    /**
     * Set pro payment method
     *
     * @param string $paymentMethod
     * @return self
     */
    public function setProMethod(string $paymentMethod): self
    {
        if (!in_array($paymentMethod, self::PLUS_PRO_VALID_METHODS, false)) {
            throw new \Exception("Invalid payment method '$paymentMethod' on User->setProMethod");
        }
        $this->pro_method = $paymentMethod;
        return $this;
    }

    /**
     * Get plus payment method
     * @return string
     */
    public function getProMethod(): string
    {
        return $this->pro_method ?: 'tokens';
    }

    /**
     * @return bool
     */
    public function isPro()
    {
        return $this->getProExpires() >= time();
    }

    /**
     * Gets the categories to which the user is subscribed.
     *
     * @return array
     */
    public function getCategories()
    {
        if (is_string($this->categories)) {
            return json_decode($this->categories);
        }

        return $this->categories ?: [];
    }

    /**
     * Sets the categories to which the user is subscribed.
     *
     * @param $value
     */
    public function setCategories($value)
    {
        $this->categories = $value;
    }

    /**
     * @return string
     */
    public function getEthWallet()
    {
        return $this->eth_wallet ?: '';
    }

    /**
     * @param string $eth_wallet
     *
     * @return $this
     */
    public function setEthWallet($eth_wallet)
    {
        $this->eth_wallet = $eth_wallet ?: '';

        return $this;
    }

    /**
     * @param string $eth_incentive
     *
     * @return User
     */
    public function setEthIncentive($eth_incentive = '')
    {
        $this->eth_incentive = $eth_incentive;

        return $this;
    }

    /**
     * @return string
     */
    public function getEthIncentive()
    {
        return $this->eth_incentive;
    }

    /**
     * Gets the user's icon URL.
     *
     * @param string $size
     *
     * @return string
     */
    public function getIconURL($size = 'medium')
    {
        $join_date = $this->getTimeCreated();

        return Di::_()->get('Config')->get('site_url') . "icon/$this->guid/$size/$join_date/$this->icontime/" . Core\Config::_()->lastcache;
    }

    /**
     * @return bool
     */
    public function isMature()
    {
        return (bool) $this->is_mature;
    }

    /**
     * @param bool $value
     *
     * @return $this
     */
    public function setMature($value)
    {
        $this->is_mature = (bool) $value;

        return $this;
    }

    /**
     * @return bool
     */
    public function getMatureLock()
    {
        return (bool) $this->mature_lock;
    }

    /**
     * @param bool $value
     *
     * @return $this
     */
    public function setMatureLock($value)
    {
        $this->mature_lock = $value;

        return $this;
    }

    /**
     * @return int
     */
    public function getLastAcceptedTOS()
    {
        return $this->last_accepted_tos ?: 0;
    }

    /**
     * @param int $value
     *
     * @return $this
     */
    public function setLastAcceptedTOS($value)
    {
        $this->last_accepted_tos = $value;

        return $this;
    }

    /**
     * Getter for supermind settings.
     * @return array
     */
    public function getSupermindSettings(): array
    {
        return $this->supermind_settings && count($this->supermind_settings) ?
            $this->supermind_settings :
            (new Settings())->export(); // default settings.
    }

    /**
     * Set supermind settings.
     * @param string $value
     * @return $this
     */
    public function setSupermindSettings($value)
    {
        $this->supermind_settings = $value;
        return $this;
    }

    /**
     * @return int
     */
    public function getOptedInHashtags()
    {
        return $this->opted_in_hashtags ?: 0;
    }

    /**
     * @param int $value
     *
     * @return $this+
     */
    public function setOptedInHashtags(int $value)
    {
        $this->opted_in_hashtags += $value;

        return $this;
    }

    /**
     * Returns an array of which Entity attributes are exportable.
     *
     * @return array
     */
    public function getExportableValues()
    {
        return array_merge(parent::getExportableValues(), [
            'website',
            'briefdescription',
            'gender',
            'city',
            'merchant',
            'boostProPlus',
            'fb',
            'mature',
            'monetized',
            'signup_method',
            'social_profiles',
            'language',
            'feature_flags',
            'programs',
            'plus',
            'hashtags',
            'verified',
            'founder',
            'disabled_boost',
            'boost_autorotate',
            'categories',
            'wire_rewards',
            'pinned_posts',
            'is_mature',
            'mature_lock',
            'last_accepted_tos',
            'opted_in_hashtags',
            'last_avatar_upload',
            'canary',
            'theme',
            'onchain_booster',
            'toaster_notifications',
            'mode',
            'btc_address',
            'surge_token',
            'hide_share_buttons',
            'allow_unsubscribed_contact',
            'dismissed_widgets',
            'liquidity_spot_opt_out',
            'supermind_settings',
        ]);
    }

    public function getTags()
    {
        if (is_array($this->tags)) {
            return $this->tags;
        }

        return json_decode($this->tags, true);
    }

    /**
     * Check if user is in canary mode.
     *
     * @return bool
     */
    public function isCanary()
    {
        return (bool) $this->canary;
    }

    /**
     * Set the users canary status.
     *
     * @return $this
     * @var bool
     */
    public function setCanary($enabled = true)
    {
        $this->canary = $enabled ? 1 : 0;
        return $this;
    }

    /**
     * Get `theme`.
     *
     * @return string
     */
    public function getTheme()
    {
        return $this->theme;
    }

    /**
     * Set `theme``.
     *
     * @param string $value
     *
     * @return $this
     */
    public function setTheme($value)
    {
        $this->theme = $value;

        return $this;
    }

    /**
     * Preferred urn.
     *
     * @return string
     */
    public function getUrn(): string
    {
        return "urn:user:{$this->getGuid()}";
    }

    /**
     * Returns whether the user has onchain_booster status.
     *
     * @return bool true if the date set in onchain_booster is larger than the current time
     */
    public function isOnchainBooster()
    {
        return (bool) (time() < $this->onchain_booster);
    }

    /**
     * Gets the unix timestamp for the last time the user boosted onchain.
     *
     * @return int the date that a booster last boosted on chain
     */
    public function getOnchainBooster()
    {
        return (int) $this->onchain_booster;
    }

    /**
     * Sets the unix timestamp for the last time the user boosted onchain.
     *
     * @param int $time - the time to set the users onchain_booster variable to
     *
     * @return $this
     */
    public function setOnchainBooster($time)
    {
        $this->onchain_booster = (int) $time;

        return $this;
    }

    /**
     * Returns toaster notifications state.
     *
     * @return bool true if toaster notifications is enabled
     */
    public function getToasterNotifications()
    {
        return (bool) $this->toaster_notifications;
    }

    /**
     * Set on/off toaster notifications.
     *
     * @return User
     */
    public function setToasterNotifications($enabled = true)
    {
        $this->toaster_notifications = $enabled ? 1 : 0;

        return $this;
    }

    /**
     * Returns if video autoplay is disabled
     *
     * @return bool true if autoplay videos is enabled
     */
    public function getDisableAutoplayVideos()
    {
        return (bool) $this->disable_autoplay_videos;
    }

    /**
     * Set on/off disable autoplay videos.
     *
     * @return User
     */
    public function setDisableAutoplayVideos($disabled = false)
    {
        $this->disable_autoplay_videos = $disabled ? 1 : 0;

        return $this;
    }

    /**
     * @return string
     */
    public function getDateOfBirth()
    {
        return $this->dob;
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setDateOfBirth(string $value)
    {
        $this->dob = $value;
        return $this;
    }

    /**
     * Sets the public date of birth flag
     * @return bool
     */
    public function isPublicDateOfBirth(): bool
    {
        return (bool) $this->public_dob;
    }

    /**
     * Sets the public date of birth flag
     * @param bool $public_dob
     * @return $this
     */
    public function setPublicDateOfBirth(bool $public_dob): User
    {
        $this->public_dob = $public_dob;
        return $this;
    }

    /**
     * Returns channel mode value.
     *
     * @return int channel mode
     */
    public function getMode()
    {
        return (int) $this->mode;
    }

    /**
     * Sets the channel mode.
     *
     * @return User
     */
    public function setMode(int $mode)
    {
        $this->mode = $mode;

        return $this;
    }

    /**
     * Returns the YouTube OAuth Token
     * @return array
     */
    public function getYouTubeChannels()
    {
        return $this->attributes['yt_channels'] ?? [];
    }

    /**
     * Sets YouTube OAuth Token and when updates the connection timestamp
     * @param array $channels
     * @return $this
     */
    public function setYouTubeChannels(array $channels)
    {
        $this->attributes['yt_channels'] = $channels;

        return $this;
    }

    /**
     * Updates or add a YouTube channel
     * @param array $channel
     */
    public function updateYouTubeChannel(array $channel)
    {
        $updated = array_walk($this->attributes['yt_channels'], function (&$item) use ($channel) {
            if ($item['id'] === $channel['id']) {
                $item = array_replace($item, $channel);
            }
        });

        // if it didn't update, this means it's not there, so we'll add it
        if (!$updated) {
            array_push($this->attributes['yt_channels'], $channel);
        }
    }

    /**
     * Returns btc_address.
     *
     * @return string
     */
    public function getBtcAddress(): string
    {
        return (string) $this->btc_address;
    }

    /**
     * Set btc_address.
     *
     * @param string $btc_address
     */
    public function setBtcAddress(string $btc_address): User
    {
        $this->btc_address = (string) $btc_address;

        return $this;
    }

    /**
     * Gets the Surge Token of the user for push notifications.
     *
     * @return string Token.
     */
    public function getSurgeToken(): string
    {
        return (string) $this->surge_token ?? '';
    }

    /**
     * Sets the Surge Token of the user for push notifications.
     *
     * @param string $token - the token string.
     * @return User instance of $this for chaining.
     */
    public function setSurgeToken(string $token = ''): User
    {
        $this->surge_token = $token;
        return $this;
    }

    /**
     * Return an array of dismissed widgets
     * @return array
     */
    public function getDismissedWidgets(): ?array
    {
        return $this->dismissed_widgets;
    }

    /**
     * Set dismissed widgets
     * @param array $dimissedWidgets
     * @return self
     */
    public function setDismissedWidgets(array $dismissedWidgets = []): self
    {
        $this->dismissed_widgets = $dismissedWidgets;
        return $this;
    }

    /**
     * True if banned
     * @return bool
     */
    public function isBanned(): bool
    {
        return $this->banned === 'yes';
    }

    /**
     * Sets the rpm for pageviews
     * @param int $rpm
     * @return self
     */
    public function setPartnerRpm(int $rpm): self
    {
        $this->partner_rpm = $rpm;
        return $this;
    }

    /**
     * @return int
     */
    public function getPartnerRpm(): int
    {
        return $this->partner_rpm;
    }

    /**
     * @return array
     */
    public function getNsfw(): array
    {
        $nsfw = parent::getNsfw();
        if ($this->is_mature) {
            $nsfw[] = 6; // other
        }
        return $nsfw;
    }

    /**
     * Set Liquidity spot opt out
     * @param int $value
     * @return self
     */
    public function setLiquiditySpotOptOut($value): self
    {
        $this->liquidity_spot_opt_out = (int) $value;
        return $this;
    }

    /**
     * Return Liquidity spot opt out
     * @return int
     */
    public function getLiquiditySpotOptOut(): int
    {
        return $this->liquidity_spot_opt_out;
    }

    /**
     * Is Liquidity spot opt out
     * @return bool
     */
    public function isLiquiditySpotOptOut(): bool
    {
        return $this->getLiquiditySpotOptOut() === 1;
    }

    /**
     * Returns the twofactor value of a user
     * @return bool
     */
    public function getTwoFactor(): bool
    {
        return $this->twofactor;
    }

    /**
     * @inheritDoc
     */
    public function setSource(FederatedEntitySourcesEnum $source): FederatedEntityInterface
    {
        $this->source = $source->value;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getSource(): ?FederatedEntitySourcesEnum
    {
        $source = FederatedEntitySourcesEnum::from($this->source ?: 'local');

        // Tmp fix (hack) for bug found in https://gitlab.com/minds/minds/-/issues/4582
        if (count(explode('@', $this->username ?? '')) === 1 && $source === FederatedEntitySourcesEnum::ACTIVITY_PUB) {
            $source = FederatedEntitySourcesEnum::LOCAL;
        }

        return $source;
    }

    /**
     * @inheritDoc
     */
    public function setCanonicalUrl(string $canonicalUrl): FederatedEntityInterface
    {
        $this->canonical_url = $canonicalUrl;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getCanonicalUrl(): ?string
    {
        return $this->getSource() === FederatedEntitySourcesEnum::LOCAL ? null : $this->canonical_url;
    }
    
    /**
     * A user can opt of of analytics tracking
     */
    public function setOptOutAnalytics(bool $optOut): self
    {
        $this->opt_out_analytics = (int) $optOut;
        return $this;
    }

    public function isOptOutAnalytics(): bool
    {
        return (bool) $this->opt_out_analytics;
    }

    public function isBot(): bool
    {
        return (bool) $this->bot;
    }

    /**
     * @inheritDoc
     */
    public function toArray(): array
    {
        $array = parent::toArray();
        
        if (!$this->override_password && !$this->guid) {
            unset($array['password']);
            unset($array['salt']);
        }
        
        if (!$this->plus_expires || $this->plus_expires < time()) { //ensure we don't update this field
            unset($array['plus_expires']);
        }

        if (!$this->merchant || !is_array($this->merchant)) {
            unset($array['merchant']); //HACK: only allow updating of merchant if it's an array
        }

        return $array;
    }

}
