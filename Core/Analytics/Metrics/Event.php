<?php

namespace Minds\Core\Analytics\Metrics;

use Minds\Common\PseudonymousIdentifier;
use Minds\Core;
use Minds\Core\AccountQuality\ManagerInterface as AccountQualityManagerInterface;
use Minds\Core\Analytics\Snowplow;
use Minds\Core\Config\Config;
use Minds\Core\Data\ElasticSearch;
use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
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

    /** @var Snowplow\Manager */
    private $snowplowManager;

    /** @var EntitiesBuilder */
    private $entitiesBuilder;

    /** @var AccountQualityManagerInterface */
    private $accountQualityManager;

    /** @var array */
    protected $data;

    /** @var User */
    protected $user;

    public function __construct(
        $elastic = null,
        $snowplowManager = null,
        $entitiesBuilder = null,
        AccountQualityManagerInterface $accountQualityManager = null,
        protected ?PseudonymousIdentifier $pseudoId = null,
        protected ?Config $config = null,
    ) {
        $this->elastic = $elastic ?: Core\Di\Di::_()->get('Database\ElasticSearch');
        $this->index = 'minds-metrics-'.date('m-Y', time());
        $this->snowplowManager = $snowplowManager ?? Di::_()->get('Analytics\Snowplow\Manager');
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

        // Submit to snowplow

        $this->emitToSnowplow();

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
     * Emit the event to snowplow
     * @return void
     */
    protected function emitToSnowplow(): void
    {
        if (
            !isset($this->data['action']) ||
            $this->data['type'] !== 'action' ||
            $this->data['action'] === 'pageview' ||
            (!isset($this->data['user_guid']) && $this->data['action'] !== 'create')
        ) {
            return; // We only want to submit actions and not legacy pageviews
        }

        $entityContext = new Snowplow\Contexts\SnowplowEntityContext();
        $sessionContext = new Snowplow\Contexts\SnowplowSessionContext();
        $proofOfWorkContext = new Snowplow\Contexts\SnowplowProofOfWorkContext();
        $clientMetaContext = new Snowplow\Contexts\SnowplowClientMetaContext();
        $contexts = [ $entityContext, $sessionContext, $proofOfWorkContext, $clientMetaContext ];

        $event = new Snowplow\Events\SnowplowActionEvent();

        $event->setAction($this->data['action']);

        if ($this->data['entity_guid'] ?? null) {
            $entityContext->setEntityGuid($this->data['entity_guid']);
        }

        if ($this->data['entity_type'] ?? null) {
            $entityContext->setEntityType($this->data['entity_type']);
        }

        if ($this->data['entity_subtype'] ?? null) {
            $entityContext->setEntitySubtype($this->data['entity_subtype']);
        }

        if ($this->data['entity_owner_guid'] ?? null) {
            $entityContext->setEntityOwnerGuid($this->data['entity_owner_guid']);
        }

        if ($this->data['entity_access_id'] ?? null) {
            $entityContext->setEntityAccessId($this->data['entity_access_id']);
        }

        if ($this->data['entity_container_guid'] ?? null) {
            $entityContext->setEntityContainerGuid($this->data['entity_container_guid']);
        }

        if ($this->data['comment_guid'] ?? null) {
            $event->setCommentGuid($this->data['comment_guid']);
        }

        if ($this->data['boost_rating'] ?? null) {
            $event->setBoostRating($this->data['boost_rating']);
        }

        if ($this->data['boost_reject_reason'] ?? null) {
            $event->setBoostRejectReason($this->data['boost_reject_reason']);
        }

        if ($this->data['user_phone_number_hash'] ?? null) {
            $sessionContext->setUserPhoneNumberHash($this->data['user_phone_number_hash']);
        }

        if ($this->data['proofOfWork'] ?? null) {
            $proofOfWorkContext->setSuccessful($this->data['proofOfWork']);
        }

        // Setting the client meta context details for snowplow
        if ($this->data['client_meta'] ?? null) {
            $clientMetaContext->platform = $this->data['client_meta']['platform'] ?? "";
            $clientMetaContext->source = $this->data['client_meta']['source'] ?? "";
            $clientMetaContext->salt = $this->data['client_meta']['salt'] ?? "";
            $clientMetaContext->medium = $this->data['client_meta']['medium'] ?? "";
            $clientMetaContext->campaign = $this->data['client_meta']['campaign'] ?? "";
            $clientMetaContext->page_token = $this->data['client_meta']['page_token'] ?? "";
            $clientMetaContext->delta = $this->data['client_meta']['delta'] ?? 0;
            $clientMetaContext->position = $this->data['client_meta']['position'] ?? 0;
            $clientMetaContext->served_by_guid = $this->data['client_meta']['served_by_guid'] ?? "";
        }

        // Rebuild the user, as we need the full entity

        /** @var User */
        $user = $this->entitiesBuilder->single($this->data['user_guid']);


        $event->setContext($contexts);


        // Emit the event
        $this->snowplowManager->setSubject($user)->emit($event);
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
