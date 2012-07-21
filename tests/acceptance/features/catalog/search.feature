Feature: Search
  In order find what I am looking for
  As a website user
  I need to be able to search for products

  Scenario: Searching for a product that does NOT exist
    Given I am on "/"
    When I fill in "keywords" with "Test Product 1"
    And I press "Quick Find"
    Then I should see "There is no product that matches the search criteria"

  Scenario: Searching for a product that does exist
    Given I am on "/"
    When I fill in "keywords" with "Speed"
    And I press "Quick Find"
    Then I should see "Displaying 1 to 3 (of 3 products)"

  Scenario: Advance Search for product by category
    Given I am on "/advanced_search.php"
    When I fill in "keywords" with "Speed"
    And I select "3" from "categories_id"
    And I press "tdb4"
    Then I should see "Displaying 1 to 2 (of 2 products)"

  Scenario: Advance Search for product by manufacturer
    Given I am on "/advanced_search.php"
    When I fill in "keywords" with "Speed"
    And I select "4" from "manufacturers_id"
    And I press "tdb4"
    Then I should see "Displaying 1 to 2 (of 2 products)"

  Scenario: Advance Search for product by manufacturer and category
    Given I am on "/advanced_search.php"
    When I fill in "keywords" with "Speed"
    And I select "1" from "categories_id"
    And I select "4" from "manufacturers_id"
    And I press "tdb4"
    Then I should see "There is no product that matches the search criteria"