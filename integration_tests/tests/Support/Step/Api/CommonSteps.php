<?php

declare(strict_types=1);

namespace Tests\Support\Step\Api;

use Behat\Gherkin\Node\PyStringNode;
use Codeception\Attribute\Given;
use Codeception\Attribute\Group;
use Codeception\Attribute\Then;
use Codeception\Attribute\When;
use Codeception\Util\Fixtures;
use Ramsey\Uuid\Uuid;
use Tests\Support\ApiTester;

/**
 * Contains common steps that can be reused in different features
 */
#[Group(
    "registration",
    "login",
    "newsfeed",
    "supermind",
    "blockchainRestrictions",
    "discovery",
    "payments",
    "activity",
)]
class CommonSteps extends ApiTester
{
    #[Given("I want to create an activity with the following details :activityDetails")]
    public function givenIWantToCreateNewActivityWithDetails(PyStringNode $activityDetails)
    {
        $activityDetails = json_decode($activityDetails->getRaw(), true);

        if (isset($activityDetails['supermind_request'])) {
            $activityDetails['supermind_request'] = $this->populateActivitySupermindRequestDetails($activityDetails['supermind_request']);
        }

        Fixtures::add('activity_details', $activityDetails);
    }

    /**
     * Store data into global state which can be reused in subsequent steps.
     * @param string $dataKey - represents the name of the data to be stored.
     * @param PyStringNode $data - data to store
     * @return void
     */
    #[Given('I have ":dataKey" data :data')]
    public function givenIHaveData(
        string $dataKey,
        PyStringNode $data
    ): void {
        $data = json_decode($data->getRaw(), true);
        Fixtures::add($dataKey, $data);
    }

    #[When('I call the ":uri" endpoint with params :queryParams')]
    public function whenICallEndpoint(string $uri, PyStringNode $queryParams)
    {
        $params = $this->generateUrlQueryParams(json_decode($queryParams->getRaw(), true));
        $this->sendGetAsJson($uri . "?$params");
    }

    #[When('I call ":requestMethod" ":uri" with params :queryParams')]
    public function whenICallEndpointByMethod(string $requestMethod, string $uri, PyStringNode $params)
    {
        switch (strtolower($requestMethod)) {
            case 'get':
                $params = $this->generateUrlQueryParams(json_decode($params->getRaw(), true));
                $this->sendGetAsJson($uri . "?$params");
                break;
            case 'post':
                $params = json_decode($params->getRaw(), true);
                $this->sendPostAsJson($uri, $params);
                break;
        }
    }

    #[When('I ":requestMethod" stored data ":dataToRetrieve" to the ":uri" endpoint')]
    public function whenICallEndpointWith(string $requestMethod, string $dataToRetrieve, string $uri)
    {
        $this->sendAsJson($requestMethod, $uri, Fixtures::get($dataToRetrieve));
    }

    #[When('I ":requestMethod" stored data ":dataToRetrieve" to the ":uri" endpoint and store the response with key ":response_data_key"')]
    public function whenICallEndpointWithDataAndStore(string $requestMethod, string $dataToRetrieve, string $uri, string $responseDataStorageKey)
    {
        $response = $this->sendAsJson($requestMethod, $uri, Fixtures::get($dataToRetrieve));
        Fixtures::add($responseDataStorageKey, $response);
    }

    #[Then('I get a :targetHttpStatusCode response containing :targetResponseContent')]
    public function thenISuccessfullyLogin(string $targetHttpStatusCode, PyStringNode $targetResponseContent)
    {
        $this->seeResponseCodeIs((int) $targetHttpStatusCode);
        $this->seeResponseContainsJson(json_decode($targetResponseContent->getRaw(), true));
    }

    #[Given('I register to Minds with :registrationData')]
    public function givenIRegisterToMindsWithRegistrationData(PyStringNode $registrationData)
    {
        $registrationData = json_decode($registrationData->getRaw(), true);
        $registrationData['username'] = str_replace(
            search: "-",
            replace: "",
            subject: Uuid::uuid4()->toString()
        );

        Fixtures::add('registration_data', $registrationData);

        $this->setCaptchaBypass();
        $this->setCookie("XSRF-TOKEN", "13b900e725e3fe5ea60464d3c8bf7423e2d215ed5c473ccca34118cb0e7c538432b89cecaa98c424246ca789ec464a0516166d492c82f3573b3d2446903f31e1");
        $this->haveHttpHeader("X-XSRF-TOKEN", "13b900e725e3fe5ea60464d3c8bf7423e2d215ed5c473ccca34118cb0e7c538432b89cecaa98c424246ca789ec464a0516166d492c82f3573b3d2446903f31e1");

        $this->sendPostAsJson("v1/register", $registrationData);
        $this->seeResponseCodeIs(200);
    }

    #[Given('I login')]
    public function givenILogin()
    {
        $this->login();
    }

    #[Given('I am logged out')]
    public function givenIAmLoggedOut()
    {
        // noop
    }
}
