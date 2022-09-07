<?php

declare(strict_types=1);

namespace Tests\Support\Step\Api;

use Behat\Gherkin\Node\PyStringNode;
use Codeception\Attribute\Group;
use Codeception\Attribute\Given;
use Codeception\Attribute\Then;
use Codeception\Attribute\When;
use Codeception\Util\Fixtures;
use Tests\Support\ApiTester;
use Ramsey\Uuid\Uuid;

/**
 * Contains common steps that can be reused in different features
 */
#[Group(
    "registration",
    "login",
    "newsfeed",
    "blockchainRestrictions"
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
}
