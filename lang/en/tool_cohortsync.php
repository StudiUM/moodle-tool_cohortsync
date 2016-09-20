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
 * Strings for component 'tool_cohortsync', language 'en'.
 *
 * @package    tool_cohortsync
 * @copyright  2016 Universite de Montreal
 * @author     Issam Taboubi <issam.taboubi@umontreal.ca>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['cohortscreated'] = '"{$a}" cohort(s) created';
$string['cohortsyncheader'] = 'Cohort synchronisation configuration';
$string['createcohort'] = 'Create cohort if it does not exists';
$string['createcohortdesc'] = 'When this parameter is set to true, we create cohort if it does not exist.';
$string['csvdelimiter'] = 'CSV delimiter';
$string['encoding'] = 'Encoding';
$string['errordelimiterfile'] = 'Only these delimeters are allowed: comma, semicolon, colon, tab';
$string['errorreadingfile'] = 'The CSV file "{$a}" is not readable or does not exist.';
$string['errorreadingdefaultfile'] = 'The default CSV file "{$a}" is not readable or does not exist.';
$string['erroruseridentifier'] = 'Only these user identifiers are allowed: user_id, username, user_idnumber';
$string['filepathsource'] = 'The path to the source file';
$string['formatcsv'] = 'CSV file format';
$string['formatcsvdesc'] = 'The following formats are allowed<br>
        <pre>name,idnumber,description,descriptionformat,contextid,visible,username</pre><br>
        The "username" is used as an identifier of the user can also use "user_id" or "user_idnumber"<br>
        If "visible" is not specified, it will be visible by default: Valid values are: "no" or 0 for no visible and "yes" or 1 for visible<br>
        "descriptionformat" is the type of chosen format for the description:<br>
            0 = Moodle auto-format<br>
            1 = HTML format<br>
            2 = Plain text format<br>
            4 = Markdown format<br><br>
        If the "contextid" is not found the context system will be used<br><br>
        All fields except the user (user_id, user_idnumber, username), are associated with cohort<br><br>
        <pre>name,idnumber,description,descriptionformat,category_name,visible,username</pre><br>
        We can use the "category_name" to get the context, if the category is not found the system context will be used<br>
        You can also replace the "category_name" by "category_path", "category_id" or "category_idnumber"';
$string['idnumbercolumnmissing'] = 'The "idnumber" column is missing';
$string['pluginname'] = 'Cohort synchronisation';
$string['notfounduser'] = 'User "{$a}" not found in database';
$string['useradded'] = '"{$a->count}" user(s) have been added to the cohort "{$a->name}"';
$string['useridentifier'] = 'User identifier';
$string['useridentifierdesc'] = 'This parameter is used to identify the user who will be added to the cohort.';