<?php

declare(strict_types=1);

namespace Tests\Support\Step\Api;

use Behat\Gherkin\Node\PyStringNode;
use Codeception\Attribute\Group;
use Codeception\Attribute\When;
use Codeception\Util\Fixtures;
use Tests\Support\ApiTester;

/**
 * Contains steps relating to the boost feature.
 */
#[Group(
    "boost"
)]
class BoostSteps extends ApiTester
{
    #[When("I boost the post with the response storage key :storage_key for :currency")]
    public function whenIBoostThePostWithStorageKey(string $dataStorageKey, string $currency)
    {
        $activityData = Fixtures::get($dataStorageKey);
        $activityGuid = $activityData['guid'];
        $ownerGuid = $activityData['ownerObj']['guid'];

        $this->sendAsJson('POST', "v2/boost/activity/$activityGuid/$ownerGuid", [
            "guid" => "1437424090381029390",
            "bidType" => $currency,
            "impressions" => 1000,
            "checksum" => "6515b06f39719a3faa3f6d8904e306c9",
            "paymentMethod" => [
                "method" => $currency,
                "payment_method_id" => $_ENV['SUPERMIND_STRIPE_PAYMENT_METHOD_ID']
            ]
        ]);
    }
}
