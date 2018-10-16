@mod @mod_realtimequiz
Feature: The teacher waits for a sufficient number of students
  Background:
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "users" exist:
      | username | firstname | lastname | email               |
      | teacher1 | Teacher   | 1        | teacher1@moodle.com |
      | student1 | Student   | 1        | student1@moodle.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Realtime quiz" to section "1" and I fill the form with:
      | Realtime quiz         | Test realtime quiz                |
      | Introduction          | Test the realtime quiz is working |
      | Default question time | 25                                |
    And I follow "Test realtime quiz"
    And I press "Add question"
    And I set the following fields to these values:
      | Question text | Which UK city is known as the Steel City? |
      | answertext[1] | Sheffield                                 |
      | answertext[2] | Manchester                                |
      | answertext[3] | London                                    | 
    And I press "Save question"

  @javascript
  Scenario: The teacher waits for at least one student before sending the first question
    When I follow "View quiz"
    And I set the field "sessionname" to "Test session"
    And I press "Start quiz"
    And I should see "0 students connected"
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test realtime quiz"
    And I press "Join"
    And I log out
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Test realtime quiz"
    And I press "Reconnect to quiz"
    Then I should see "1 student connected"
