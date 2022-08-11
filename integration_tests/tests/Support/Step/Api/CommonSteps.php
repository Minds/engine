<?php

declare(strict_types=1);

namespace Tests\Support\Step\Api;

use Behat\Gherkin\Node\PyStringNode;
use Codeception\Attribute\Group;
use Codeception\Attribute\Then;
use Codeception\Attribute\When;
use Tests\Support\ApiTester;

/**
 * Contains common steps that can be reused in different features
 */
#[Group(
    "registration",
    "login",
    "newsfeed"
)]
class CommonSteps extends ApiTester
{
    #[When('I call the ":uri" endpoint with params :queryParams')]
    public function whenICallEndpoint(string $uri, PyStringNode $queryParams)
    {
        $params = $this->generateUrlQueryParams(json_decode($queryParams->getRaw(), true));
        $this->sendGetAsJson($uri . "?$params");
    }

    #[Then('I get a :targetHttpStatusCode response containing :targetResponseContent')]
    public function thenISuccessfullyLogin(string $targetHttpStatusCode, PyStringNode $targetResponseContent)
    {
        $this->seeResponseCodeIs((int) $targetHttpStatusCode);
        $this->seeResponseContainsJson(json_decode($targetResponseContent->getRaw(), true));
    }
}
