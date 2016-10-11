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
 * Synchronize cohorts members.
 *
 * @package    tool_cohortsync
 * @copyright  2016 Universite de Montreal
 * @author     Issam Taboubi <issam.taboubi@umontreal.ca>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_cohortsync;

defined('MOODLE_INTERNAL') || die;

global $CFG;
require_once($CFG->libdir . '/csvlib.class.php');
require_once($CFG->libdir. '/coursecatlib.php');
require_once($CFG->dirroot.'/cohort/lib.php');


/**
 * Class to synchronise cohort and cohort members from a source file.
 *
 * @package    tool_cohortsync
 * @copyright  2016 Universite de Montreal
 * @author     Issam Taboubi <issam.taboubi@umontreal.ca>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cohortmembersync {

    /** @var array errors when prcessing file or updating cohorts */
    protected $errors = array();

    /** @var string the file name of the file */
    protected $filename = '';

    /** @var array updating cohorts options */
    protected $params = array();

    /** @var array warnings if cohort or user are not found */
    protected $warnings = array();

    /** @var array informations about members added or deleted */
    protected $infos = array('usersadded' => array(), 'usersdeleted' => array());

    /** @var progress_trace trace */
    protected $trace = null;

    /**
     * Class constructor.
     *
     * @param progress_trace $trace
     * @param string $filepath the file path of the cohorts members file
     * @param array $params Options for processing file
     */
    public function __construct($trace, $filepath, $params = array()) {

        $this->trace = $trace;
        if (!empty($filepath)) {
            if (is_readable($filepath) && is_file($filepath) && filesize($filepath) != 0) {
                $this->filename = $filepath;
            } else {
                $this->errors[] = "The file '$filepath' is not readable or does not exist.";
            }
        } else {
            $this->errors[] = "The file '$filepath' is not readable or does not exist.";
        }

        $this->params = $this->get_defaults_params();

        // Merge params.
        $this->params = array_merge($this->params, $params);

        // Validate the delimiter.
        if (!in_array($this->params['flatfiledelimiter'], array_keys(\csv_import_reader::get_delimiter_list()))) {
            $this->errors[] = "Unknown delimiter : " . $this->params['flatfiledelimiter'];
        }
        // Validate useridentifier.
        if (!in_array($this->params['useridentifier'], array('id',  'idnumber', 'username'))) {
            $this->errors[] = "Unknown user identifier : " . $this->params['useridentifier'];
        }

        // Validate cohortidentifier.
        if (!in_array($this->params['cohortidentifier'], array('name', 'idnumber', 'id'))) {
            $this->errors[] = "Unknown cohort identifier : " . $this->params['cohortidentifier'];
        }
    }

    /**
     * Update cohorts members.
     */
    public function update_cohortsmembers() {

        // Prepare cohorts members data from file.
        $data = $this->process_file();

        if (empty($this->errors) && !empty($data)) {
            foreach ($data as $cohortmember) {
                $cohortid = $cohortmember[1];
                if ($cohortmember[0] == "add") {
                    cohort_add_member($cohortid, $cohortmember[2]);

                    if (!isset($this->infos['usersadded'][$cohortid])) {
                        $this->infos['usersadded'][$cohortid] = 1;
                    } else {
                        $this->infos['usersadded'][$cohortid]++;
                    }
                } else {
                    cohort_remove_member($cohortid, $cohortmember[2]);
                    if (!isset($this->infos['usersdeleted'][$cohortid])) {
                        $this->infos['usersdeleted'][$cohortid] = 1;
                    } else {
                        $this->infos['usersdeleted'][$cohortid]++;
                    }
                }
            }
        }
    }

    /**
     * Get default params for chohort sync plugin.
     *
     * @return array params list
     */
    protected function get_defaults_params() {
        return array(
            'useridentifier' => get_config('tool_cohortsync', 'useridentifier'),
            'cohortidentifier' => get_config('tool_cohortsync', 'cohortidentifier'),
            'flatfiledelimiter' => get_config('tool_cohortsync', 'flatfiledelimiter'),
            'flatfileencoding' => get_config('tool_cohortsync', 'flatfileencoding')
        );
    }

    /**
     * Return list of errors.
     *
     * @return array error list
     */
    public function get_errors() {
        return $this->errors;
    }

    /**
     * Return list of warnings.
     *
     * @return array warning list
     */
    public function get_warnings() {
        return $this->warnings;
    }

    /**
     * Process flatfile.
     * @return array content file
     */
    protected function process_file() {
        global $DB;

        if (!empty($this->errors)) {
            return;
        }

        $cachedusers = array();
        $cachedcohorts = array();

        // We may need more memory here.
        \core_php_time_limit::raise();
        \raise_memory_limit(MEMORY_HUGE);

        $this->trace->output("Processing flat file cohort members");
        $data = array();

        $content = file_get_contents($this->filename);
        $delimiters = \csv_import_reader::get_delimiter_list();
        $separator = $delimiters[$this->params['flatfiledelimiter']];

        if ($content !== false) {
            $content = \core_text::convert($content, $this->params['flatfileencoding'], 'utf-8');
            $content = str_replace("\r", '', $content);
            $content = explode("\n", $content);

            $line = 0;
            foreach ($content as $fields) {
                $line++;
                if (trim($fields) === '') {
                    // Empty lines are ignored.
                    continue;
                }

                // Deal with different separators.
                $fields = explode($separator, $fields);

                // If a line is incorrectly formatted ie does not have 2 comma separated fields then ignore it.
                if (count($fields) !== 3 ) {
                    $this->warnings[] = "Line incorrectly formatted - ignoring $line";
                    continue;
                }

                $fields[0] = trim(\core_text::strtolower($fields[0]));
                $fields[1] = trim(\core_text::strtolower($fields[1]));
                $fields[2] = trim(\core_text::strtolower($fields[2]));

                // Deal with quoted values - all or nothing, we need to support "' in idnumbers, sorry.
                if (strpos($fields[0], "'") === 0) {
                    foreach ($fields as $k => $v) {
                        $fields[$k] = trim($v, "'");
                    }
                } else if (strpos($fields[0], '"') === 0) {
                    foreach ($fields as $k => $v) {
                        $fields[$k] = trim($v, '"');
                    }
                }

                // Check correct formatting of operation field.
                if ($fields[0] !== "add" and $fields[0] !== "del") {
                    $this->warnings[] = "Unknown operation in field 1 - ignoring line $line";
                    continue;
                }

                // Check user information.
                if (!empty($fields[2])) {
                    if (!array_key_exists($fields[2], $cachedusers)) {
                        $cachedusers[$fields[2]] = 0;
                        $user = $DB->get_record("user",
                                array($this->params['useridentifier'] => $fields[2], 'deleted' => 0));
                        if ($user) {
                            $cachedusers[$fields[2]] = $user->id;
                        }
                    }
                    if ($cachedusers[$fields[2]] != 0) {
                        $fields[2] = $cachedusers[$fields[2]];
                    } else {
                        $this->warnings[] = "User not found or deleted field 3 - ignoring line $line value $fields[2]";
                        continue;
                    }
                } else {
                    $this->warnings[] = "Empty user identifier field 3 - ignoring line $line";
                    continue;
                }

                // Check cohort information.
                if (!empty($fields[1])) {
                    if (!array_key_exists($fields[1], $cachedcohorts)) {
                        $cachedcohorts[$fields[1]] = 0;
                        $cohort = $DB->get_record("cohort",
                                array($this->params['cohortidentifier'] => $fields[1]));
                        if ($cohort) {
                            $cachedcohorts[$fields[1]] = $cohort->id;
                        }
                    }
                    if ($cachedcohorts[$fields[1]] != 0) {
                        $fields[1] = $cachedcohorts[$fields[1]];
                    } else {
                        $this->warnings[] = "Cohort not found field 2 - ignoring line $line value $fields[1]";
                        continue;
                    }
                } else {
                    $this->warnings[] = "Empty cohort identifier field 2 - ignoring line $line";
                    continue;
                }

                $data[] = $fields;
            }

            unset($content);
        }

        $this->trace->output("Finished cohortmember file processing.");

        return $data;
    }

    /**
     * Display informations about processing cohorts data.
     *
     */
    public function output_result() {

        if (!empty($this->errors)) {
            $errormessage = 'Errors were found in file data. See details below.';
            $this->trace->output($errormessage);

            foreach ($this->errors as $key => $error) {
                $this->trace->output($error);
            }
        }

        if (!empty($this->warnings)) {
            $warningsmessage = 'Warnings were found in file data. See details below.';
            $this->trace->output($warningsmessage);

            foreach ($this->warnings as $warning) {
                $this->trace->output($warning);
            }
        }

        if (!empty($this->infos)) {
            $this->trace->output("Information");

            if (isset($this->infos['usersadded'])) {
                foreach ($this->infos['usersadded'] as $key => $count) {
                    if ($count > 0) {
                        $m = "'$count' user(s) have been added to the cohort '$key'";
                        $this->trace->output($m);
                    }
                }
            }

            if (isset($this->infos['usersdeleted'])) {
                foreach ($this->infos['usersdeleted'] as $key => $count) {
                    if ($count > 0) {
                        $m = "'$count' user(s) have been removed from the cohort '$key'";
                        $this->trace->output($m);
                    }
                }
            }
        }
        $this->trace->finished();
    }

}
