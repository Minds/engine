<?php

declare(strict_types=1);

namespace Tests\Support\Step\Api;

use Codeception\Attribute\Group;
use Codeception\Attribute\Then;
use Tests\Support\ApiTester;

#[Group("referrer")]
class ReferrerSteps extends ApiTester
{
    #[Then('I should see a referrer cookie with the value :value')]
    public function IShouldSeeAReferrerCookieWithTheValue(string $value): void
    {
        $this->checkCookieValue('referrer', $value);
    }
}
