<?php

namespace Minds\Core\Rewards\Restrictions\Blockchain\Ofac;

use Minds\Core\Di\Di;
use Minds\Core\Log\Logger;
use Minds\Core\Rewards\Restrictions\Blockchain\Exceptions\UnsupportedNetworkException;
use Minds\Core\Rewards\Restrictions\Blockchain\Manager as RestrictionsManager;
use Minds\Core\Rewards\Restrictions\Blockchain\Restriction;

/**
 * Ofac Manager - used to populate database with entries from OFAC sanction list.
 */
class Manager
{
    public function __construct(
        private ?Client $client = null,
        private ?RestrictionsManager $restrictionsManager = null,
        private ?Logger $logger = null
    ) {
        $this->client ??= Di::_()->get('Rewards\Restrictions\Blockchain\Ofac\Client');
        $this->restrictionsManager ??= Di::_()->get('Rewards\Restrictions\Blockchain\Manager');
        $this->logger ??= Di::_()->get('Logger');
    }

    /**
     * Populate database from OFAC entries - will clean out existing entries with
     * the reason 'ofac' automatically.
     * @return void
     */
    public function populate(): void
    {
        $this->deleteExisting();
        $sanctions = $this->client->getAll();

        foreach ($sanctions as $sanction) {
            try {
                $this->restrictionsManager->add(
                    (new Restriction())
                        ->setAddress($sanction['address'])
                        ->setReason('ofac')
                        ->setNetwork($sanction['network'])
                );
            } catch (UnsupportedNetworkException $e) {
                $this->logger->warning("Unsupported network: {$sanction['network']} for address: {$sanction['address']}");
            }
        }
    }

    /**
     * Delete existing OFAC entries in database.
     * @return bool
     */
    private function deleteExisting(): bool
    {
        return $this->restrictionsManager->deleteByReason('ofac');
    }
}
