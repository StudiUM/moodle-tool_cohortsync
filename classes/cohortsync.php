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
 * Synchronise cohorts.
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
class cohortsync {

    /** @var array errors when prcessing csv file or updating cohorts */
    protected $errors = array();

    /** @var string the file name of the csv file */
    protected $filename = '';

    /** @var array updating cohorts options */
    protected $params = array();

    /** @var array cached list of available contexts */
    protected $contextlist = null;

    /** @var context to be used when no context defined or found */
    protected $defaultcontext = null;

    /** @var array warnings if cohort or user are not found */
    protected $warnings = array();

    /** @var array informations about members added and cohorts created */
    protected $infos = array('cohorts' => 0, 'users' => array());

    /** @var array list of cohort data */
    protected $cohorts = array();


    /**
     * Class constructor.
     *
     * @param string $filepath the file path of the csv cohorts file
     * @param array $params Options for processing csv file
     */
    public function __construct($filepath = null, $params = array()) {

        if (empty($filepath)) {
            $filepath = get_config('tool_cohortsync', 'filepathsource');
        }
        if (!empty($filepath)) {
            if (is_readable($filepath) && is_file($filepath)) {
                $this->filename = $filepath;
            } else {
                $this->errors[] = new \lang_string('errorreadingfile', 'tool_cohortsync', $filepath);
            }
        } else {
            $this->errors[] = new \lang_string('errorreadingfile', 'tool_cohortsync', $filepath);
        }

        $this->params = $this->get_defaults_params();

        // Define the default context.
        if (isset($params['context'])) {
            $this->defaultcontext = \context_coursecat::instance($params['context']);
        } else {
            $this->defaultcontext = \context_system::instance();
        }

        // Merge params.
        $this->params = array_merge($this->params, $params);
        // Validate the delimiter.
        if (!in_array($this->params['csvdelimiter'], array_keys(\csv_import_reader::get_delimiter_list()))) {
            $this->errors[] = new \lang_string('errordelimiterfile', 'tool_cohortsync');
        }
        // Validate useridentifier.
        if (!in_array($this->params['useridentifier'], array('user_id', 'username', 'user_idnumber'))) {
            $this->errors[] = new \lang_string('erroruseridentifier', 'tool_cohortsync');
        }
    }

    /**
     * Update cohorts members.
     */
    public function update_cohorts() {
        global $DB;

        // Prepare cohorts data from CSV file.
        $this->process_file();
        if (empty($this->errors) && !empty($this->cohorts)) {
            $cohortsexist = array();
            foreach ($this->cohorts as $cohort) {
                $useridentifiervalue = (isset($cohort[$this->params['useridentifier']])) ?
                        $cohort[$this->params['useridentifier']] : false;
                $cohortid = isset($cohortsexist[$cohort['idnumber']]) ? $cohortsexist[$cohort['idnumber']] : false;
                if ($cohortid === false) {
                    $c = $DB->get_record('cohort', array('idnumber' => $cohort['idnumber']));
                    if ($c) {
                        $cohortid = $c->id;
                    } else if ($this->params['createcohort']) {
                        // Not found in the database so insert cohort.
                        unset($cohort[$this->params['useridentifier']]);
                        $cohortobject = (object) $cohort;
                        $cohortid = cohort_add_cohort($cohortobject);
                        $this->infos['cohorts']++;
                    }
                    // Store cohort in local var.
                    $cohortsexist[$cohort['idnumber']] = $cohortid;
                }
                if ($useridentifiervalue) {
                    $identifier = $this->params['useridentifier'];
                    if ($this->params['useridentifier'] == 'user_id') {
                        $identifier = 'id';
                    }
                    if ($this->params['useridentifier'] == 'user_idnumber') {
                        $identifier = 'idnumber';
                    }
                    $user = $DB->get_record('user', array( $identifier => $useridentifiervalue));
                    if ($user) {
                        cohort_add_member($cohortid, $user->id);
                        if (!isset($this->infos['users'][$cohort['idnumber']])) {
                            $this->infos['users'][$cohort['idnumber']] = 1;
                        } else {
                            $this->infos['users'][$cohort['idnumber']]++;
                        }
                    } else {
                        $this->warnings[] = new \lang_string('notfounduser', 'tool_cohortsync', $useridentifiervalue);
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
            'createcohort' => get_config('tool_cohortsync', 'createcohort'),
            'csvdelimiter' => get_config('tool_cohortsync', 'csvdelimiter'),
            'encoding' => get_config('tool_cohortsync', 'encoding')
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
     * Returns the list of contexts where current user can create cohorts.
     *
     * @return array
     */
    protected function get_context_options() {

        if ($this->contextlist === null) {
            $this->contextlist = array();
            $displaylist = \coursecat::make_categories_list('moodle/cohort:manage');
            // We need to index the options array by context id instead of category id and add option for system context.
            $syscontext = \context_system::instance();
            if (has_capability('moodle/cohort:manage', $syscontext)) {
                $this->contextlist[$syscontext->id] = $syscontext->get_context_name();
            }
            foreach ($displaylist as $cid => $name) {
                $context = \context_coursecat::instance($cid);
                $this->contextlist[$context->id] = $name;
            }
        }
        return $this->contextlist;
    }

    /**
     * Determines in which context the particular cohort will be created
     *
     * @param array $hash
     * @return array hash
     */
    protected function resolve_context($hash) {
        global $DB;

        if (!empty($hash['contextid'])) {
            // Contextid was specified, verify we can post there.
            $contextlist = $this->get_context_options();
            if (!isset($contextlist[$hash['contextid']])) {
                $this->warnings[] = new \lang_string('contextnotfound', 'cohort', $hash['contextid']);
                $hash['contextid'] = $this->defaultcontext->id;
            }
            return $hash;
        }

        if (!empty($hash['context'])) {
            $systemcontext = \context_system::instance();
            if ((\core_text::strtolower(trim($hash['context'])) ===
                    \core_text::strtolower($systemcontext->get_context_name())) ||
                    ('' . $hash['context'] === '' . $systemcontext->id)) {
                // User meant system context.
                $hash['contextid'] = $systemcontext->id;
                $contextlist = $this->get_context_options();
                if (!isset($contextlist[$hash['contextid']])) {
                    $this->warnings[] = new \lang_string('contextnotfound', 'cohort', $hash['context']);
                    $hash['contextid'] = $this->defaultcontext->id;
                }
            }
        }

        if (!empty($hash['category_path'])) {
            // We already have array with available categories, look up the value.
            $contextlist = $this->get_context_options();
            if (!$hash['contextid'] = array_search($hash['category_path'], $contextlist)) {
                $this->warnings[] = new \lang_string('categorynotfound', 'cohort', s($hash['category_path']));
                $hash['contextid'] = $this->defaultcontext->id;
            }
            return $hash;
        }

        // Now search by category id or category idnumber or category_name.
        if (!empty($hash['category_id'])) {
            $field = 'id';
            $value = clean_param($hash['category_id'], PARAM_INT);
        } else if (!empty($hash['category_idnumber'])) {
            $field = 'idnumber';
            $value = $hash['category_idnumber'];
        } else if (!empty($hash['category_name'])) {
            $field = 'name';
            $value = $hash['category_name'];
        } else {
            // No category field was specified, assume default category.
            $hash['contextid'] = $this->defaultcontext->id;
            return $hash;
        }

        if (empty($this->categoriescache[$field][$value])) {
            $record = $DB->get_record_sql("SELECT c.id, ctx.id contextid
                FROM {context} ctx JOIN {course_categories} c ON ctx.contextlevel = ? AND ctx.instanceid = c.id
                WHERE c.$field = ?", array(CONTEXT_COURSECAT, $value));
            if ($record && ($contextlist = $this->get_context_options()) && isset($contextlist[$record->contextid])) {
                $contextid = $record->contextid;
            } else {
                $this->warnings[] = new \lang_string('categorynotfound', 'cohort', s($value));
                $contextid = $this->defaultcontext->id;
            }
            // Next time when we can look up and don't search by this value again.
            $this->categoriescache[$field][$value] = $contextid;
        }
        $hash['contextid'] = $this->categoriescache[$field][$value];

        return $hash;
    }

    /**
     * Process file to get cohorts with their members.
     *
     * @return array
     */
    protected function process_file() {

        if (!empty($this->errors)) {
            return;
        }

        // Read and parse the CSV file using csv library.
        $content = file_get_contents($this->filename);
        if (!$content) {
            $this->errors[] = new \lang_string('csvemptyfile', 'error');
            return;
        }

        $cohorts = array();

        $uploadid = \csv_import_reader::get_new_iid('cohortsync');
        $cir = new \csv_import_reader($uploadid, 'cohortsync');
        $readcount = $cir->load_csv_content($content, $this->params['encoding'], $this->params['csvdelimiter']);
        unset($content);
        if (!$readcount) {
            $this->errors[] = get_string('csvloaderror', 'error', $cir->get_error());
            return;
        }
        $columns = $cir->get_columns();

        // Check that columns include 'name' and warn about extra columns.
        $allowedcolumns = array('contextid', 'name', 'idnumber', 'description', 'descriptionformat', 'visible');
        // Add user identifier.
        array_push($allowedcolumns, $this->params['useridentifier']);
        $additionalcolumns = array('context', 'category_id', 'category_idnumber', 'category_path', 'category_name');
        $displaycolumns = array();
        $extracolumns = array();
        $columnsmapping = array();
        foreach ($columns as $i => $columnname) {
            $columnnamelower = preg_replace('/ /', '', \core_text::strtolower($columnname));
            $columnsmapping[$i] = null;
            if (in_array($columnnamelower, $allowedcolumns)) {
                $displaycolumns[$columnnamelower] = $columnname;
                $columnsmapping[$i] = $columnnamelower;
            } else if (in_array($columnnamelower, $additionalcolumns)) {
                $columnsmapping[$i] = $columnnamelower;
            } else {
                $extracolumns[] = $columnname;
            }
        }
        if (!in_array('name', $columnsmapping)) {
            $this->errors[] = new \lang_string('namecolumnmissing', 'cohort');
            return;
        }
        if (!in_array('idnumber', $columnsmapping)) {
            $this->errors[] = new \lang_string('idnumbercolumnmissing', 'tool_cohortsync');
            return;
        }
        if ($extracolumns) {
            $this->warnings[] = new \lang_string('csvextracolumns', 'cohort', s(join(', ', $extracolumns)));
        }

        if (!isset($displaycolumns['contextid'])) {
            $displaycolumns['contextid'] = 'contextid';
        }

        // Parse data rows.
        $cir->init();
        $rownum = 0;
        $haserrors = false;
        $haswarnings = false;
        while ($row = $cir->next()) {
            $rownum++;
            $hash = array();
            foreach ($row as $i => $value) {
                if ($columnsmapping[$i]) {
                    $hash[$columnsmapping[$i]] = $value;
                }
            }
            $hash = $this->clean_cohort_data($hash);
            $hash = $this->resolve_context($hash);

            if (empty($hash['name'])) {
                $this->errors['line_' . ($rownum + 1)] = new \lang_string('namefieldempty', 'cohort');
            }
            if (empty($hash['idnumber'])) {
                $this->errors['line_' . ($rownum + 1)] = new \lang_string('idnumbercolumnmissing', 'tool_cohortsync');
            }

            $cohorts[$rownum] = array_intersect_key($hash, $displaycolumns);
        }

        // Close and unlink the temp folder and file.
        $cir->close();
        $cir->cleanup(true);

        $this->cohorts = $cohorts;
    }

    /**
     * Cleans input data about one cohort.
     *
     * @param array $hash
     */
    protected function clean_cohort_data($hash) {
        foreach ($hash as $key => $value) {
            switch ($key) {
                case 'contextid': $hash[$key] = clean_param($value, PARAM_INT);
                    break;
                case 'name': $hash[$key] = \core_text::substr(clean_param($value, PARAM_TEXT), 0, 254);
                    break;
                case 'idnumber': $hash[$key] = \core_text::substr(clean_param($value, PARAM_RAW), 0, 254);
                    break;
                case 'description': $hash[$key] = \clean_param($value, PARAM_RAW);
                    break;
                case 'descriptionformat': $hash[$key] = \clean_param($value, PARAM_INT);
                    break;
                case 'visible':
                    $tempstr = trim(\core_text::strtolower($value));
                    if ($tempstr === '') {
                        // Empty string is treated as "YES" (the default value for cohort visibility).
                        $hash[$key] = 1;
                    } else {
                        if ($tempstr === \core_text::strtolower(get_string('no')) || $tempstr === 'n') {
                            // Special treatment for 'no' string that is not included in clean_param().
                            $value = 0;
                        }
                        $hash[$key] = clean_param($value, PARAM_BOOL) ? 1 : 0;
                    }
                    break;
            }
        }
        return $hash;
    }

    /**
     * Display informations about processing cohorts data. 
     *
     * @param string $type type of information to output.
     */
    public function output_result($type = 'all') {

        if (!empty($this->errors) && ($type == 'all' || $type == 'error')) {
            $errormessage = new \lang_string('csvcontainserrors', 'cohort');
            mtrace($errormessage . "\n");

            foreach ($this->errors as $key => $error) {
                mtrace($key . ": " . $error . "\n");
            }

            mtrace("****************** \n");
        }

        if (!empty($this->warnings) && ($type == 'all' || $type == 'warning')) {
            $warningsmessage = new \lang_string('csvcontainswarnings', 'cohort');
            mtrace($warningsmessage . "\n");

            foreach ($this->warnings as $warning) {
                mtrace($warning . "\n");
            }

            mtrace("****************** \n");
        }

        if (!empty($this->infos) && ($type == 'all' || $type == 'info')) {
            $infomessage = new \lang_string('info');
            mtrace($infomessage . "\n");

            $nbcohortcreated = (!empty($this->infos['cohorts'])) ? $this->infos['cohorts'] : '00';
            $messageinfocohort = new \lang_string('cohortscreated', 'tool_cohortsync', $nbcohortcreated);
            mtrace($messageinfocohort . "\n");

            if (isset($this->infos['users'])) {
                foreach ($this->infos['users'] as $key => $count) {
                    $varinfo = array();
                    $varinfo['name'] = $key;
                    $varinfo['count'] = $count;
                    $messageuseradded = new \lang_string('useradded', 'tool_cohortsync', (object) $varinfo);
                    mtrace($messageuseradded . "\n");
                }
            }

            mtrace("****************** \n");
        }
    }

}
