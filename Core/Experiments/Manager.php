<?php

/**
 * Experiments Manager. Handles experiments and feature flags
 * specified within Growthbook. State of a flag can be checked by calling
 * setUser($user) followed by isOn($flag). This will check both experiments
 * AND features. Also for checking experiments you can use the
 * hasVariation(key, val) function.
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
    /** @var Growthbook\Growthbook */
    private $growthbook;

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
        Growthbook\Growthbook $growthbook = null,
        CookieManager $cookieManager = null,
        GuzzleHttp\Client $httpClient = null,
        Config $config = null,
        PsrWrapper $psrCache = null
    ) {
        $this->cookieManager = $cookieManager ?? Di::_()->get('Experiments\Cookie\Manager');
        $this->httpClient = $httpClient ?? new GuzzleHttp\Client();
        $this->config = $config ?? Di::_()->get('Config');
        $this->psrCache = $psrCache ?? Di::_()->get('Cache\PsrWrapper');
        $this->growthbook = $growthbook ?? Growthbook\Growthbook::create();

        $this->initFeatures();
    }

    /**
     * Getter for class level user.
     * @return User|null - User that has been set.
     */
    public function getUser(): ?User
    {
        return $this->user;
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
    public function setUser(User $user = null): self
    {
        $this->user = $user;

        $this->growthbook->withAttributes(array_merge(
            $this->growthbook->getAttributes(),
            [ 'id' => $this->getUserId() ]
        ));

        return $this;
    }

    /**
     * Gets features from cache or growthbook
     * @return array - features from cache or growthbook.
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
     * Returns public export of growthbook features.
     * @return array - public export of growthbook features.
     */
    public function getExportableConfig(): array
    {
        return [
            'attributes' => [
                'id' => $this->getUserId(),
                'loggedIn' => !!$this->getUser(),
            ],
            'features' => $this->getFeatures(),
        ];
    }

    /**
     * Whether feature / experiment is on or off.
     * @param string $featureKey - the key of the feature.
     * @return boolean - true if feature / experiment is on.
     */
    public function isOn(string $featureKey): bool
    {
        return $this->growthbook->isOn($featureKey);
    }

    /**
     * Whether user has been put in the specified variation of an experiment.
     * @param string $featureKey - the key of the feature.
     * @param $variationId - the variation label.
     * @return boolean - true if feature is on and experiment is active for user.
     */
    public function hasVariation(string $featureKey, $variation): bool
    {
        $defaultValue = $this->growthbook->getFeatures()[$featureKey]->defaultValue ?? false;
        return $this->growthbook->getValue($featureKey, $defaultValue) === $variation;
    }

    /**
     * Get viewed experiments from Growthbook
     * @return array - array of viewed experiments.
     */
    public function getViewedExperiments(): array
    {
        return $this->growthbook->getViewedExperiments();
    }

    /**
     * Gets User ID for experiments. Will get either the users logged in guid, the experimentsId via cookie,
     * or generate a unique ID and store it in a cookie.
     * @return string - user id for experiments.
     */
    public function getUserId(): string
    {
        if ($this->getUser()) {
            return $this->getUser()->getGuid();
        }

        // if cookie exists, return it's value as the id.
        $experimentsIdCookie = $this->cookieManager->get();

        if ($experimentsIdCookie) {
            return $experimentsIdCookie;
        }

        // else if no user - generate an ID, store it in a cookie.
        $id = uniqid('exp-', true);
        $this->cookieManager->set($id);
        return $id;
    }

    /**
     * Inits growthbook by getting features and user attributes and
     * assigning them to growthbook.
     * @return self - instance of $this.
     */
    private function initFeatures(): self
    {
        $features = $this->getFeatures(true);

        $this->growthbook
            ->withFeatures($features)
            ->withAttributes($this->getAttributes());

        return $this;
    }

    /**
     * Gets attributes for a user to be passed to growthbook.
     * @return array - attributes for a user.
     */
    private function getAttributes(): array
    {
        $attributes = [
            'id' => $this->getUserId(),
            'loggedIn' => !!$this->getUser(),
            'route' => $_SERVER['HTTP_REFERER'] ?? '',
            'api_path' => strtok($_SERVER['REQUEST_URI'] ?? '', '?') ?? '',
            'environment' => getenv('MINDS_ENV') ?: 'development'
        ];

        if ($this->user) {
            $attributes['userAge'] = $this->user->getAge();
        }

        return $attributes;
    }

    /**
     * Gets the endpoint for getting features from growthbook.
     * @return string - endpoint for growthbook features.
     */
    private function getGrowthbookFeaturesEndpoint(): ?string
    {
        return $this->config->get('growthbook')['features_endpoint'] ?? null;
    }
}
