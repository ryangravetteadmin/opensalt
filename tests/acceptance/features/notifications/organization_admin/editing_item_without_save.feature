Feature: Change Notifications
When an item is being edited, a notification is shown and buttons that allow editing are disabled.

  @0108-0808 @change-notification @ui @organization-admin @duplicate
  Scenario: 0108-0808 Notification for item being edited when logged in as an Organization Admin
    Given I am logged in as an "Admin"
    And I log a new "Editor"
    When I create a framework
    And I add a Item
    And I select the item
    Then I edit the fields in a item without saving the changes
      | Human coding scheme   | QA Test Item         |
      | List enum in source   | 1                    |
      | Abbreviated statement | New Abb statement    |
      | Concept keywords      | reading              |
      | Concept keywords uri  | http://reading.com   |
      | Licence uri           | http://somewhere.com |
    Then I see a notification of editing "Item"
    And I see the Item buttons disabled
    And I delete the framework
