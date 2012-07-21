Feature: Account
  In order to purchase products
  As a website user
  I need to be able to complete checkout

  Scenario: Add to Cart
    Given I am on "/"
    When I follow "Courage Under Fire"
      And I press "Add to Cart"
    Then I should see "What's In My Cart?"
     And I should see "Sub-Total: $29.99"

  Scenario: Add Complex Product to Cart
    Given I am on "/"
    When I follow "Hardware"
      And I follow "Graphics Cards"
      And I follow "Matrox G200 MMS"
      And I select "3" from "id[4]"
      And I select "6" from "id[3]"
      And I press "Add to Cart"
    Then I should see "What's In My Cart?"
     And I should see "Sub-Total: $469.99"

  Scenario: Remove Single Product from Cart
    Given I am on "/"
      And I have "Courage Under Fire" in my cart
    When I follow "Cart Contents"
      And I follow "remove"
    Then I should see "What's In My Cart?"
     And I should see "Your Shopping Cart is empty!"

 Scenario: Add Multiple Products to My Cart
    Given I am on "/"
      And I have "Courage Under Fire" in my cart
      And I have "Speed" in my cart
    When I follow "Cart Contents"
    Then I should see "What's In My Cart?"
     And I should see "Sub-Total: $69.98"