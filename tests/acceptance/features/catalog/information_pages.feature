@information
Feature: Information Pages
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

  Scenario: View Contact Us
    Given I am on "/"
    When I follow "Contact Us"
    Then I should see "Contact Us"

  Scenario: Complete Contact Us form
    Given I am on "/"
    When I follow "Contact Us"
      And I fill in "name" with "Behat User"
      And I fill in "email" with "testing@example.com"
      And I fill in "enquiry" with "This is a test contact message to test that the contact us page is working"
      And I press "Continue"
    Then I should see "Your enquiry has been successfully sent to the Store Owner"

  Scenario: Try to Spam Store Owner
    Given I am on "/"
    When I follow "Contact Us"
      And I fill in "name" with "Behat User"
      And I fill in "email" with "testing@example.com"
      And I fill in "enquiry" with "This is a test contact message to test that the contact us page is working"
      And I press "Continue"
      And I follow "Contact Us"
      And I fill in "name" with "Behat User"
      And I fill in "email" with "testing@example.com"
      And I fill in "enquiry" with "This is a test contact message to test that the contact us page is working"
      And I press "Continue"
    Then I should see "Error: An enquiry has already been sent. Please try again in 15 minutes."