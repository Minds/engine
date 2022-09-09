<?php

declare(strict_types=1);

namespace Tests\Support\Step\Api;

use Behat\Gherkin\Node\PyStringNode;
use Codeception\Attribute\Given;
use Codeception\Attribute\Group;
use Codeception\Attribute\Then;
use Codeception\Attribute\When;
use Codeception\Util\Fixtures;
use Tests\Support\ApiTester;

/**
 * Contains common steps that can be reused in different features
 */
#[Group(
    "registration",
    "login",
    "newsfeed",
    "supermind",
)]
class CommonSteps extends ApiTester
{
    #[Given("I want to create an activity with the following details :activityDetails")]
    public function something(PyStringNode $activityDetails)
    {
        $activityDetails = json_decode($activityDetails->getRaw(), true);

        if (isset($activityDetails['supermind_request'])) {
            $activityDetails['supermind_request'] = $this->populateActivitySupermindRequestDetails($activityDetails['supermind_request']);
        }

        Fixtures::add('activity_details', $activityDetails);
    }

    #[When('I call the ":uri" endpoint with params :queryParams')]
    public function whenICallEndpoint(string $uri, PyStringNode $queryParams)
    {
        $params = $this->generateUrlQueryParams(json_decode($queryParams->getRaw(), true));
        $this->sendGetAsJson($uri . "?$params");
    }

    #[When('I ":requestMethod" stored data ":dataToRetrieve" to the ":uri" endpoint')]
    public function whenICallEndpointWith(string $requestMethod, string $dataToRetrieve, string $uri)
    {
        $this->sendAsJson($requestMethod, $uri, Fixtures::get($dataToRetrieve));
    }

    #[Then('I get a :targetHttpStatusCode response containing :targetResponseContent')]
    public function thenISuccessfullyLogin(string $targetHttpStatusCode, PyStringNode $targetResponseContent)
    {
        $this->seeResponseCodeIs((int) $targetHttpStatusCode);
        $this->seeResponseContainsJson(json_decode($targetResponseContent->getRaw(), true));
    }
}
