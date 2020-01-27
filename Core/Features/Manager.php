<?php

/**
 * Features Manager
 *
 * @author emi
 */

namespace Minds\Core\Features;

use Minds\Core\Di\Di;
use Minds\Core\Features\Exceptions\FeatureNotImplementedException;
use Minds\Core\Sessions\ActiveSession;

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

    /**
     * Manager constructor.
     * @param Services\ServiceInterface[] $services
     * @param ActiveSession $activeSession
     * @param string[] $features
     */
    public function __construct(
        $services = null,
        $activeSession = null,
        array $features = null
    ) {
        $this->services = $services ?: [
            new Services\Config(),
            new Services\Unleash(),
            new Services\Environment(),
        ];
        $this->activeSession = $activeSession ?: Di::_()->get('Sessions\ActiveSession');
        $this->featureKeys = ($features ?? Di::_()->get('Features\Keys')) ?: [];
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
                    ->setUser($this->activeSession->getUser())
                    ->fetch($this->featureKeys)
            );
        }

        //

        return $features;
    }
}
