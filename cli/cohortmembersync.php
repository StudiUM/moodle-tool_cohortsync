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
 * Command-line script for cohort member synchronization.
 *
 * @package    tool_cohortsync
 * @copyright  2016 Universite de Montreal
 * @author     Issam Taboubi <issam.taboubi@umontreal.ca>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_cohortsync;

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->libdir . '/weblib.php');
require_once($CFG->dirroot . '/admin/tool/cohortsync/classes/cohortmembersync.php');

// Now get cli options.
list($options, $unrecognized) = cli_get_params(
        array(
            'help' => false,
            'filepath' => false,
            'flatfiledelimiter' => false,
            'flatfileencoding' => false,
            'cohortidentifier' => false,
            'useridentifier' => false,
            'verbose' => false),
        array(
            'h' => 'help',
            'f' => 'filepath',
            'd' => 'flatfiledelimiter',
            'e' => 'flatfileencoding',
            'c' => 'cohortidentifier',
            'u' => 'useridentifier',
            'v' => 'verbose')
);

if ($options['help']) {
    $help = "Perform Cohort member Synchronization.

    This script synchronize cohorts members from flatfile with cohorts in Moodle database
    The members existing in flatfile but not in Moodle cohorts will be ignored.

    Options:
    -h, --help                Print out this help
    -f, --filepath            Indicate the csv file source to be processed
    -d, --flatfiledelimiter   The csv delimiter used in the file
                              these delimiters are considered 'comma', 'semicolon', 'colon', 'tab'
                              Default: the value defined in the plugin setting
    -e, --flatfileencoding    The encoding of the file.
                              Default: the value defined in the plugin setting
    -c, --cohortidentifier    The column used to identify cohort in the database
                              These idetenfiers are considered: name, idnumber, id
                              Default: the value defined in the plugin setting
    -u, --useridentifier      The column used to identify user in the database
                              These idetenfiers are considered: username, id, idnumber
                              Default: the value defined in the plugin setting
    -v, --verbose             Print verbose progress information

    Example:
    \$ sudo -u www-data /usr/bin/php admin/tool/cohortsync/cli/cohortmembersync.php -u=user_identifier -f=/app/data/cohort/csv/flatfile.txt
    ";

    echo $help;
    die;
}

$params = array(
    'help',
    'verbose',
    'filepath',
    'flatfiledelimiter',
    'flatfileencoding',
    'cohortidentifier',
    'useridentifier'
);

foreach ($params as $param) {
    if ($options[$param] === false) {
        unset($options[$param]);
    }
}

// Cast boolean params.
$filename = (isset($options['filepath']) && !empty($options['filepath'])) ? $options['filepath'] : '';
// Initialise the timer.
$starttime = microtime();

if (empty($options['verbose'])) {
    $trace = new \null_progress_trace();
} else {
    $trace = new \text_progress_trace();
}

$cohortsync = new cohortmembersync($trace, $filename, $options);
$cohortsync->update_cohortsmembers();
$cohortsync->output_result();

// Start output log.
$timenow = time();

$trace->output("Server Time: " . date('r', $timenow));

$difftime = microtime_diff($starttime, microtime());
$trace->output("Execution took " . floor($difftime) . " seconds");
