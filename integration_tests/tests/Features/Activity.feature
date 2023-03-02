@activity
Feature: Activity

  Scenario: Successfully Vote up an activity
    Given I login
    And I create an activity with the following details
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
        "entity_guid_update": true
      }
      """
    When I vote "up" the last created activity
    Then I get a 200 response containing
      """json
      {
        "status": "success"
      }
      """

  Scenario: Successfully Vote up an activity with client meta details
    Given I login
    And I create an activity with the following details
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
        "entity_guid_update": true
      }
      """
    When I vote "up" the last created activity with the following client meta details
      """json
      {
        "source": null
      }
      """
    Then I get a 200 response containing
      """json
      {
        "status": "success"
      }
      """
