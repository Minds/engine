<?php

namespace Minds\Core\Analytics\Metrics;

use Minds\Common\PseudonymousIdentifier;
use Minds\Core;
use Minds\Core\AccountQuality\ManagerInterface as AccountQualityManagerInterface;
use Minds\Core\Analytics\PostHog\PostHogService;
use Minds\Core\Config\Config;
use Minds\Core\Data\ElasticSearch;
use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Session;
use Minds\Entities\User;

/**
 * Class Event.
 *
 * @method Event setType($value)
 * @method Event setAction($value)
 * @method Event setProduct($value)
 * @method Event setUserPhoneNumberHash($value)
 * @method Event setEntityGuid($value)
 * @method Event setEntityContainerGuid($value)
 * @method Event setEntityAccessId($value)
 * @method Event setEntityType($value)
 * @method Event setEntitySubtype($value)
 * @method Event setEntityOwnerGuid($value)
 * @method Event setCommentGuid($value)
 * @method Event setRatelimitKey($value)
 * @method Event setRatelimitPeriod($value)
 * @method Event setPlatform($value)
 * @method Event setEmailCampaign($value)
 * @method Event setEmailTopic($topic)
 * @method Event setEmailState($state)
 * @method Event setCookieId($cookieId)
 * @method Event setLoggedIn(bool $loggedIn)
 * @method Event setReferrerGuid($referrerGuid)
 * @method Event setProReferrer(bool $proReferrer)
 * @method Event setIsRemind(bool $isRemind)
 * @method Event setProofOfWork(bool $proofOfWork)
 * @method Event setClientMeta(array $clientMeta)
 */
class Event
{
    /** @var ElasticSearch\Client */
    private $elastic;

    /** @var string */
    private $index = 'minds-metrics-';

    /** @var EntitiesBuilder */
    private $entitiesBuilder;

    /** @var AccountQualityManagerInterface */
    private $accountQualityManager;

    /** @var array */
    protected $data;

    /** @var User */
    protected $user;

    protected string $eventName;

    public function __construct(
        $elastic = null,
        protected ?PostHogService $postHogService = null,
        $entitiesBuilder = null,
        AccountQualityManagerInterface $accountQualityManager = null,
        protected ?PseudonymousIdentifier $pseudoId = null,
        protected ?Config $config = null,
    ) {
        $this->elastic = $elastic ?: Core\Di\Di::_()->get('Database\ElasticSearch');
        $this->index = 'minds-metrics-'.date('m-Y', time());
        $this->postHogService ??= Di::_()->get(PostHogService::class);
        $this->entitiesBuilder = $entitiesBuilder ?? Di::_()->get('EntitiesBuilder');
        $this->accountQualityManager = $accountQualityManager ?? Di::_()->get('AccountQuality\Manager');
        $this->pseudoId = $pseudoId ?? new PseudonymousIdentifier();
        $this->config ??= Di::_()->get(Config::class);
    }

    /**
     * Set the user guid (and will build a new user entity)
     * @param string $guid
     * @return self
     */
    public function setUserGuid($guid)
    {
        $this->data['user_guid'] = (string) $guid;

        // Rebuild the user, as we need the full entity
        $user = $this->entitiesBuilder->single($this->data['user_guid']);
        if ($user instanceof User) {
            $this->user = $user;
        }

        return $this;
    }

    /**
     * Set the user entity and applies their guid to the data
     * @param User $user
     * @return self
     */
    public function setUser(User $user): self
    {
        $this->user = $user;
        $this->data['user_guid'] = (string) $user->getGuid();
        return $this;
    }

    public function push(bool $shouldIndex = true)
    {
        $this->data['@timestamp'] = (int) microtime(true) * 1000;

        if ($tenantId = $this->config->get('tenant_id')) {
            $this->data['tenant_id'] = $tenantId;
        }

        if ($this->user) {
            $this->data['user_is_plus'] = (bool) $this->user->isPlus();

            if ($this->data['action']) {
                $this->data['account_quality_score'] = $this->getAccountQualityScore();
            }

            $this->data['source'] = $this->user->getSource()->value;
        }

        if (isset($this->data['client_meta'])) {
            $this->data['campaign'] = $this->data['client_meta']['campaign'] ?? "";
        }

        $this->data['user_agent'] = $this->getUserAgent();
        $this->data['ip_hash'] = $this->getIpHash();
        $this->data['ip_range_hash'] = $this->getIpRangeHash();

        if (isset($_SERVER['HTTP_APP_VERSION'])) {
            $this->data['mobile_version'] = $_SERVER['HTTP_APP_VERSION'];
        }

        if (!isset($this->data['platform'])) {
            $platform = isset($_REQUEST['cb']) ? 'mobile' : 'browser';
            if (isset($_REQUEST['platform'])) { //will be the sole method once mobile supports
                $platform = $_REQUEST['platform'];
            }
            $this->data['platform'] = $platform;
        }

        // Submit to PostHog

        $this->emitToPostHog();

        if (!$shouldIndex) {
            return;
        }

        // Submit to ES

        $prepared = new Core\Data\ElasticSearch\Prepared\Index();
        $prepared->query([
            'body' => $this->data,
            'index' => $this->index,
            //'id' => $data['guid'],
            'client' => [
                'timeout' => 2,
                'connect_timeout' => 1,
            ],
        ]);

        try {
            return $this->elastic->request($prepared);
        } catch (\Exception $e) {
        }
    }

    /**
     * Sets the event name to pass through to analytics
     */
    public function setEventName(string $object, string $verb): self
    {
        $this->eventName = "{$object}_{$verb}";
        return $this;
    }

    /**
     * Magic method for getter and setters.
     */
    public function __call($name, array $args = [])
    {
        if (strpos($name, 'set', 0) === 0) {
            $attribute = str_replace('set', '', $name);
            $attribute = implode('_', preg_split('/([\s])?(?=[A-Z])/', $attribute, -1, PREG_SPLIT_NO_EMPTY));
            $attribute = strtolower($attribute);
            $this->data[$attribute] = $args[0];

            return $this;
        }
    }

    public function getData()
    {
        return $this->data;
    }

    /**
     * For security, record the user agent.
     *
     * @return string
     */
    protected function getUserAgent()
    {
        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            return $_SERVER['HTTP_USER_AGENT'];
        }

        return '';
    }

    protected function getIpHash()
    {
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return hash('sha256', $_SERVER['HTTP_X_FORWARDED_FOR']);
        }

        return '';
    }

    protected function getIpRangeHash()
    {
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $parts = explode('.', $_SERVER['HTTP_X_FORWARDED_FOR']);
            array_pop($parts);

            return hash('sha256', implode('.', $parts));
        }

        return '';
    }

    /**
     * Emit the event to PostHog
     * @return void
     */
    protected function emitToPostHog(): void
    {
        if (
            !isset($this->data['action']) ||
            $this->data['type'] !== 'action' ||
            $this->data['action'] === 'pageview' ||
            (!isset($this->data['user_guid']) && $this->data['action'] !== 'create')
        ) {
            return; // We only want to submit actions and not legacy pageviews
        }

        // Rebuild the user, as we need the full entity

        /** @var User */
        $user = $this->entitiesBuilder->single($this->data['user_guid'] ?? Session::getLoggedInUserGuid());

        if (!$user) {
            return;
        }

        $properties = array_filter($this->data, fn ($key) => in_array($key, [
                'entity_guid',
                'entity_type',
                'entity_subtype',
                'entity_owner_guid',
        ], true), ARRAY_FILTER_USE_KEY);

        $eventName = isset($this->eventName) ? $this->eventName
            : ($this->data['entity_type'] ?? 'user') . '_' . str_replace(':', '_', $this->data['action']);

        $this->postHogService->capture(
            event:  $eventName,
            user: $user,
            properties: $properties,
        );
    }

    /**
     * Gets account quality score from manager.
     * @return float account quality score.
     */
    protected function getAccountQualityScore(): float
    {
        return $this->accountQualityManager->getAccountQualityScoreAsFloat(
            $this->pseudoId->getId() ?: $this->user->getGuid()
        );
    }
}
