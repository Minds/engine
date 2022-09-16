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
                "payment_type": 1,
                "amount": 0.50
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

  Scenario: Accept Supermind request successfully
    Given I create a Supermind request with the following details
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
    And I login to "receive" Supermind requests
    When I accept the Supermind request for stored data "created_activity" with the following reply
      """json
      {
        "message": "This is a test post for supermind request from integration tests",
        "wire_threshold": null,
        "paywall": null,
        "time_created": null,
        "mature": false,
        "nsfw": [],
        "tags": [
            "test_tag"
        ],
        "access_id": "2",
        "license": "all-rights-reserved",
        "remind_guid": "",
        "post_to_permaweb": false,
        "entity_guid_update": true,
        "supermind_reply_guid": ""
      }
      """
    Then I get a 200 response containing
      """json
      {}
      """

  Scenario: Accept Supermind request failed with validation errors
    Given I create a Supermind request with the following details
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
    And I login to "receive" Supermind requests
    When I accept the Supermind request for stored data "created_activity" with the following reply
      """json
      {
        "message": "This is a test post for supermind request reply from integration tests",
        "wire_threshold": [],
        "paywall": null,
        "time_created": 1663081913,
        "mature": true,
        "nsfw": [],
        "tags": [
            "test_tag"
        ],
        "access_id": "1",
        "license": "all-rights-reserved",
        "remind_guid": "",
        "post_to_permaweb": false,
        "entity_guid_update": true,
        "supermind_reply_guid": ""
      }
      """
    Then I get a 400 response containing
      """json
      {}
      """

  Scenario: Reject Supermind request successfully
    Given I create a Supermind request with the following details
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
    And I login to "receive" Supermind requests
    When I reject the Supermind request for stored data "created_activity"
    Then I get a 200 response containing
      """json
      {}
      """

  Scenario: Get Supermind inbox
    Given I login to "receive" Supermind requests
    When I call the "v3/supermind/inbox" endpoint with params
    """json
    [
        {
          "key": "limit",
          "value": 12
        },
        {
          "key": "offset",
          "value": 0
        }
      ]
    """
    Then I get a 200 response containing
    """json
    {}
    """

  Scenario: Get Supermind outbox
    Given I login to "create" Supermind requests
    When I call the "v3/supermind/outbox" endpoint with params
    """json
    [
        {
          "key": "limit",
          "value": 12
        },
        {
          "key": "offset",
          "value": 0
        }
      ]
    """
    Then I get a 200 response containing
    """json
    {}
    """
