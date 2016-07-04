<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Tests for tool_cohortsync.
 *
 * @package   tool_cohortsync
 * @copyright 2016 Universite de Montreal
 * @author    Issam Taboubi <issam.taboubi@umontreal.ca>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
use tool_cohortsync\cohortsync;


defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/admin/tool/cohortsync/classes/cohortsync.php');

/**
 * Tests for tool_cohortsync.
 *
 * @package   tool_cohortsync
 * @copyright 2016 Universite de Montreal
 * @author    Issam Taboubi <issam.taboubi@umontreal.ca>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_cohortsync_testcase extends advanced_testcase {

    /**
     * Loads the database with test data.
     */
    public static function setUpBeforeClass() {

        set_config('useridentifier', 'username', 'tool_cohortsync');
        set_config('csvdelimiter', 'comma', 'tool_cohortsync');
        set_config('encoding', 'UTF-8', 'tool_cohortsync');
        set_config('createcohort', 1, 'tool_cohortsync');
    }

    /**
     * Set configuration value in database.
     * Deactivate display of mtrace.
     */
    public function setUp() {
        $this->resetAfterTest();
        $this->setAdminUser();

    }

    /**
     * Tests cohort synchronisation.
     */
    public function test_cohortsync() {
        global $CFG, $DB;
        $csvfilename = $CFG->dirroot.'/admin/tool/cohortsync/tests/fixtures/cohorts_username.csv';
        set_config('filepathsource', $csvfilename, 'tool_cohortsync');

        $cat1 = $this->getDataGenerator()->create_category(array('name' => 'CAT1'));
        $cat2 = $this->getDataGenerator()->create_category(array('name' => 'CAT2'));

        $user1 = $this->getDataGenerator()->create_user(array(
            'firstname' => 'User1',
            'lastname' => 'User1',
            'username' => 'user1',
            'email' => 'nomail+user1@test.com'));

        $user2 = $this->getDataGenerator()->create_user(array(
            'firstname' => 'User2',
            'lastname' => 'User2',
            'username' => 'user2',
            'email' => 'nomail+user2@test.com'));

        $user3 = $this->getDataGenerator()->create_user(array(
            'firstname' => 'User3',
            'lastname' => 'User3',
            'username' => 'user3',
            'email' => 'nomail+user3@test.com'));
        $cohortsync = new cohortsync();
        $cohortsync->update_cohorts();

        $this->assertEmpty($cohortsync->get_errors());

        $cohort1 = $DB->get_record('cohort', array('idnumber' => 'cohortid1'));
        $cohort2 = $DB->get_record('cohort', array('idnumber' => 'cohortid2'));
        $this->assertEquals('cohortid1', $cohort1->idnumber);
        $this->assertEquals('cohortid2', $cohort2->idnumber);

        $this->assertTrue(cohort_is_member($cohort1->id, $user1->id));
        $this->assertTrue(cohort_is_member($cohort1->id, $user2->id));
        $this->assertTrue(cohort_is_member($cohort1->id, $user3->id));

        $this->assertTrue(cohort_is_member($cohort2->id, $user1->id));

        $contextcat1 = context_coursecat::instance($cat1->id);
        $contextcat2 = context_coursecat::instance($cat2->id);

        $this->assertEquals($contextcat1->id, $cohort1->contextid);
        $this->assertEquals($contextcat2->id, $cohort2->contextid);
    }

    /**
     * Tests cohort synchronisation with user idnumber specified.
     */
    public function test_cohortsync_withuseridnumber() {
        global $CFG, $DB;
        $csvfilename = $CFG->dirroot.'/admin/tool/cohortsync/tests/fixtures/cohorts_withuseridnumber.csv';
        set_config('filepathsource', $csvfilename, 'tool_cohortsync');
        set_config('useridentifier', 'user_idnumber', 'tool_cohortsync');

        $cat1 = $this->getDataGenerator()->create_category(array('name' => 'CAT1'));
        $cat2 = $this->getDataGenerator()->create_category(array('name' => 'CAT2'));

        $user1 = $this->getDataGenerator()->create_user(array(
            'firstname' => 'User1',
            'lastname' => 'User1',
            'username' => 'user1',
            'idnumber' => 'user1idnumber',
            'email' => 'nomail+user1@test.com'));

        $user2 = $this->getDataGenerator()->create_user(array(
            'firstname' => 'User2',
            'lastname' => 'User2',
            'username' => 'user2',
            'idnumber' => 'user2idnumber',
            'email' => 'nomail+user2@test.com'));

        $user3 = $this->getDataGenerator()->create_user(array(
            'firstname' => 'User3',
            'lastname' => 'User3',
            'username' => 'user3',
            'idnumber' => 'user3idnumber',
            'email' => 'nomail+user3@test.com'));
        $cohortsync = new cohortsync();
        $cohortsync->update_cohorts();

        $this->assertEmpty($cohortsync->get_errors());

        $cohort1 = $DB->get_record('cohort', array('idnumber' => 'cohortid1'));
        $cohort2 = $DB->get_record('cohort', array('idnumber' => 'cohortid2'));
        $this->assertEquals('cohortid1', $cohort1->idnumber);
        $this->assertEquals('cohortid2', $cohort2->idnumber);

        $this->assertTrue(cohort_is_member($cohort1->id, $user1->id));
        $this->assertTrue(cohort_is_member($cohort1->id, $user2->id));
        $this->assertTrue(cohort_is_member($cohort1->id, $user3->id));

        $this->assertTrue(cohort_is_member($cohort2->id, $user1->id));

        $contextcat1 = context_coursecat::instance($cat1->id);
        $contextcat2 = context_coursecat::instance($cat2->id);

        $this->assertEquals($contextcat1->id, $cohort1->contextid);
        $this->assertEquals($contextcat2->id, $cohort2->contextid);
    }

    /**
     * Tests cohort synchronisation with default context given.
     */
    public function test_cohortsync_with_defaultcontext() {
        global $CFG, $DB;
        $csvfilename = $CFG->dirroot.'/admin/tool/cohortsync/tests/fixtures/cohorts_nocontext.csv';

        $defaultcat = $this->getDataGenerator()->create_category(array('name' => 'DEFAULTCAT'));
        $contextdefault = context_coursecat::instance($defaultcat->id);

        $user1 = $this->getDataGenerator()->create_user(array(
            'firstname' => 'User1',
            'lastname' => 'User1',
            'username' => 'user1',
            'email' => 'nomail+user1@test.com'));

        $cohortsync = new cohortsync($csvfilename, array('context' => $defaultcat->id));
        $cohortsync->update_cohorts();

        $this->assertEmpty($cohortsync->get_errors());

        $cohort1 = $DB->get_record('cohort', array('idnumber' => 'cohortid1'));

        $this->assertEquals('cohortid1', $cohort1->idnumber);

        $this->assertTrue(cohort_is_member($cohort1->id, $user1->id));

        $this->assertEquals($contextdefault->id, $cohort1->contextid);
    }

    /**
     * Tests cohort synchronisation with no default context given.
     * Context system will be used.
     */
    public function test_cohortsync_with_nodefaultcontext() {
        global $CFG, $DB;
        $csvfilename = $CFG->dirroot.'/admin/tool/cohortsync/tests/fixtures/cohorts_nocontext.csv';

        $user1 = $this->getDataGenerator()->create_user(array(
            'firstname' => 'User1',
            'lastname' => 'User1',
            'username' => 'user1',
            'email' => 'nomail+user1@test.com'));

        $cohortsync = new cohortsync($csvfilename);
        $cohortsync->update_cohorts();

        $this->assertEmpty($cohortsync->get_errors());

        $cohort1 = $DB->get_record('cohort', array('idnumber' => 'cohortid1'));

        $this->assertEquals('cohortid1', $cohort1->idnumber);

        $this->assertTrue(cohort_is_member($cohort1->id, $user1->id));
        $contextsystem = context_system::instance();

        $this->assertEquals($contextsystem->id, $cohort1->contextid);
    }

    /**
     * Tests cohort synchronisation with empty file.
     */
    public function test_cohortsync_with_emptyfile() {
        global $CFG;
        $csvfilename = $CFG->dirroot.'/admin/tool/cohortsync/tests/fixtures/cohorts_empty.csv';

        $cohortsync = new cohortsync($csvfilename);
        $cohortsync->update_cohorts();

        $this->assertEquals(1, count($cohortsync->get_errors()));
        $errormsg = $cohortsync->get_errors()[0];
        $this->assertContains('The CSV file is empty', $errormsg->out());
    }

    /**
     * Tests cohort synchronisation with no found csv file.
     */
    public function test_cohortsync_with_notfoundfile() {
        global $CFG;
        $csvfilename = $CFG->dirroot.'/admin/tool/cohortsync/tests/fixtures/cohorts_notfound.csv';

        $cohortsync = new cohortsync($csvfilename);
        $cohortsync->update_cohorts();

        $this->assertEquals(1, count($cohortsync->get_errors()));
        $errormsg = $cohortsync->get_errors()[0];
        $this->assertContains('not readable or does not exist', $errormsg->out());
    }

    /**
     * Tests cohort synchronisation with no column names in the csv file.
     */
    public function test_cohortsync_with_no_columnnames() {
        global $CFG;
        $csvfilename = $CFG->dirroot.'/admin/tool/cohortsync/tests/fixtures/cohorts_nocolumnnames.csv';

        $cohortsync = new cohortsync($csvfilename);
        $cohortsync->update_cohorts();

        $this->assertEquals(1, count($cohortsync->get_errors()));
        $errormsg = $cohortsync->get_errors()[0];
        $this->assertContains('Please check that it includes column names', $errormsg->out());
    }

    /**
     * Tests cohort synchronisation with empty idnumber.
     */
    public function test_cohortsync_with_emptyidnumber() {
        global $CFG;
        $csvfilename = $CFG->dirroot.'/admin/tool/cohortsync/tests/fixtures/cohorts_emptyidnumber.csv';

        $cohortsync = new cohortsync($csvfilename);
        $cohortsync->update_cohorts();

        $this->assertEquals(4, count($cohortsync->get_errors()));
        foreach ($cohortsync->get_errors() as $errormsg) {
            $this->assertContains('The "idnumber" column is missing', $errormsg->out());
        }
    }

    /**
     * Tests cohort synchronisation with category path as a context.
     */
    public function test_cohortsync_withcategorypath() {
        global $CFG, $DB;
        $csvfilename = $CFG->dirroot.'/admin/tool/cohortsync/tests/fixtures/cohorts_withcategorypath.csv';

        $user1 = $this->getDataGenerator()->create_user(array(
            'firstname' => 'User1',
            'lastname' => 'User1',
            'username' => 'user1',
            'email' => 'nomail+user1@test.com'));

        $cat1 = $this->getDataGenerator()->create_category(array('name' => 'CAT1'));
        $cat2 = $this->getDataGenerator()->create_category(array('parent' => $cat1->id,'name' => 'CAT2'));

        $cohortsync = new cohortsync($csvfilename);
        $cohortsync->update_cohorts();

        $cohort1 = $DB->get_record('cohort', array('idnumber' => 'cohortid1'));
        $contextcat2 = context_coursecat::instance($cat2->id);

        $this->assertTrue(cohort_is_member($cohort1->id, $user1->id));
        $this->assertEquals($contextcat2->id, $cohort1->contextid);

    }

    /**
     * Tests cohort synchronisation with not found category as a context.
     */
    public function test_cohortsync_warnings_when_notfoundcategory() {
        global $CFG, $DB;
        $csvfilename = $CFG->dirroot.'/admin/tool/cohortsync/tests/fixtures/cohorts_notfoundcategory.csv';

        $user1 = $this->getDataGenerator()->create_user(array(
            'firstname' => 'User1',
            'lastname' => 'User1',
            'username' => 'user1',
            'email' => 'nomail+user1@test.com'));

        $cohortsync = new cohortsync($csvfilename);
        $cohortsync->update_cohorts();

        $this->assertEquals(2, count($cohortsync->get_warnings()));
        foreach ($cohortsync->get_warnings() as $warningmsg) {
            $this->assertContains("not found or you don't have permission to create a cohort there", $warningmsg->out());
        }

        // Test no errors found when creating cohorts.
        $this->assertEmpty($cohortsync->get_errors());
        $cohort1 = $DB->get_record('cohort', array('idnumber' => 'cohortid1'));

        $this->assertEquals('cohortid1', $cohort1->idnumber);
        $contextsystem = context_system::instance();
        $this->assertEquals($contextsystem->id, $cohort1->contextid);
    }

    /**
     * Tests cohort synchronisation with not found user.
     */
    public function test_cohortsync_warnings_when_notfounduser() {
        global $CFG;
        $csvfilename = $CFG->dirroot.'/admin/tool/cohortsync/tests/fixtures/cohorts_notfounduser.csv';
        $defaultcat = $this->getDataGenerator()->create_category(array('name' => 'DEFAULTCAT'));

        $cohortsync = new cohortsync($csvfilename);
        $cohortsync->update_cohorts();

        $this->assertEquals(1, count($cohortsync->get_warnings()));
        $warningmsg = $cohortsync->get_warnings()[0];

        $this->assertContains('User "user1" not found in database', $warningmsg->out());

    }

    /**
     * Tests cohort synchronisation with wrong delimiter.
     */
    public function test_cohortsync_withwrongdelimiter() {
        global $CFG;
        $csvfilename = $CFG->dirroot.'/admin/tool/cohortsync/tests/fixtures/cohorts_wrongdelimeter.csv';
        set_config('csvdelimiter', 'wrongdelimiter', 'tool_cohortsync');

        $cohortsync = new cohortsync($csvfilename);
        $cohortsync->update_cohorts();

        $this->assertEquals(1, count($cohortsync->get_errors()));
        $errormsg = $cohortsync->get_errors()[0];

        $this->assertContains('Only these delimeters are allowed: comma, semicolon, colon, tab', $errormsg->out());

    }

    /**
     * Tests cohort synchronisation with wrong user identifier.
     */
    public function test_cohortsync_withwronguseridentifier() {
        global $CFG;
        $csvfilename = $CFG->dirroot.'/admin/tool/cohortsync/tests/fixtures/cohorts_username.csv';
        set_config('useridentifier', 'wronguseridentifier', 'tool_cohortsync');

        $cohortsync = new cohortsync($csvfilename);
        $cohortsync->update_cohorts();

        $this->assertEquals(1, count($cohortsync->get_errors()));
        $errormsg = $cohortsync->get_errors()[0];

        $this->assertContains('Only these user identifiers are allowed: user_id, username, user_idnumber', $errormsg->out());

    }
}
