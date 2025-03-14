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

use tool_cohortsync\cohortsync;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/admin/tool/cohortsync/classes/cohortsync.php');
require_once($CFG->libdir . '/weblib.php');

/**
 * Tests for tool_cohortsync.
 *
 * @package   tool_cohortsync
 * @copyright 2016 Université de Montréal
 * @author    Issam Taboubi <issam.taboubi@umontreal.ca>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \tool_cohortsync\cohortsync
 */
final class cohortsync_test extends \advanced_testcase {

    /** @var \progress_trace trace */
    protected $trace = null;

    /** @var array common columns in header */
    protected $commonheader = null;

    /**
     * Loads the database with test data.
     */
    public static function setUpBeforeClass(): void {

        set_config('csvdelimiter', 'comma', 'tool_cohortsync');
        set_config('csvencoding', 'UTF-8', 'tool_cohortsync');
        set_config('defaultcontext', 'System', 'tool_cohortsync');
        parent::setUpBeforeClass();
    }

    /**
     * Set configuration value in database.
     * Deactivate display of mtrace.
     */
    public function setUp(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $this->commonheader = ['name', 'idnumber', 'description', 'visible'];
        $this->trace = new \null_progress_trace();
        parent::setUp();
    }

    /**
     * Tests cohort synchronisation with column category containing category name.
     */
    public function test_cohortsync_with_column_category_as_name(): void {
        global $DB;

        $cat1 = $this->getDataGenerator()->create_category(['name' => 'CAT1']);
        $cat2 = $this->getDataGenerator()->create_category(['name' => 'CAT2']);

        $cohorts = [];
        $cohorts[] = ['cohort name 1', 'cohortid1', 'first description', 1, 'CAT1'];
        $cohorts[] = ['cohort name 2', 'cohortid2', 'first description', 1, 'CAT2'];
        $cohorts[] = ['cohort name 3', 'cohortid3', 'first description', 1, 'CAT2'];

        $extraheader = ['category'];
        $csvfilename = $this->set_csv_file($this->commonheader, $extraheader, $cohorts);

        $cohortsync = new cohortsync($this->trace, $csvfilename);
        $cohortsync->update_cohorts();
        $this->assertEmpty($cohortsync->get_errors());

        $cohort1 = $DB->get_record('cohort', ['idnumber' => 'cohortid1']);
        $cohort2 = $DB->get_record('cohort', ['idnumber' => 'cohortid2']);
        $cohort3 = $DB->get_record('cohort', ['idnumber' => 'cohortid3']);

        $this->assertEquals('cohortid1', $cohort1->idnumber);
        $this->assertEquals('cohortid2', $cohort2->idnumber);
        $this->assertEquals('cohortid3', $cohort3->idnumber);

        $contextcat1 = \context_coursecat::instance($cat1->id);
        $contextcat2 = \context_coursecat::instance($cat2->id);

        $this->assertEquals($contextcat1->id, $cohort1->contextid);
        $this->assertEquals($contextcat2->id, $cohort2->contextid);
        $this->assertEquals($contextcat2->id, $cohort3->contextid);

    }

    /**
     * Tests cohort synchronisation with column category containing category id.
     */
    public function test_cohortsync_with_column_category_as_id(): void {
        global $DB;

        $cat1 = $this->getDataGenerator()->create_category(['name' => 'CAT1']);
        $cat2 = $this->getDataGenerator()->create_category(['name' => 'CAT2']);

        $cohorts = [];
        $cohorts[] = ['cohort name 1', 'cohortid1', 'first description', 1, $cat1->id];
        $cohorts[] = ['cohort name 2', 'cohortid2', 'first description', 1, $cat2->id];
        $cohorts[] = ['cohort name 3', 'cohortid3', 'first description', 1, $cat2->id];

        $extraheader = ['category'];
        $csvfilename = $this->set_csv_file($this->commonheader, $extraheader, $cohorts);

        $cohortsync = new cohortsync($this->trace, $csvfilename);
        $cohortsync->update_cohorts();

        $this->assertEmpty($cohortsync->get_errors());

        $cohort1 = $DB->get_record('cohort', ['idnumber' => 'cohortid1']);
        $cohort2 = $DB->get_record('cohort', ['idnumber' => 'cohortid2']);
        $cohort3 = $DB->get_record('cohort', ['idnumber' => 'cohortid3']);

        $this->assertEquals('cohortid1', $cohort1->idnumber);
        $this->assertEquals('cohortid2', $cohort2->idnumber);
        $this->assertEquals('cohortid3', $cohort3->idnumber);

        $contextcat1 = \context_coursecat::instance($cat1->id);
        $contextcat2 = \context_coursecat::instance($cat2->id);

        $this->assertEquals($contextcat1->id, $cohort1->contextid);
        $this->assertEquals($contextcat2->id, $cohort2->contextid);
        $this->assertEquals($contextcat2->id, $cohort3->contextid);
    }

    /**
     * Tests cohort synchronisation with column category containing category idnumber.
     */
    public function test_cohortsync_with_column_category_as_idnumber(): void {
        global $DB;

        $cat1 = $this->getDataGenerator()->create_category(['name' => 'CAT1', 'idnumber' => 'IDNBCAT1']);
        $cat2 = $this->getDataGenerator()->create_category(['name' => 'CAT2', 'idnumber' => 'IDNBCAT2']);

        $cohorts = [];
        $cohorts[] = ['cohort name 1', 'cohortid1', 'first description', 1, $cat1->idnumber];
        $cohorts[] = ['cohort name 2', 'cohortid2', 'first description', 1, $cat2->idnumber];
        $cohorts[] = ['cohort name 3', 'cohortid3', 'first description', 1, $cat2->idnumber];

        $extraheader = ['category'];
        $csvfilename = $this->set_csv_file($this->commonheader, $extraheader, $cohorts);

        $cohortsync = new cohortsync($this->trace, $csvfilename);
        $cohortsync->update_cohorts();

        $this->assertEmpty($cohortsync->get_errors());

        $cohort1 = $DB->get_record('cohort', ['idnumber' => 'cohortid1']);
        $cohort2 = $DB->get_record('cohort', ['idnumber' => 'cohortid2']);
        $cohort3 = $DB->get_record('cohort', ['idnumber' => 'cohortid3']);

        $this->assertEquals('cohortid1', $cohort1->idnumber);
        $this->assertEquals('cohortid2', $cohort2->idnumber);
        $this->assertEquals('cohortid3', $cohort3->idnumber);

        $contextcat1 = \context_coursecat::instance($cat1->id);
        $contextcat2 = \context_coursecat::instance($cat2->id);

        $this->assertEquals($contextcat1->id, $cohort1->contextid);
        $this->assertEquals($contextcat2->id, $cohort2->contextid);
        $this->assertEquals($contextcat2->id, $cohort3->contextid);
    }

    /**
     * Tests cohort synchronisation with column category containing category id.
     */
    public function test_cohortsync_with_column_contextid(): void {
        global $DB;

        $cat1 = $this->getDataGenerator()->create_category(['name' => 'CAT1']);
        $cat2 = $this->getDataGenerator()->create_category(['name' => 'CAT2']);

        $contextcat1 = \context_coursecat::instance($cat1->id);
        $contextcat2 = \context_coursecat::instance($cat2->id);

        $cohorts = [];
        $cohorts[] = ['cohort name 1', 'cohortid1', 'first description', 1, $contextcat1->id];
        $cohorts[] = ['cohort name 2', 'cohortid2', 'first description', 1, $contextcat2->id];
        $cohorts[] = ['cohort name 3', 'cohortid3', 'first description', 1, $contextcat2->id];

        $extraheader = ['contextid'];
        $csvfilename = $this->set_csv_file($this->commonheader, $extraheader, $cohorts);

        $cohortsync = new cohortsync($this->trace, $csvfilename);
        $cohortsync->update_cohorts();

        $this->assertEmpty($cohortsync->get_errors());

        $cohort1 = $DB->get_record('cohort', ['idnumber' => 'cohortid1']);
        $cohort2 = $DB->get_record('cohort', ['idnumber' => 'cohortid2']);
        $cohort3 = $DB->get_record('cohort', ['idnumber' => 'cohortid3']);

        $this->assertEquals('cohortid1', $cohort1->idnumber);
        $this->assertEquals('cohortid2', $cohort2->idnumber);
        $this->assertEquals('cohortid3', $cohort3->idnumber);

        $this->assertEquals($contextcat1->id, $cohort1->contextid);
        $this->assertEquals($contextcat2->id, $cohort2->contextid);
        $this->assertEquals($contextcat2->id, $cohort3->contextid);
    }

    /**
     * Tests cohort synchronisation with column category_id containing category id.
     */
    public function test_cohortsync_with_column_category_id(): void {
        global $DB;

        $cat1 = $this->getDataGenerator()->create_category(['name' => 'CAT1']);
        $cat2 = $this->getDataGenerator()->create_category(['name' => 'CAT2']);

        $cohorts = [];
        $cohorts[] = ['cohort name 1', 'cohortid1', 'first description', 1, $cat1->id];
        $cohorts[] = ['cohort name 2', 'cohortid2', 'first description', 1, $cat2->id];
        $cohorts[] = ['cohort name 3', 'cohortid3', 'first description', 1, $cat2->id];

        $extraheader = ['category_id'];
        $csvfilename = $this->set_csv_file($this->commonheader, $extraheader, $cohorts);

        $cohortsync = new cohortsync($this->trace, $csvfilename);
        $cohortsync->update_cohorts();

        $this->assertEmpty($cohortsync->get_errors());

        $cohort1 = $DB->get_record('cohort', ['idnumber' => 'cohortid1']);
        $cohort2 = $DB->get_record('cohort', ['idnumber' => 'cohortid2']);
        $cohort3 = $DB->get_record('cohort', ['idnumber' => 'cohortid3']);

        $this->assertEquals('cohortid1', $cohort1->idnumber);
        $this->assertEquals('cohortid2', $cohort2->idnumber);
        $this->assertEquals('cohortid3', $cohort3->idnumber);

        $contextcat1 = \context_coursecat::instance($cat1->id);
        $contextcat2 = \context_coursecat::instance($cat2->id);

        $this->assertEquals($contextcat1->id, $cohort1->contextid);
        $this->assertEquals($contextcat2->id, $cohort2->contextid);
        $this->assertEquals($contextcat2->id, $cohort3->contextid);
    }

    /**
     * Tests cohort synchronisation with column category_idnumber containing the category idnumber.
     */
    public function test_cohortsync_with_column_category_idnumber(): void {
        global $DB;

        $cat1 = $this->getDataGenerator()->create_category(['name' => 'CAT1', 'idnumber' => 'IDNBCAT1']);
        $cat2 = $this->getDataGenerator()->create_category(['name' => 'CAT2', 'idnumber' => 'IDNBCAT2']);

        $cohorts = [];
        $cohorts[] = ['cohort name 1', 'cohortid1', 'first description', 1, $cat1->idnumber];
        $cohorts[] = ['cohort name 2', 'cohortid2', 'first description', 1, $cat2->idnumber];
        $cohorts[] = ['cohort name 3', 'cohortid3', 'first description', 1, $cat2->idnumber];

        $extraheader = ['category_idnumber'];
        $csvfilename = $this->set_csv_file($this->commonheader, $extraheader, $cohorts);

        $cohortsync = new cohortsync($this->trace, $csvfilename);
        $cohortsync->update_cohorts();

        $this->assertEmpty($cohortsync->get_errors());

        $cohort1 = $DB->get_record('cohort', ['idnumber' => 'cohortid1']);
        $cohort2 = $DB->get_record('cohort', ['idnumber' => 'cohortid2']);
        $cohort3 = $DB->get_record('cohort', ['idnumber' => 'cohortid3']);

        $this->assertEquals('cohortid1', $cohort1->idnumber);
        $this->assertEquals('cohortid2', $cohort2->idnumber);
        $this->assertEquals('cohortid3', $cohort3->idnumber);

        $contextcat1 = \context_coursecat::instance($cat1->id);
        $contextcat2 = \context_coursecat::instance($cat2->id);

        $this->assertEquals($contextcat1->id, $cohort1->contextid);
        $this->assertEquals($contextcat2->id, $cohort2->contextid);
        $this->assertEquals($contextcat2->id, $cohort3->contextid);
    }

    /**
     * Tests cohort synchronisation with column category_path.
     */
    public function test_cohortsync_with_column_category_path(): void {
        global $DB;

        $cat1 = $this->getDataGenerator()->create_category(['name' => 'CAT1']);
        $cat2 = $this->getDataGenerator()->create_category(['parent' => $cat1->id, 'name' => 'CAT2']);

        $cohorts = [];
        $cohorts[] = ['cohort name 1', 'cohortid1', 'first description', 1, 'CAT1'];
        $cohorts[] = ['cohort name 2', 'cohortid2', 'first description', 1, 'CAT1 / CAT2'];
        $cohorts[] = ['cohort name 3', 'cohortid3', 'first description', 1, 'CAT1 / CAT2'];

        $extraheader = ['category_path'];
        $csvfilename = $this->set_csv_file($this->commonheader, $extraheader, $cohorts);

        $cohortsync = new cohortsync($this->trace, $csvfilename);
        $cohortsync->update_cohorts();

        $this->assertEmpty($cohortsync->get_errors());

        $cohort1 = $DB->get_record('cohort', ['idnumber' => 'cohortid1']);
        $cohort2 = $DB->get_record('cohort', ['idnumber' => 'cohortid2']);
        $cohort3 = $DB->get_record('cohort', ['idnumber' => 'cohortid3']);

        $this->assertEquals('cohortid1', $cohort1->idnumber);
        $this->assertEquals('cohortid2', $cohort2->idnumber);
        $this->assertEquals('cohortid3', $cohort3->idnumber);

        $contextcat1 = \context_coursecat::instance($cat1->id);
        $contextcat2 = \context_coursecat::instance($cat2->id);

        $this->assertEquals($contextcat1->id, $cohort1->contextid);
        $this->assertEquals($contextcat2->id, $cohort2->contextid);
        $this->assertEquals($contextcat2->id, $cohort3->contextid);
    }

    /**
     * Tests cohort synchronisation with default context given.
     */
    public function test_cohortsync_with_defaultcontext(): void {
        global $DB;

        $defaultcat = $this->getDataGenerator()->create_category(['name' => 'DEFAULTCAT']);
        $contextdefault = \context_coursecat::instance($defaultcat->id);

        $cohorts = [];
        $cohorts[] = ['cohort name 1', 'cohortid1', 'first description', 1];

        $extraheader = [];
        $csvfilename = $this->set_csv_file($this->commonheader, $extraheader, $cohorts);

        $cohortsync = new cohortsync($this->trace, $csvfilename, ['context' => $defaultcat->id]);
        $cohortsync->update_cohorts();

        $this->assertEmpty($cohortsync->get_errors());

        $cohort1 = $DB->get_record('cohort', ['idnumber' => 'cohortid1']);

        $this->assertEquals('cohortid1', $cohort1->idnumber);

        $this->assertEquals($contextdefault->id, $cohort1->contextid);
    }

    /**
     * Tests cohort synchronisation with no default context given.
     * Context system will be used.
     */
    public function test_cohortsync_no_defaultcontext(): void {
        global $DB;

        $cohorts = [];
        $cohorts[] = ['cohort name 1', 'cohortid1', 'first description', 1];

        $extraheader = [];
        $csvfilename = $this->set_csv_file($this->commonheader, $extraheader, $cohorts);

        $cohortsync = new cohortsync($this->trace, $csvfilename);
        $cohortsync->update_cohorts();

        $this->assertEmpty($cohortsync->get_errors());

        $cohort1 = $DB->get_record('cohort', ['idnumber' => 'cohortid1']);

        $this->assertEquals('cohortid1', $cohort1->idnumber);

        $contextsystem = \context_system::instance();
        $this->assertEquals($contextsystem->id, $cohort1->contextid);
    }

    /**
     * Tests cohort synchronisation with wrong default context given.
     */
    public function test_cohortsync_wrong_defaultcontext(): void {
        global $DB;

        $cohorts = [];
        $cohorts[] = ['cohort name 1', 'cohortid1', 'first description', 1];

        $extraheader = [];
        $csvfilename = $this->set_csv_file($this->commonheader, $extraheader, $cohorts);

        $cohortsync = new cohortsync($this->trace, $csvfilename, ['context' => 100]);

        $this->assertEquals(1, count($cohortsync->get_errors()));
        $errormsg = $cohortsync->get_errors()[0];
        $this->assertStringContainsString('Default context does not exist', $errormsg->out());
    }

    /**
     * Tests cohort synchronisation with empty file.
     */
    public function test_cohortsync_with_emptyfile(): void {

        $csvfilename = $this->set_csv_file();
        $cohortsync = new cohortsync($this->trace, $csvfilename);

        $this->assertEquals(1, count($cohortsync->get_errors()));
        $errormsg = $cohortsync->get_errors()[0];
        $this->assertStringContainsString('is not readable or does not exist', $errormsg->out());

    }

    /**
     * Tests cohort synchronisation with not found csv file.
     */
    public function test_cohortsync_with_notfoundfile(): void {
        global $CFG;

        $csvfilename = $CFG->dirroot.'/admin/tool/cohortsync/tests/fixtures/cohorts_notfound.csv';

        $cohortsync = new cohortsync($this->trace, $csvfilename);

        $this->assertEquals(1, count($cohortsync->get_errors()));
        $errormsg = $cohortsync->get_errors()[0];
        $this->assertStringContainsString('not readable or does not exist', $errormsg->out());
    }

    /**
     * Tests cohort synchronisation with no column names in the csv file.
     */
    public function test_cohortsync_with_no_columnnames(): void {

        $cat1 = $this->getDataGenerator()->create_category(['name' => 'CAT1']);

        $cohorts = [];
        $cohorts[] = ['cohort name 1', 'cohortid1', 'first description', 1, $cat1->id];

        $extracolums = ['category_path'];
        $columnname = false;

        $header = [];
        $extraheader = [];
        $csvfilename = $this->set_csv_file($header, $extraheader, $cohorts);

        $cohortsync = new cohortsync($this->trace, $csvfilename);
        $cohortsync->update_cohorts();

        $this->assertEquals(1, count($cohortsync->get_errors()));
        $errormsg = $cohortsync->get_errors()[0];
        $this->assertStringContainsString('Please check that it includes the correct column names', $errormsg->out());
    }

    /**
     * Tests cohort synchronisation with not found category as a context.
     */
    public function test_cohortsync_warnings_when_notfoundcategory(): void {
        global $DB;

        $cohorts = [];
        $cohorts[] = ['cohort name 1', 'cohortid1', 'first description', 1, 100];

        $extraheader = ['category_id'];
        $csvfilename = $this->set_csv_file($this->commonheader, $extraheader, $cohorts);

        $cohortsync = new cohortsync($this->trace, $csvfilename);
        $cohortsync->update_cohorts();

        $this->assertEquals(1, count($cohortsync->get_warnings()));
        foreach ($cohortsync->get_warnings() as $warningmsg) {
            $this->assertStringContainsString("not found or you don't have permission to create a cohort there",
            $warningmsg->out());
        }

        // Test no errors found when creating cohorts.
        $this->assertEmpty($cohortsync->get_errors());
        $cohort1 = $DB->get_record('cohort', ['idnumber' => 'cohortid1']);

        $this->assertEquals('cohortid1', $cohort1->idnumber);
        $contextsystem = \context_system::instance();
        $this->assertEquals($contextsystem->id, $cohort1->contextid);
    }

    /**
     * Creates an CSV file.
     *
     * @param bool|array $header false or array of extra columns in header
     * @param bool|array $extraheader false or array of extra columns in header
     * @param bool|array $cohorts false or array of cohort ines to put in the file
     * @param string $csvseparator separator used.
     */
    public function set_csv_file($header = false, $extraheader = false, $cohorts = false, $csvseparator = ','): string {

        // Creating the CSV file.
        $filename = 'cohortsync.csv';
        $tmpdir = make_temp_directory('cohortsync');
        $csvfilepath = $tmpdir . '/' . $filename;
        $fp = fopen($csvfilepath, 'w+');

        if (!empty($cohorts)) {
            if (!empty($header)) {
                // Add columns header in file.
                $header = implode($csvseparator, $header);
                if (!empty($extraheader)) {
                    $header .= $csvseparator . implode($csvseparator, $extraheader);
                }
                fwrite($fp, $header . "\n");
            }

            foreach ($cohorts as $cohort) {
                $row = implode($csvseparator, $cohort);
                fwrite($fp, $row . "\n");
            }
        }

        fclose($fp);

        return $csvfilepath;
    }
}
