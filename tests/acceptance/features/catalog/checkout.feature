Feature: Account
  In order to purchase products
  As a website user
  I need to be able to complete checkout

  Scenario: View Product Page
    Given I am on "/"
    When I follow "Courage Under Fire"
    And I press "tdb4"
    Then I should see "What's In My Cart?"
	And I should see "Sub-Total: $29.99"