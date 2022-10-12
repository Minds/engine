@discovery
Feature: Discovery

  Scenario: Successfully retrieve Discovery Supermind Feed data
    When I call the "v3/newsfeed/superminds" endpoint with params
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
      {}
      """
