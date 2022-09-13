<?php

namespace Spec\Minds\Core\Rewards\Restrictions\Blockchain\Ofac;

use PhpSpec\ObjectBehavior;
use Minds\Core\Rewards\Restrictions\Blockchain\Ofac\Manager;
use Minds\Core\Log\Logger;
use Minds\Core\Rewards\Restrictions\Blockchain\Manager as RestrictionsManager;
use Minds\Core\Rewards\Restrictions\Blockchain\Ofac\Client;
use Minds\Core\Rewards\Restrictions\Blockchain\Restriction;

class ManagerSpec extends ObjectBehavior
{
    /** @var Client */
    private $client;
    
    /** @var RestrictionsManager */
    private $restrictionsManager;
    
    /** @var Logger */
    private $logger;

    public function let(
        Client $client,
        RestrictionsManager $restrictionsManager,
        Logger $logger
    ) {
        $this->client = $client;
        $this->restrictionsManager = $restrictionsManager;
        $this->logger = $logger;
        
        $this->beConstructedWith(
            $client,
            $restrictionsManager,
            $logger
        );
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    public function it_should_populate_from_ofac_list()
    {
        $this->restrictionsManager->deleteByReason('ofac')
            ->shouldBeCalled()
            ->willReturn(true);

        $this->client->getAll()
            ->shouldBeCalled()
            ->willReturn([
                [ 'network' => 'ETH', 'address' => '0x00' ],
                [ 'network' => 'ETH', 'address' => '0x01' ],
            ]);
        
        $this->restrictionsManager->add(
            (new Restriction())
                ->setAddress('0x00')
                ->setReason('ofac')
                ->setNetwork('ETH')
        )->shouldBeCalled();

        $this->restrictionsManager->add(
            (new Restriction())
                ->setAddress('0x01')
                ->setReason('ofac')
                ->setNetwork('ETH')
        )->shouldBeCalled();

        $this->populate();
    }

    public function it_should_log_warning_if_unsupported_network_is_not_added()
    {
        $this->restrictionsManager->deleteByReason('ofac')
            ->shouldBeCalled()
            ->willReturn(true);

        $this->client->getAll()
            ->shouldBeCalled()
            ->willReturn([
                [ 'network' => 'LTC', 'address' => 'L000' ],
                [ 'network' => 'ETH', 'address' => '0x01' ],
            ]);

        $this->logger->warn("Unsupported network: LTC for address: L000")
            ->shouldBeCalled();

        $this->restrictionsManager->add(
            (new Restriction())
                ->setAddress('0x01')
                ->setReason('ofac')
                ->setNetwork('ETH')
        )->shouldBeCalled();

        $this->populate();
    }
}
