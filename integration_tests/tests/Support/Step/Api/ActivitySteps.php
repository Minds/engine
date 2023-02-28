<?php

declare(strict_types=1);

namespace Tests\Support\Step\Api;

use Behat\Gherkin\Node\PyStringNode;
use Codeception\Attribute\Given;
use Codeception\Attribute\Group;
use Codeception\Attribute\When;
use Tests\Support\ApiTester;

#[Group(
    "activity",
)]
class ActivitySteps extends ApiTester
{
    #[Given('I create an activity with the following details :activityDetails')]
    public function givenICreateAnActivityWithDetails(PyStringNode $activityDetails)
    {
        $activityDetails = json_decode($activityDetails->getRaw(), true);

        $this->createActivityWithDetails($activityDetails, true);
    }

    #[When('I vote :direction the last created activity')]
    public function whenIVoteLastCreatedActivity(string $direction)
    {
        $this->voteLastCreatedActivity($direction);
    }

    #[When('I vote :direction the last created activity with the following client meta details :clientMetaDetails')]
    public function whenIVoteLastCreatedActivityWithClientMetaDetails(string $direction, PyStringNode $clientMetaDetails)
    {
        $clientMetaDetails = json_decode($clientMetaDetails->getRaw(), true);
        
        $this->voteLastCreatedActivity($direction, $clientMetaDetails);
    }
}
