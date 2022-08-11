@login
Feature: Authentication

  Scenario: Successful authentication
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

  Scenario: Unsuccessful authentication
    Given my login details are
      | username | password |
      | minds    |          |
    When I call the login endpoint
    Then I get a 401 response containing
      """json
      {
        "status": "failed"
      }
      """

    Given my login details are
      | username | password |
      |          |          |
    When I call the login endpoint
    Then I get a 404 response containing
      """json
      {
        "status": "failed"
      }
      """
