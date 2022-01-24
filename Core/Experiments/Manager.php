<?php
/**
 * Experiments manager
 */
namespace Minds\Core\Experiments;

use Minds\Entities\User;
use Growthbook;
use GuzzleHttp;
use Minds\Core\Config\Config;
use Minds\Core\Data\cache\PsrWrapper;
use Minds\Core\Di\Di;
use Minds\Core\Experiments\Cookie\Manager as CookieManager;

class Manager
{
    /** @var Growthbook\Client */
    private $growthbook;

    /** @var Growthbook\User */
    private $growthbookUser;

    /** @var User */
    private $user;

    /** @var CookieManager */
    private $cookieManager;

    /** @var GuzzleHttp\Client */
    private $httpClient;

    /** @var Config */
    private $config;

    /** @var PsrWrapper */
    private $psrCache;

    /** @var string */
    const FEATURES_CACHE_KEY = 'growthbook-features';

    public function __construct(
        Growthbook\Client $growthbook = null,
        CookieManager $cookieManager = null,
        GuzzleHttp\Client $httpClient = null,
        Config $config = null,
        PsrWrapper $psrCache = null
    ) {
        $this->growthbook = $growthbook ?? new Growthbook\Client();
        $this->cookieManager = $cookieManager ?? Di::_()->get('Experiments\Cookie\Manager');
        $this->httpClient = $httpClient ?? new GuzzleHttp\Client();
        $this->config = $config ?? Di::_()->get('Config');
        $this->psrCache = $psrCache ?? Di::_()->get('Cache\PsrWrapper');
    }

    /**
     * Set the user who will view experiments
     * !! CONFUSION WARNING !!
     * The id here is not what growthbook will use in its reporting.
     * For reporting purposes in snowplow, anonymous users will be `network_user_id`
     * and loggedin users will be `user_id`.
     * If you're experiment is tracking conversion from logged out to signups, your report
     * will want to use `network_user_id`
     * @param User $user (optional)
     * @return Manager
     */
    public function setUser(User $user = null)
    {
        $this->user = $user;

        // TODO: this will be removed soon
        $this->growthbookUser = $this->growthbook->user([
            'id' => $this->getUserId($user),
        ]);
        return $this;
    }

    /**
     * @return array
     */
    public function getFeatures($useCached = true): array
    {
        if (!$this->getGrowthbookFeaturesEndpoint()) {
            return [];
        }
        
        // If we have a cached version use that
        if ($useCached) {
            $cached = $this->psrCache->get(static::FEATURES_CACHE_KEY);
            if ($cached) {
                return $cached;
            }
        }

        try {
            $json = $this->httpClient->request('GET', $this->getGrowthbookFeaturesEndpoint(), [
                'headers' => [
                    'Content-Type' => 'application/json'
                ],
            ]);

            $responseData = json_decode($json->getBody()->getContents(), true);
            $features = $responseData['features'] ?? [];

            // Set the cache
            $this->psrCache->set(static::FEATURES_CACHE_KEY, $features);
        } catch (\Exception $e) {
            return [];
        }

        return $features;
    }

    /**
     * Returns public export of growthbook features
     * @return array
     */
    public function getExportableConfig(): array
    {
        return [
            'attributes' => [
                'id' => $this->getUserId($this->user),
                'loggedIn' => !!$this->user,
            ],
            'features' => $this->getFeatures(),
        ];
    }
    
    /**
     * Gets User ID for experiments. Will get either the users experimentsId via cookie,
     * their logged in user GUID, or generate a unique ID and store it in a cookie.
     * @param User $user - user to generate / get ID for - nullable.
     * @return string user id for experiments.
     */
    private function getUserId(User $user = null): string
    {
        // if cookie exists, return it's value as the id.
        $experimentsIdCookie = $this->cookieManager->get();

        if ($experimentsIdCookie) {
            return $experimentsIdCookie;
        }

        // else if user is logged in, use their userGuid.
        if ($user) {
            return $user->getGuid();
        }

        // else if no user - generate an ID, store it in a cookie.
        $id = uniqid('exp-', true);
        $this->cookieManager->set($id);
        return $id;
    }

    /**
     * @return string
     */
    private function getGrowthbookFeaturesEndpoint(): ?string
    {
        return $this->config->get('growthbook')['features_endpoint'] ?? null;
    }
}
