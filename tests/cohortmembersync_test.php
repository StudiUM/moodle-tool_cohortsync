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
 * @copyright 2016 Université de Montréal
 * @author    Issam Taboubi <issam.taboubi@umontreal.ca>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_cohortsync;

use tool_cohortsync\cohortmembersync;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/admin/tool/cohortsync/classes/cohortmembersync.php');
require_once($CFG->libdir . '/weblib.php');

/**
 * Tests for tool_cohortmembersync.
 *
 * @package   tool_cohortsync
 * @copyright 2016 Université de Montréal
 * @author    Issam Taboubi <issam.taboubi@umontreal.ca>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \tool_cohortsync\cohortmembersync
 */
final class cohortmembersync_test extends \advanced_testcase {

    /** @var \progress_trace trace */
    protected $trace = null;

    /** @var \coursecat created */
    protected $cat1 = null;

    /** @var \coursecat created */
    protected $cat2 = null;

    /** @var \context of category 1 */
    protected $contextcat1 = null;

    /** @var \context of category 2 */
    protected $contextcat2 = null;

    /** @var \cohort created */
    protected $cohort1 = null;

    /** @var \cohort created */
    protected $cohort2 = null;

    /** @var \user created */
    protected $user1 = null;

    /** @var \user created */
    protected $user2 = null;

    /** @var \user created */
    protected $user3 = null;

    /**
     * Create information in database.
     */
    public function setUp(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        set_config('useridentifier', 'id', 'tool_cohortsync');
        set_config('cohortidentifier', 'id', 'tool_cohortsync');
        set_config('flatfiledelimiter', 'comma', 'tool_cohortsync');
        set_config('flatfileencoding', 'UTF-8', 'tool_cohortsync');

        $this->trace = new \null_progress_trace();

        $this->cat1 = $this->getDataGenerator()->create_category(['name' => 'CAT1']);
        $this->cat2 = $this->getDataGenerator()->create_category(['name' => 'CAT2']);

        $this->contextcat1 = \context_coursecat::instance($this->cat1->id);
        $this->contextcat2 = \context_coursecat::instance($this->cat2->id);

        $this->cohort1 = $this->getDataGenerator()->create_cohort([
            'name' => 'Cohort1',
            'idnumber' => 'ID-Coh1',
            'contextid' => $this->contextcat1->id]);

        $this->cohort2 = $this->getDataGenerator()->create_cohort([
            'name' => 'Cohort2',
            'idnumber' => 'ID-Coh2',
            'contextid' => $this->contextcat2->id]);

        $this->user1 = $this->getDataGenerator()->create_user([
            'firstname' => 'User1',
            'lastname' => 'User1',
            'username' => 'user1',
            'idnumber' => 'ID-number1',
            'email' => 'nomail+user1@test.com']);

        $this->user2 = $this->getDataGenerator()->create_user([
            'firstname' => 'User2',
            'lastname' => 'User2',
            'username' => 'user2',
            'idnumber' => 'ID-number2',
            'email' => 'nomail+user2@test.com']);

        $this->user3 = $this->getDataGenerator()->create_user([
            'firstname' => 'User3',
            'lastname' => 'User3',
            'username' => 'user3',
            'idnumber' => 'ID-number3',
            'email' => 'nomail+user3@test.com']);
        parent::setUp();

    }

    /**
     * Tests cohort members synchronisation with user id and cohort id.
     */
    public function test_cohortmembersync_with_userid_cohortid(): void {

        $lines = [];
        $lines[] = ['add', $this->cohort1->id, $this->user1->id];
        $lines[] = ['add', $this->cohort1->id, $this->user2->id];
        $lines[] = ['add', $this->cohort1->id, $this->user3->id];
        $lines[] = ['add', $this->cohort2->id, $this->user1->id];

        $filename = $this->set_csv_file($lines);

        $cohortmembersync = new cohortmembersync($this->trace, $filename);
        $cohortmembersync->update_cohortsmembers();

        // Check theres is no error and cohort members are created.
        $this->assertEmpty($cohortmembersync->get_errors());
        $this->assertEmpty($cohortmembersync->get_warnings());
        $this->assertTrue(cohort_is_member($this->cohort1->id, $this->user1->id));
        $this->assertTrue(cohort_is_member($this->cohort1->id, $this->user2->id));
        $this->assertTrue(cohort_is_member($this->cohort1->id, $this->user3->id));
        $this->assertTrue(cohort_is_member($this->cohort2->id, $this->user1->id));

    }

    /**
     * Tests cohort members synchronisation with user idnumber and cohort id.
     */
    public function test_cohortmembersync_with_useridnumber_cohortid(): void {

        set_config('useridentifier', 'idnumber', 'tool_cohortsync');

        $lines = [];
        $lines[] = ['add', $this->cohort1->id, $this->user1->idnumber];
        $lines[] = ['add', $this->cohort1->id, $this->user2->idnumber];
        $lines[] = ['add', $this->cohort1->id, $this->user3->idnumber];
        $lines[] = ['add', $this->cohort2->id, $this->user1->idnumber];

        $filename = $this->set_csv_file($lines);

        $cohortmembersync = new cohortmembersync($this->trace, $filename);
        $cohortmembersync->update_cohortsmembers();

        // Check there is no error and cohort members are created.
        $this->assertEmpty($cohortmembersync->get_errors());
        $this->assertEmpty($cohortmembersync->get_warnings());
        $this->assertTrue(cohort_is_member($this->cohort1->id, $this->user1->id));
        $this->assertTrue(cohort_is_member($this->cohort1->id, $this->user2->id));
        $this->assertTrue(cohort_is_member($this->cohort1->id, $this->user3->id));
        $this->assertTrue(cohort_is_member($this->cohort2->id, $this->user1->id));
    }

    /**
     * Tests cohort members synchronisation with user name and cohort id.
     */
    public function test_cohortmembersync_with_username_cohortid(): void {

        set_config('useridentifier', 'username', 'tool_cohortsync');

        $lines = [];
        $lines[] = ['add', $this->cohort1->id, $this->user1->username];
        $lines[] = ['add', $this->cohort1->id, $this->user2->username];
        $lines[] = ['add', $this->cohort1->id, $this->user3->username];
        $lines[] = ['add', $this->cohort2->id, $this->user1->username];

        $filename = $this->set_csv_file($lines);

        $cohortmembersync = new cohortmembersync($this->trace, $filename);
        $cohortmembersync->update_cohortsmembers();

        // Check there is no error and cohort members are created.
        $this->assertEmpty($cohortmembersync->get_errors());
        $this->assertEmpty($cohortmembersync->get_warnings());
        $this->assertTrue(cohort_is_member($this->cohort1->id, $this->user1->id));
        $this->assertTrue(cohort_is_member($this->cohort1->id, $this->user2->id));
        $this->assertTrue(cohort_is_member($this->cohort1->id, $this->user3->id));
        $this->assertTrue(cohort_is_member($this->cohort2->id, $this->user1->id));
    }

    /**
     * Tests cohort members synchronisation with user id and cohort name.
     */
    public function test_cohortmembersync_with_userid_cohortname(): void {

        set_config('cohortidentifier', 'name', 'tool_cohortsync');

        $lines = [];
        $lines[] = ['add', $this->cohort1->name, $this->user1->id];
        $lines[] = ['add', $this->cohort1->name, $this->user2->id];
        $lines[] = ['add', $this->cohort1->name, $this->user3->id];
        $lines[] = ['add', $this->cohort2->name, $this->user1->id];

        $filename = $this->set_csv_file($lines);

        $cohortmembersync = new cohortmembersync($this->trace, $filename);
        $cohortmembersync->update_cohortsmembers();

        // Check there is no error and cohort members are created.
        $this->assertEmpty($cohortmembersync->get_errors());
        $this->assertEmpty($cohortmembersync->get_warnings());
        $this->assertTrue(cohort_is_member($this->cohort1->id, $this->user1->id));
        $this->assertTrue(cohort_is_member($this->cohort1->id, $this->user2->id));
        $this->assertTrue(cohort_is_member($this->cohort1->id, $this->user3->id));
        $this->assertTrue(cohort_is_member($this->cohort2->id, $this->user1->id));
    }

    /**
     * Tests cohort members synchronisation with user id and cohort idnumber.
     */
    public function test_cohortmembersync_with_userid_cohortidnumner(): void {

        set_config('cohortidentifier', 'idnumber', 'tool_cohortsync');

        $lines = [];
        $lines[] = ['add', $this->cohort1->idnumber, $this->user1->id];
        $lines[] = ['add', $this->cohort1->idnumber, $this->user2->id];
        $lines[] = ['add', $this->cohort1->idnumber, $this->user3->id];
        $lines[] = ['add', $this->cohort2->idnumber, $this->user1->id];

        $filename = $this->set_csv_file($lines);

        $cohortmembersync = new cohortmembersync($this->trace, $filename);
        $cohortmembersync->update_cohortsmembers();

        // Check there is no error and cohort members are created.
        $this->assertEmpty($cohortmembersync->get_errors());
        $this->assertEmpty($cohortmembersync->get_warnings());
        $this->assertTrue(cohort_is_member($this->cohort1->id, $this->user1->id));
        $this->assertTrue(cohort_is_member($this->cohort1->id, $this->user2->id));
        $this->assertTrue(cohort_is_member($this->cohort1->id, $this->user3->id));
        $this->assertTrue(cohort_is_member($this->cohort2->id, $this->user1->id));
    }

    /**
     * Tests cohort members synchronisation remove user.
     */
    public function test_cohortmembersync_delete(): void {
        global $DB;

        set_config('cohortidentifier', 'id', 'tool_cohortsync');

        $cohortmember = new \stdClass();
        $cohortmember->cohortid = $this->cohort1->id;
        $cohortmember->userid = $this->user1->id;

        $DB->insert_record('cohort_members', $cohortmember);
        // Check cohort members are not created.
        $this->assertEquals(1, $DB->count_records('cohort_members'));

        $lines = [];
        $lines[] = ['del', $this->cohort1->id, $this->user1->id];

        $filename = $this->set_csv_file($lines);

        $cohortmembersync = new cohortmembersync($this->trace, $filename);
        $cohortmembersync->update_cohortsmembers();

        // Check theres is no error and cohort members are created.
        $this->assertEmpty($cohortmembersync->get_errors());
        $this->assertEmpty($cohortmembersync->get_warnings());

        // Check cohort members is deleted.
        $this->assertEquals(0, $DB->count_records('cohort_members'));

    }

    /**
     * Tests cohort members synchronisation with wrong parameter values.
     */
    public function test_cohortmembersync_with_wrong_parameters(): void {
        global $DB;

        $lines = [];
        $lines[] = ['add', $this->cohort1->id, $this->user1->id];
        $lines[] = ['add', $this->cohort1->id, $this->user2->id];
        $lines[] = ['add', $this->cohort1->id, $this->user3->id];
        $lines[] = ['add', $this->cohort2->id, $this->user1->id];

        $filename = $this->set_csv_file($lines);

        $params = ['cohortidentifier' => 'badvalue',
            'useridentifier' => 'badvalue',
            'flatfiledelimiter' => 'badvalue'];

        $cohortmembersync = new cohortmembersync($this->trace, $filename, $params);
        $cohortmembersync->update_cohortsmembers();

        // Check there is error.
        $errors = $cohortmembersync->get_errors();
        $this->assertEquals(3, count($errors));
        $this->assertStringContainsString('Unknown delimiter', $errors[0] );
        $this->assertStringContainsString('Unknown user identifier', $errors[1] );
        $this->assertStringContainsString('Unknown cohort identifier', $errors[2] );

        // Check cohort members are not created.
        $this->assertEquals(0, $DB->count_records('cohort_members'));

    }

    /**
     * Tests cohort members synchronisation with good parameter values.
     */
    public function test_cohortmembersync_with_good_parameters(): void {

        $lines = [];
        $lines[] = ['add', $this->cohort1->idnumber, $this->user1->idnumber];
        $lines[] = ['add', $this->cohort1->idnumber, $this->user2->idnumber];
        $lines[] = ['add', $this->cohort1->idnumber, $this->user3->idnumber];
        $lines[] = ['add', $this->cohort2->idnumber, $this->user1->idnumber];

        $filename = $this->set_csv_file($lines);

        $cohortmembersync = new cohortmembersync($this->trace, $filename);
        $cohortmembersync->update_cohortsmembers();

        $filename = $this->set_csv_file($lines, ';');

        $params = ['cohortidentifier' => 'idnumber',
            'useridentifier' => 'idnumber',
            'flatfiledelimiter' => 'semicolon'];

        $cohortmembersync = new cohortmembersync($this->trace, $filename, $params);
        $cohortmembersync->update_cohortsmembers();

        // Check there is no error and cohort members are created.
        $this->assertEmpty($cohortmembersync->get_errors());
        $this->assertEmpty($cohortmembersync->get_warnings());
        $this->assertTrue(cohort_is_member($this->cohort1->id, $this->user1->id));
        $this->assertTrue(cohort_is_member($this->cohort1->id, $this->user2->id));
        $this->assertTrue(cohort_is_member($this->cohort1->id, $this->user3->id));
        $this->assertTrue(cohort_is_member($this->cohort2->id, $this->user1->id));

    }

    /**
     * Tests cohort members synchronisation with user id and cohort idnumber.
     */
    public function test_cohortmembersync_warning(): void {
        global $DB;

        $lines = [];
        $lines[] = ['badvalue', $this->cohort1->id, $this->user1->id];
        $lines[] = ['add', 'badcohortid', $this->user2->id];
        $lines[] = ['add', $this->cohort1->id, 'badcohortid'];
        $lines[] = ['add', '', $this->user1->id];
        $lines[] = ['add', $this->cohort1->id, ''];

        $filename = $this->set_csv_file($lines);

        $cohortmembersync = new cohortmembersync($this->trace, $filename);
        $cohortmembersync->update_cohortsmembers();

        $this->assertEmpty($cohortmembersync->get_errors());

        $warnings = $cohortmembersync->get_warnings();
        $this->assertEquals(5, count($warnings));
        $this->assertStringContainsString('Unknown operation', $warnings[0] );
        $this->assertStringContainsString('Cohort not found', $warnings[1] );
        $this->assertStringContainsString('User not found or deleted', $warnings[2] );
        $this->assertStringContainsString('Empty cohort identifier', $warnings[3] );
        $this->assertStringContainsString('Empty user identifier', $warnings[4] );

        // Check cohort members are not created.
        $this->assertEquals(0, $DB->count_records('cohort_members'));

    }

    /**
     * Tests cohort members synchronisation with empty file.
     */
    public function test_cohortmembersync_with_emptyfile(): void {

        $csvfilename = $this->set_csv_file();
        $cohortmembersync = new cohortmembersync($this->trace, $csvfilename);

        $this->assertEquals(1, count($cohortmembersync->get_errors()));
        $errormsg = $cohortmembersync->get_errors()[0];
        $this->assertStringContainsString('is not readable or does not exist', $errormsg);
    }

    /**
     * Tests cohort members synchronisation with no found file.
     */
    public function test_cohortmembersync_with_notfoundfile(): void {
        global $CFG;
        $csvfilename = $CFG->dirroot.'/admin/tool/cohortsync/tests/fixtures/cohortmembers_notfound';

        $cohortmembersync = new cohortmembersync($this->trace, $csvfilename);

        $this->assertEquals(1, count($cohortmembersync->get_errors()));
        $errormsg = $cohortmembersync->get_errors()[0];
        $this->assertStringContainsString('is not readable or does not exist', $errormsg);
    }

    /**
     * Creates file.
     *
     * @param bool|array $lines false or array of lines to write in file
     * @param string $separator  separator if columns
     * @return string $filepath file path
     */
    public function set_csv_file($lines = false, $separator = ','): string {

        // Creating the file.
        $filename = 'cohortmembersync';
        $tmpdir = make_temp_directory('cohortsync');
        $filepath = $tmpdir . '/' . $filename;
        $fp = fopen($filepath, 'w+');

        if (!empty($lines)) {
            foreach ($lines as $line) {
                $row = implode($separator, $line);
                fwrite($fp, $row . "\n");
            }
        }

        fclose($fp);

        return $filepath;
    }
}
