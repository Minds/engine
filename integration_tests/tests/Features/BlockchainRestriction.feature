@blockchainRestrictions
Feature: BlockchainRestrictions

  Scenario: Successfully allow a non-restricted address on check
    Given my login details are
      | username                          | password   |
      | minds_engine_integration_tests    | Pa$$w0rd!@ |
    When I call the login endpoint
    And I call the check endpoint
    Then I get a 200 response containing
      """json
      {
        "status": "success"
      }
      """

  Scenario: Ban Minds account with restricted wallet
    Given I register to Minds with
      """json
      {
        "username": "",
        "password": "Pa$$w0rd",
        "email": "fausto@minds.com",
        "captcha": "{\"clientText\": \"captcha_bypass\"}",
        "parentId": ""
      }
      """
    When I call the check endpoint with "0x890099E17BfD3f49d8E5fe302B564997440d9079"
    Then I get a 403 response containing
      """json
      {
        "status": "error",
        "message": "Your address is restricted",
        "errorId": "Minds::Core::Rewards::Restrictions::Blockchain::Exceptions::RestrictedException"
      }
      """
