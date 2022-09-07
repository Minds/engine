@registration
Feature: User Registration

  Scenario: Successful Registration
    Given I want to register to Minds with the following data
      """json
      {
        "username": "",
        "password": "Pa$$w0rd",
        "email": "fausto@minds.com",
        "captcha": "{\"clientText\": \"captcha_bypass\"}",
        "parentId": ""
      }
      """
    When I call the registration endpoint
    Then I get a 200 response containing
      """json
      {
        "status": "success"
      }
      """

  Scenario: Unsuccessful Registration
    Given I want to register to Minds with the following data
      """json
      {
        "username": "",
        "password": "",
        "email": "",
        "captcha": "{\"clientText\": \"captcha_bypass\"}",
        "parentId": ""
      }
      """
    When I call the registration endpoint
    Then I get a 200 response containing
      """json
      {
        "status": "error"
      }
      """
