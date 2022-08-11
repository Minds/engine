@newsfeed
Feature: Top NewsFeed

  Background: I am a logged in user
    Given my login details are
      | username                          | password   |
      | minds_engine_integration_tests    | Pa$$w0rd!@ |
    When I call the login endpoint
    Then I get a 200 response containing
      """json
      {
        "status": "success"
      }
      """

  Scenario: Successfully Retrieve Top Feed
    When I call the "v3/newsfeed/feed/unseen-top" endpoint with params
      """json
      [
        {
          "key": "limit",
          "value": 150
        }
      ]
      """
    Then I get a 200 response containing
      """json
      {
        "status": "success"
      }
      """
