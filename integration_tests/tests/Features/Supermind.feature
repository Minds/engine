@supermind
Feature: Supermind

  Scenario: Successful Supermind request creation with Stripe payment
    Given I want to create an activity with the following details
      """json
      {
        "message": "This is a test post for supermind request from integration tests",
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
        "entity_guid_update": true,
        "supermind_request": {
            "receiver_guid": "",
            "payment_options": {
                "payment_type": 0,
                "payment_method_id": "",
                "amount": 10.00
            },
            "reply_type": 0,
            "twitter_required": false,
            "terms_agreed": true
        }
      }
      """
    And I login to "create" Supermind requests
    When I "PUT" stored data "activity_details" to the "v3/newsfeed/activity" endpoint
    Then I get a 200 response containing
      """json
      {
        "type": "activity"
      }
      """

  Scenario: Successful Supermind request creation with off-chain token payment
    Given I want to create an activity with the following details
      """json
      {
        "message": "This is a test post for supermind request from integration tests",
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
        "entity_guid_update": true,
        "supermind_request": {
            "receiver_guid": "",
            "payment_options": {
                "payment_type": 1,
                "amount": 10.00
            },
            "reply_type": 0,
            "twitter_required": false,
            "terms_agreed": true
        }
      }
      """
    And I login to "create" Supermind requests
    When I "PUT" stored data "activity_details" to the "v3/newsfeed/activity" endpoint
    Then I get a 200 response containing
      """json
      {
        "type": "activity"
      }
      """

  Scenario: Supermind request creation with invalid details
    Given I want to create an activity with the following details
      """json
      {
        "message": "This is a test post for supermind request from integration tests",
        "wire_threshold": [],
        "paywall": null,
        "time_created": 213123121,
        "mature": true,
        "nsfw": null,
        "tags": [
            "test_tag"
        ],
        "access_id": "1",
        "license": "all-rights-reserved",
        "post_to_permaweb": false,
        "entity_guid_update": true,
        "supermind_request": {
            "receiver_guid": "",
            "payment_options": {
                "payment_type": -1,
                "amount": 10.00
            },
            "reply_type": -1,
            "terms_agreed": false
        }
      }
      """
    And I login to "create" Supermind requests
    When I "PUT" stored data "activity_details" to the "v3/newsfeed/activity" endpoint
    Then I get a 400 response containing
      """json
      {}
      """

#  Scenario:
