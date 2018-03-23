
Feature: Create new account password should be 8 characters long

    @public-user @user @add-user
    Scenario: Must match passwords
        Given "create_account" is enabled
        And I am on the homepage
        And I follow "Sign up"

        Then I should see "Create new account"
        Then I fill in the "Email Address" with "user@opensalt.com"
        Then I fill in the "Password" with "Password123!"
        Then I fill in the "Confirm Password" with "password"
        Then I click "Submit"
        Then I should see "Passwords do not match"

    @public-user @user @add-user
    Scenario: Must be a valid password
        Given "create_account" is enabled
        And I am on the homepage
        And I follow "Sign up"

        Then I should see "Create new account"
        Then I fill in the "Email Address" with "user@opensalt.com"
        Then I fill in the "Password" with "password"
        Then I fill in the "Confirm Password" with "password"
        Then I click "Submit"
        Then I should see "Password does not match required criteria"

    @public-user @user @add-user
    Scenario: Password must be 8 characters long
        Given "create_account" is enabled
        And I am on the homepage
        And I follow "Sign up"

        Then I should see "Create new account"
        Then I fill in the "Email Address" with "user@opensalt.com"
        Then I fill in the "Password" with "pass@1"
        Then I fill in the "Confirm Password" with "pass@1"
        Then I click "Submit"
        Then I should see "Sorry, Passwords must be at least 8 characters long." 
