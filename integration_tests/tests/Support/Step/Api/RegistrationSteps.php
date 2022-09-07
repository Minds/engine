<?php

declare(strict_types=1);

namespace Tests\Support\Step\Api;

use Behat\Gherkin\Node\PyStringNode;
use Codeception\Attribute\Given;
use Codeception\Attribute\Group;
use Codeception\Attribute\When;
use Codeception\Util\Fixtures;
use Ramsey\Uuid\Uuid;
use Tests\Support\ApiTester;

#[Group("registration")]
class RegistrationSteps extends ApiTester
{
    #[Given('I want to register to Minds with the following data :registrationData')]
    public function IWantToRegisterToMindsWithData(PyStringNode $registrationData)
    {
        Fixtures::add("registration-data", json_decode($registrationData->getRaw(), true));
    }

    #[When('I call the registration endpoint')]
    public function ICallTheRegistrationEndpoint()
    {
        $registrationData = Fixtures::get("registration-data");
        $registrationData['username'] = str_replace(
            search: "-",
            replace: "",
            subject: Uuid::uuid4()->toString()
        );

        $this->setCaptchaBypass();
        $this->setCookie("XSRF-TOKEN", "13b900e725e3fe5ea60464d3c8bf7423e2d215ed5c473ccca34118cb0e7c538432b89cecaa98c424246ca789ec464a0516166d492c82f3573b3d2446903f31e1");
        $this->haveHttpHeader("X-XSRF-TOKEN", "13b900e725e3fe5ea60464d3c8bf7423e2d215ed5c473ccca34118cb0e7c538432b89cecaa98c424246ca789ec464a0516166d492c82f3573b3d2446903f31e1");

        $this->sendPostAsJson("v1/register", $registrationData);
    }
}
