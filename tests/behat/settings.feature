@tool @tool_cohortsync
Feature: Configure cohort synchronization settings
  In order to configure cohort synchronisation
  As an admin
  I need to be able to access and save plugin settings

  Scenario: Admin can update cohort sync user identifier setting
    Given I log in as "admin"
    And I navigate to "Plugins > Admin tools > Cohort synchronization" in site administration
    Then I should see "Cohort configuration"
    And I should see "Cohort member configuration"
    When I set the field "User identifier" to "id"
    And I press "Save changes"
    Then I should see "Changes saved"
    And the field "User identifier" matches value "id"

  Scenario: Admin can update cohort sync CSV delimiter setting
    Given I log in as "admin"
    And I navigate to "Plugins > Admin tools > Cohort synchronization" in site administration
    When I set the field "CSV delimiter" to "semicolon"
    And I press "Save changes"
    Then I should see "Changes saved"
    And the field "CSV delimiter" matches value "semicolon"
