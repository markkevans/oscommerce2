Feature: Search
  In order to have the information I need
  As a website user
  I need to be able to read information policies

  Scenario: View Shipping and Returns
    Given I am on "/"
    When I follow "Shipping & Returns"
    Then I should see "Put here your Shipping & Returns information."

  Scenario: View Privacy Notice
    Given I am on "/"
    When I follow "Privacy Notice"
    Then I should see "Put here your Privacy Notice information."

  Scenario: View Conditions of Use
    Given I am on "/"
    When I follow "Conditions of Use"
    Then I should see "Put here your Conditions of Use information."
