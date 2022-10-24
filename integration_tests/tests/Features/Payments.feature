@payments
Feature: Payments

  Scenario: Successfully create an account
    Given I register to Minds with
      """json
      {
        "username": "",
        "password": "Pa$$w0rd!@",
        "email": "noreply@minds.com",
        "captcha": "{\"clientText\": \"captcha_bypass\"}",
        "parentId": ""
      }
      """
    When I call "POST" "v3/payments/stripe/connect/account" with params
      """php
        []
      """
    Then I get a 200 response containing
      """json
        {}
      """

  Scenario: Not be able to create an account if I already have one
    Given I login to "receive" Supermind requests
    When I call "POST" "v3/payments/stripe/connect/account" with params
      """json
        {}
      """
    Then I get a 400 response containing
      """json
        {}
      """

  Scenario: Be able to get an existing account
    Given I login to "receive" Supermind requests
    When I call "GET" "v3/payments/stripe/connect/account" with params
      """json
        {}
      """
    Then I get a 200 response containing
      """json
        {}
      """
  
  Scenario: Not be able to get a non-existant account
    Given I register to Minds with
      """json
      {
        "username": "",
        "password": "Pa$$w0rd!@",
        "email": "noreply@minds.com",
        "captcha": "{\"clientText\": \"captcha_bypass\"}",
        "parentId": ""
      }
      """
    When I call "GET" "v3/payments/stripe/connect/account" with params
      """json
        {}
      """
    Then I get a 404 response containing
      """json
        {}
      """
    
  Scenario: Get payments for logged in user
    Given I login to "send" Supermind requests
    When I call "GET" "v3/payments" with params
      """json
        {}
      """
    Then I get a 200 response containing
      """json
        {}
      """
