@referrer
Feature: Referrer

  Scenario: Set a referrer cookie logged out
    Given I am logged out
    When I call the "v1/minds/config" endpoint with params
    """json
    [
      {
        "key": "referrer",
        "value": "testUser"
      }
    ]
    """
    Then I should see a referrer cookie with the value "testUser" 

  Scenario: Set a referrer cookie logged in
    Given I login
    When I call the "v1/minds/config" endpoint with params
    """json
    [
      {
        "key": "referrer",
        "value": "testUser2"
      }
    ]
    """
    Then I should see a referrer cookie with the value "testUser2"


  Scenario: Override existing referrer cookie
    Given I am logged out
    When I call the "v1/minds/config" endpoint with params
    """json
    [
      {
        "key": "referrer",
        "value": "testUser3"
      }
    ]
    """
    And I call the "v1/minds/config" endpoint with params
    """json
    [
      {
        "key": "referrer",
        "value": "testUser4"
      }
    ]
    """
    Then I should see a referrer cookie with the value "testUser4" 
