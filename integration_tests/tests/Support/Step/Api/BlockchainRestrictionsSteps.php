<?php

declare(strict_types=1);

namespace Tests\Support\Step\Api;

use Codeception\Attribute\Group;
use Codeception\Attribute\When;
use Tests\Support\ApiTester;
use Behat\Gherkin\Node\PyStringNode;

/**
 * Blockchain restrictions steps
 */
#[Group("blockchainRestrictions")]
class BlockchainRestrictionsSteps extends ApiTester
{
    #[When('I call the check endpoint with :wallet_address')]
    public function ICallTheCheckEndpointWithWalletAddress(string $walletAddress)
    {
        $this->sendGetAsJson("v3/rewards/check/${walletAddress}");
    }
}
