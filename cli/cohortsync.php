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
 * @package    local_cohortsync
 * @copyright  2016 Universite de Montreal
 * @author     Issam Taboubi <issam.taboubi@umontreal.ca>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_cohortsync;

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->dirroot . '/local/cohortsync/classes/cohortsync.php');

// Now get cli options.
list($options, $unrecognized) = cli_get_params(
        array(
            'help' => false,
            'filepath' => false,
            'csvdelimiter' => false,
            'encoding' => false,
            'createcohort' => false,
            'useridentifier' => false,
            'context' => false),
        array(
            'h' => 'help',
            'f' => 'filepath',
            'd' => 'csvdelimiter',
            'e' => 'encoding',
            'c' => 'createcohort',
            'u' => 'useridentifier',
            'ctx' => 'context')
);

if ($options['help']) {
    $help = "Perform Cohort Synchronisation.

    This script synchronise chohrts from csv file with cohorts in Moddle database
    The members existing in csv file but not in Moodle cohorts will be ignored.

    Options:
    -h, --help            Print out this help
    -f, --filepath        Indicate the csv file source to be processed
                          Default: the path defined in the plugin setting
    -d, --csvdelimiter    The csv delimiter used in the file
                          these delimiters are considered 'comma', 'semicolon', 'colon', 'tab'
                          Default: the value defined in the plugin setting
    -e, --encoding        The encoding of the file.
                          Default: the value defined in the plugin setting
    -c, --createcohort    Indicate to create cohort if it does not exist. Possible values: true, false.
                          Default: the value defined in the plugin setting
    -u, --useridentifier  The column used to identify user in the database
                          These idetenfiers are considered: username, user_idnumber, user_id
                          Default: the value defined in the plugin setting
    -ctx, --context       The category ID matching the context of the cohort
                          Default: system

    Example:
    \$ sudo -u www-data /usr/bin/php local/cohortsync/cli/cohortsync.php -u=user_idnumber -f=/app/data/cohort/csv/file.csv
    ";

    echo $help;
    die;
}

$params = array(
    'help',
    'filepath',
    'csvdelimiter',
    'encoding',
    'createcohort',
    'useridentifier',
    'context'
);
foreach ($params as $param) {
    if ($options[$param] === false) {
        unset($options[$param]);
    }
}
// Emulate normal session.
cron_setup_user();

// Cast boolean params.
if (isset($options['createcohort'])) {
    $options['createcohort'] = ($options['createcohort'] === 'false' || $options['createcohort'] === '0') ? false : true;
}

$filename = (isset($options['filepath']) && !empty($options['filepath'])) ? $options['filepath'] : '';
// Initialise the timer.
$starttime = microtime();

$cohortsync = new cohortsync($filename,  $options);
$cohortsync->update_cohorts();
$cohortsync->output_result();

// Start output log.
$timenow = time();

mtrace("Server Time: " . date('r', $timenow) . "\n\n");

$difftime = microtime_diff($starttime, microtime());
mtrace("Execution took " . floor($difftime) . " seconds");
