<?php

/**
 * Features Manager
 *
 * @author emi
 */

namespace Minds\Core\Features;

use Exception;
use Minds\Core\Di\Di;
use Minds\Core\Features\Exceptions\FeatureNotImplementedException;
use Minds\Core\Sessions\ActiveSession;
use Minds\Entities\User;

/**
 * Features Manager
 * @package Minds\Core\Features
 */
class Manager
{
    /** @var Services\ServiceInterface[] */
    protected $services;

    /** @var ActiveSession */
    protected $activeSession;

    /** @var string[] */
    protected $featureKeys;

    /** @var string */
    protected $environment;

    /**
     * Manager constructor.
     * @param string $environment
     * @param Services\ServiceInterface[] $services
     * @param ActiveSession $activeSession
     * @param string[] $features
     */
    public function __construct(
        $environment = null,
        $services = null,
        $activeSession = null,
        array $features = null
    ) {
        $this->environment = $environment;
        $this->services = $services ?: [
            new Services\Config(),
            new Services\Unleash(),
            new Services\Environment(),
            new Services\Cypress(),
        ];
        $this->activeSession = $activeSession ?: Di::_()->get('Sessions\ActiveSession');
        $this->featureKeys = ($features ?? Di::_()->get('Features\Keys')) ?: [];
    }

    /**
     * Sets the current environment
     * @param string $environment
     * @return Manager
     */
    public function setEnvironment(string $environment): Manager
    {
        $this->environment = $environment;
        return $this;
    }

    /**
     * Gets the current environment based on overrides or environment variables
     * @return string
     */
    public function getEnvironment()
    {
        return $this->environment ?: getenv('MINDS_ENV') ?: 'development';
    }

    /**
     * Synchronizes all services using their respective mechanisms
     * @param int $ttl
     * @return iterable
     */
    public function sync(int $ttl): iterable
    {
        foreach ($this->services as $service) {
            try {
                $output = $service
                    ->setEnvironment($this->getEnvironment())
                    ->sync($ttl);
            } catch (Exception $e) {
                $output = $e;
            }

            yield get_class($service) => $output;
        }
    }

    /**
     * Checks if a feature is enabled
     * @param string $feature
     * @return bool
     * @throws FeatureNotImplementedException
     */
    public function has(string $feature): ?bool
    {
        $features = $this->export();

        if (!isset($features[$feature])) {
            throw new FeatureNotImplementedException(
                "${feature}: Not Implemented"
            );
        }

        return (bool) $features[$feature];
    }

    /**
     * Exports the whole features array based on Features DI
     * @return array
     */
    public function export(): array
    {
        $features = [];

        // Initialize array with false values

        foreach ($this->featureKeys as $feature) {
            $features[$feature] = false;
        }

        // Fetch from every service

        foreach ($this->services as $service) {
            $features = array_merge(
                $features,
                $service
                    ->setEnvironment($this->getEnvironment())
                    ->setUser($this->activeSession->getUser())
                    ->fetch($this->featureKeys)
            );
        }

        //

        return $features;
    }

    /**
     * Breakdown for services, features and its individual values for certain user.
     * Used by admin interface.
     * @param User|null $for
     * @return array
     */
    public function breakdown(?User $for = null)
    {
        $env = $this->getEnvironment();

        $output = [
            'environment' => $env,
            'for' => $for ? (string) $for->username : null,
            'services' => [
                'Default'
            ],
            'features' => [],
        ];

        $cache = [];

        foreach ($this->featureKeys as $feature) {
            $cache[$feature] = [
                'Default' => false,
            ];

            foreach ($this->services as $service) {
                $cache[$feature][$service->getReadableName()] = null;
            }
        }

        foreach ($this->services as $service) {
            $output['services'][] = $service->getReadableName();

            $features = [];

            $features = array_merge(
                $features,
                $service
                    ->setUser($for)
                    ->setEnvironment($env)
                    ->fetch($this->featureKeys)
            );

            foreach ($features as $feature => $value) {
                $cache[$feature][$service->getReadableName()] = $value;
            }
        }

        foreach ($cache as $name => $services) {
            $output['features'][] = compact('name', 'services');
        }

        usort($output['features'], function ($a, $b) {
            return $a['name'] <=> $b['name'];
        });

        return $output;
    }
}
