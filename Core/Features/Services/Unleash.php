<?php
/**
 * Unleash
 *
 * @author edgebal
 */

namespace Minds\Core\Features\Services;

use Exception;
use Minds\Core\Config;
use Minds\Core\Di\Di;
use Minds\Core\Features\Services\Unleash\Entity;
use Minds\Core\Features\Services\Unleash\Repository;
use Minds\UnleashClient\Exceptions\InvalidFeatureImplementationException;
use Minds\UnleashClient\Exceptions\InvalidFeatureNameException;
use Minds\UnleashClient\Exceptions\InvalidFeaturesArrayException;
use Minds\UnleashClient\Exceptions\InvalidStrategyImplementationException;
use Minds\UnleashClient\Exceptions\NoContextException;
use Minds\UnleashClient\Factories\FeatureArrayFactory as UnleashFeatureArrayFactory;
use Minds\UnleashClient\Entities\Context;
use Minds\UnleashClient\Unleash as UnleashResolver;

/**
 * Unleash server (GitLab FF) feature flags service
 * @package Minds\Core\Features\Services
 */
class Unleash extends BaseService
{
    /** @var Config */
    protected $config;

    /** @var Repository */
    protected $repository;

    /** @var UnleashResolver */
    protected $unleashResolver;

    /** @var UnleashFeatureArrayFactory */
    protected $unleashFeatureArrayFactory;

    /** @var Unleash\ClientFactory */
    protected $unleashClientFactory;

    /**
     * Unleash constructor.
     * @param Config $config
     * @param Repository $repository
     * @param UnleashResolver $unleashResolver
     * @param UnleashFeatureArrayFactory $unleashFeatureArrayFactory
     * @param Unleash\ClientFactory $unleashClientFactory
     */
    public function __construct(
        $config = null,
        $repository = null,
        $unleashResolver = null,
        $unleashFeatureArrayFactory = null,
        $unleashClientFactory = null
    ) {
        $this->config = $config ?: Di::_()->get('Config');
        $this->repository = $repository ?: new Repository();
        $this->unleashResolver = $unleashResolver ?: new UnleashResolver(Di::_()->get('Logger\Singleton'));
        $this->unleashFeatureArrayFactory = $unleashFeatureArrayFactory ?: new UnleashFeatureArrayFactory();
        $this->unleashClientFactory = $unleashClientFactory ?: new Unleash\ClientFactory($this->config, Di::_()->get('Logger\Singleton'));
    }

    /**
     * @inheritDoc
     */
    public function getReadableName(): string
    {
        return 'GitLab';
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function sync(int $ttl): bool
    {
        $client = $this->unleashClientFactory
            ->build($this->environment);

        $registered = $client->register();

        if (!$registered) {
            throw new Exception('Could not register Unleash client');
        }

        $now = time();
        $features = $client->fetch();

        foreach ($features as $feature) {
            $entity = new Entity();
            $entity
                ->setEnvironment($this->environment)
                ->setFeatureName($feature['name'])
                ->setData($feature)
                ->setCreatedAt($now)
                ->setStaleAt($now + $ttl);

            $this->repository
                ->add($entity);
        }

        return true;
    }

    /**
     * @inheritDoc
     * @param array $keys
     * @return array
     * @throws InvalidFeatureImplementationException
     * @throws InvalidFeatureNameException
     * @throws InvalidFeaturesArrayException
     * @throws InvalidStrategyImplementationException
     * @throws NoContextException
     * @throws Exception
     */
    public function fetch(array $keys): array
    {
        $context = new Context();
        $context
            ->setUserGroups($this->getUserGroups())
            ->setRemoteAddress($_SERVER['REMOTE_ADDR'] ?? '')
            ->setHostName($_SERVER['HTTP_HOST'] ?? '');

        if ($this->user) {
            $context
                ->setUserId((string) $this->user->guid);
        }

        // Read features from local repository

        $features = $this->unleashFeatureArrayFactory
            ->build(
                $this->repository
                    ->getAllData([
                        'environment' => $this->environment,
                    ])
                    ->toArray()
            );

        // Return whitelisted 'features' array with its values resolved

        return array_intersect_key(
            $this->unleashResolver
                ->setContext($context)
                ->setFeatures($features)
                ->export(),
            array_flip($keys)
        );
    }
}
