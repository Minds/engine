<?php

declare(strict_types=1);

namespace Tests\Support\Step\Api;

use Codeception\Attribute\Given;
use Codeception\Attribute\Group;
use Tests\Support\ApiTester;

#[Group('supermind')]
class SupermindSteps extends ApiTester
{
    #[Given('I login to ":action" Supermind requests')]
    public function givenILoginToInteractWithSupermindRequests(string $action)
    {
        $this->loginWithSupermindRequesterAccount();
        $this->seeResponseCodeIs(200);
    }
}
