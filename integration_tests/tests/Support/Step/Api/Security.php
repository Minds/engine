<?php

declare(strict_types=1);

namespace Tests\Support\Step\Api;

use Codeception\Attribute\Given;
use Codeception\Attribute\Group;
use Codeception\Attribute\Then;
use Tests\Support\ApiTester;

#[Group(
    'supermind'
)]
class Security extends ApiTester
{
    #[Given('I block user ":username"')]
    public function givenIBlockUser(string $username)
    {
        $this->sendPutAsJson('api/v1/block/$username');
        $this->seeResponseCodeIs(200);
    }

    #[Then('I unblock user ":username"')]
    public function thenIUnblockUser(string $username)
    {
        $this->sendDeleteAsJson('api/v1/block/$username');
        $this->seeResponseCodeIs(200);
    }
}
