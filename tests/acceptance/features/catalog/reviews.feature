@reviews
Feature: Reviews
  In order to help decide on my purchase
  As a website user
  I need to be able to read and submit reviews

  Scenario: View List of Reviews
    Given I am on "/"
    When I follow "Reviews"
    Then I should see "Displaying 1 to 1 (of 1 reviews)"

  Scenario: View Review
    Given I am on "/"
    When I follow "There's Something About Mary"
      And I follow "Reviews (1)"
    Then I should see "This has to be one of the funniest movies released for 1999!"

  Scenario: Write Review
    Given The following customers exist
    | Email  | Gender | Firstname | Lastname | DOB | Phone | Password | Fax | Newsletter | Address |
    | testing2@example.com | m | Behat | User | 1970-05-21 | 01204 2324224 | $P$DTl3oE1hOday7GR40bSpLEjGimHVJI1 | 01331 5335153 | Y | 1 |
      And I am on "/"
      And I am logged in as "testing2@example.com" with password "password"
    When I follow "There's Something About Mary"
      And I follow "Reviews (1)"
      And I follow "Write Review"
      And I fill in "review" with "This is a test product review to about this film which is really funny"
      And I select "5" from "rating"
      And I press "Continue"
    Then I should see "Thank you for your review. It has been submitted to the store owner for approval and should appear shortly."