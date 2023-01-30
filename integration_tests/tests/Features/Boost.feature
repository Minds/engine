@boost
Feature: Boost

  Scenario: Successful boost an activity with Stripe payment
    Given I want to create an activity with the following details
      """json
      {
        "message": "This is a test post for boosts from integration tests",
        "wire_threshold": null,
        "paywall": null,
        "time_created": null,
        "mature": false,
        "nsfw": null,
        "tags": [
            "test_tag"
        ],
        "access_id": "2",
        "license": "all-rights-reserved",
        "post_to_permaweb": false,
        "entity_guid_update": true
      }
      """
    And I login to "create" Supermind requests
    When I "PUT" stored data "activity_details" to the "v3/newsfeed/activity" endpoint and store the response with key "activity_creation_response"
    And I boost the post with the response storage key "activity_creation_response" for "cash"
    Then I get a 200 response containing
      """json
      {
      }
      """

  Scenario: Successful boost an activity with offchain tokens
    Given I want to create an activity with the following details
      """json
      {
        "message": "This is a test post for boosts from integration tests",
        "wire_threshold": null,
        "paywall": null,
        "time_created": null,
        "mature": false,
        "nsfw": null,
        "tags": [
            "test_tag"
        ],
        "access_id": "2",
        "license": "all-rights-reserved",
        "post_to_permaweb": false,
        "entity_guid_update": true
      }
      """
    And I login to "create" Supermind requests
    When I "PUT" stored data "activity_details" to the "v3/newsfeed/activity" endpoint and store the response with key "activity_creation_response"
    And I boost the post with the response storage key "activity_creation_response" for "tokens"
    Then I get a 200 response containing
      """json
      {
      }
      """
