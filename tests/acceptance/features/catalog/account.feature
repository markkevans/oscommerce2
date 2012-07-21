Feature: Account
  In order to manage my orders
  As a website user
  I need to be able to view my account information

  Scenario: View Create Account page
    Given I am on "/"
    When I follow "create an account"
    Then I should see "My Account Information"

  Scenario: Create an Account
    Given I am on "/"
    When I follow "create an account"
    And I select "m" from "gender"
    And I fill in "firstname" with "Behat"
    And I fill in "lastname" with "Behat"
    And I fill in "dob" with "05/21/1970"
    And I fill in "email_address" with "testing@osservices.co.uk"
    And I fill in "street_address" with "123 Fake Street"
    And I fill in "postcode" with "BH1 5RT"
    And I fill in "city" with "Fake City"
    And I fill in "state" with "Dorset"
    And I select "222" from "country"
    And I fill in "telephone" with "01222 435353"
    And I fill in "password" with "password"
    And I fill in "confirmation" with "password"
    And I press "tdb4"
    Then I should see "Your Account Has Been Created!"

  Scenario: Login to Account
    Given I am on "/"
    When I follow "log yourself in"
    And I fill in "email_address" with "testing@osservices.co.uk"
    And I fill in "password" with "password"
    And I press "tdb5"
    Then I should see "Welcome back Behat!"