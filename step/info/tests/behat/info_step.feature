@tool @tool_lifecycle
Feature: Information-only email step runs without teacher interaction

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category | startdate      |
      | Course A | CA        | 0        | ##4 days ago## |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | CA     | editingteacher |
    And I log in as "admin"
    And I am on workflowdrafts page
    And I click on "Create new workflow" "link"
    And I set the following fields to these values:
      | Title                    | Info mail workflow        |
      | Displayed workflow title | Info mail workflow        |
    And I press "Save changes"
    And I select "Start Date Delay Trigger" from the "tool_lifecycle-choose-trigger" singleselect
    And I set the following fields to these values:
      | Instance name   | Start date delay trigger |
      | delay[number]   | 3                        |
      | delay[timeunit] | days                     |
    And I press "Save changes"
    And I select "Email step (information only)" from the "tool_lifecycle-choose-step" singleselect
    And I set the following fields to these values:
      | Instance name               | Info email step             |
      | Subject template            | Info mail for ##courses##   |
      | Content plain text template | Hello ##firstname##         |
      | Content HTML Template       | Hello ##firstname##         |
    And I press "Save changes"
    And I am on workflowdrafts page
    And I press "Activate"
    And I log out

  @javascript
  Scenario: Info step completes without interaction entries
    When I log in as "admin"
    And I run the scheduled task "tool_lifecycle\task\lifecycle_task"
    And I log out
    And I log in as "teacher1"
    And I am on lifecycle view
    Then I should not see "Keep course"
    And I should not see "Course is marked for deletion"
