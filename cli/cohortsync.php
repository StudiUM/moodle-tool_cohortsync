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
 * Command-line script for cohort synchronisation.
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
require_once($CFG->dirroot . '/admin/tool/cohortsync/classes/cohortsync.php');

// Now get cli options.
list($options, $unrecognized) = cli_get_params(
        array(
            'help' => false,
            'filepath' => false,
            'csvdelimiter' => false,
            'csvencoding' => false,
            'context' => false,
            'verbose' => false),
        array(
            'h' => 'help',
            'f' => 'filepath',
            'd' => 'csvdelimiter',
            'e' => 'csvencoding',
            'ctx' => 'context',
            'v' => 'verbose')
);

if ($options['help']) {
    $help = "Perform Cohort Synchronisation.

    This script synchronize cohorts from csv file with cohorts in Moodle database.

    Options:
    -h, --help            Print out this help
    -f, --filepath        Indicate the csv file source to be processed
                          Default: the path defined in the plugin setting
    -d, --csvdelimiter    The csv delimiter used in the file
                          these delimiters are considered 'comma', 'semicolon', 'colon', 'tab'
                          Default: the value defined in the plugin setting
    -e, --csvencoding        The encoding of the file.
                          Default: the value defined in the plugin setting
    -ctx, --context       The category ID matching the context of the cohort
                          Default: system
    -v, --verbose         Print verbose progress information

    Example:
    \$ sudo -u www-data /usr/bin/php admin/tool/cohortsync/cli/cohortsync.php  -f=/app/data/cohort/csv/file.csv
    ";

    echo $help;
    die;
}

$params = array(
    'help',
    'verbose',
    'filepath',
    'csvdelimiter',
    'csvencoding',
    'context'
);
foreach ($params as $param) {
    if ($options[$param] === false) {
        unset($options[$param]);
    }
}
// Emulate normal session.
cron_setup_user();

if (empty($options['verbose'])) {
    $trace = new \null_progress_trace();
} else {
    $trace = new \text_progress_trace();
}

$filename = (isset($options['filepath']) && !empty($options['filepath'])) ? $options['filepath'] : '';
// Initialise the timer.
$starttime = microtime();

$cohortsync = new cohortsync($trace, $filename,  $options);

if (!$cohortsync->get_errors()) {
    $cohortsync->update_cohorts();
}
$cohortsync->output_result();

// Start output log.
$timenow = time();

$trace->output("Server Time: " . date('r', $timenow) . "\n\n");

$difftime = microtime_diff($starttime, microtime());
$trace->output("Execution took " . floor($difftime) . " seconds");
