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
 * Settings for the cohort synchronisation plugin.
 *
 * @package    tool_cohortsync
 * @copyright  2016 Universite de Montreal
 * @author     Issam Taboubi <issam.taboubi@umontreal.ca>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

global $CFG;
require_once($CFG->libdir . '/csvlib.class.php');

if ($hassiteconfig) { // Needs this condition or there is error on login page.
    $settings = new admin_settingpage('tool_cohortsync', get_string('pluginname', 'tool_cohortsync'));
    $ADMIN->add('tools', $settings);

    $settings->add(new admin_setting_heading('cohortsyncheader',
            get_string('cohortsyncheader', 'tool_cohortsync'), ''));


    $useridentifieroptions = array(
        'user_id' => 'user_id',
        'username' => 'username',
        'user_idnumber' => 'user_idnumber'
    );

    $settings->add(new admin_setting_configselect('tool_cohortsync/useridentifier',
        get_string('useridentifier', 'tool_cohortsync'),
        get_string('useridentifierdesc', 'tool_cohortsync'),
        'username',
        $useridentifieroptions));

    $settings->add(new admin_setting_configcheckbox('tool_cohortsync/createcohort',
            new lang_string('createcohort', 'tool_cohortsync'),
            new lang_string('createcohortdesc', 'tool_cohortsync'), 1));


    $choices = csv_import_reader::get_delimiter_list();
    $settings->add(new admin_setting_configselect('tool_cohortsync/csvdelimiter',
        get_string('csvdelimiter', 'tool_cohortsync'), '', 'comma', $choices));

    $choices = core_text::get_encodings();
    $settings->add(new admin_setting_configselect('tool_cohortsync/encoding',
        get_string('encoding', 'tool_cohortsync'), '', 'UTF-8', $choices));

    $settings->add(new admin_setting_configdirectory('tool_cohortsync/filepathsource',
            new lang_string('filepathsource', 'tool_cohortsync'),
            '', '', PARAM_TEXT));

    $settings->add(new admin_setting_heading('formatcsv', get_string('formatcsv', 'tool_cohortsync'),
        get_string('formatcsvdesc', 'tool_cohortsync')));
}
