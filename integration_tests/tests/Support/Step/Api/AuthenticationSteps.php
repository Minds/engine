<?php

declare(strict_types=1);

namespace Tests\Support\Step\Api;

use Behat\Gherkin\Node\TableNode;
use Codeception\Attribute\Given;
use Codeception\Attribute\Group;
use Codeception\Attribute\When;
use Codeception\Util\Fixtures;
use Tests\Support\ApiTester;

#[Group("login", "newsfeed")]
class AuthenticationSteps extends ApiTester
{
    #[Given("my login details are")]
    public function myLoginDetailsAre(TableNode $loginDetails)
    {
        Fixtures::add("loginDetails", $loginDetails->getRow(1));
    }

    #[When("I call the login endpoint")]
    public function ICallTheLoginEndpoint()
    {
        [$username, $password] = (array) Fixtures::get("loginDetails") ?? ["", ""];

        $this->setCookie("XSRF-TOKEN", "13b900e725e3fe5ea60464d3c8bf7423e2d215ed5c473ccca34118cb0e7c538432b89cecaa98c424246ca789ec464a0516166d492c82f3573b3d2446903f31e1");
        $this->haveHttpHeader("X-XSRF-TOKEN", "13b900e725e3fe5ea60464d3c8bf7423e2d215ed5c473ccca34118cb0e7c538432b89cecaa98c424246ca789ec464a0516166d492c82f3573b3d2446903f31e1");

        $this->sendPostAsJson("v1/authenticate", [
            "password" => $password,
            "username" => $username
        ]);
    }
}
