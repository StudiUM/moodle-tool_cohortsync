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
require_once($CFG->libdir. '/coursecatlib.php');

if ($hassiteconfig) { // Needs this condition or there is error on login page.
    $settings = new admin_settingpage('tool_cohortsync', get_string('pluginname', 'tool_cohortsync'));
    $ADMIN->add('tools', $settings);

    // Cohort configuration.
    $settings->add(new admin_setting_heading('cohortsyncheader',
            get_string('cohortsyncheader', 'tool_cohortsync'), ''));

    $choices = csv_import_reader::get_delimiter_list();
    $settings->add(new admin_setting_configselect('tool_cohortsync/csvdelimiter',
        get_string('csvdelimiter', 'tool_cohortsync'), '', 'comma', $choices));

    $choices = core_text::get_encodings();
    $settings->add(new admin_setting_configselect('tool_cohortsync/csvencoding',
        get_string('encoding', 'tool_cohortsync'), '', 'UTF-8', $choices));

    // GEt context for cohorts.
    $contextoptions = array();
    $displaylist = coursecat::make_categories_list('moodle/cohort:manage');
    // We need to index the options array by context id instead of category id and add option for system context.
    $syscontext = context_system::instance();
    if (has_capability('moodle/cohort:manage', $syscontext)) {
        $contextoptions[$syscontext->id] = $syscontext->get_context_name();
    }
    foreach ($displaylist as $cid => $name) {
        $context = context_coursecat::instance($cid);
        $contextoptions[$context->id] = $name;
    }

    $settings->add(new admin_setting_configselect('tool_cohortsync/defaultcontext',
            new lang_string('defaultcontext', 'cohort'),
            '',
            1,
            $contextoptions));

    // Cohort member configuration.
    $settings->add(new admin_setting_heading('cohortmembersyncheader',
            get_string('cohortmembersyncheader', 'tool_cohortsync'), ''));

    $useridentifieroptions = array(
        'id' => 'id',
        'username' => 'username',
        'idnumber' => 'idnumber'
    );

    $settings->add(new admin_setting_configselect('tool_cohortsync/useridentifier',
        get_string('useridentifier', 'tool_cohortsync'),
        get_string('useridentifierdesc', 'tool_cohortsync'),
        'username',
        $useridentifieroptions));

    $cohortidentifieroptions = array(
        'id' => 'id',
        'name' => 'name',
        'idnumber' => 'idnumber'
    );

    $settings->add(new admin_setting_configselect('tool_cohortsync/cohortidentifier',
            new lang_string('cohortidentifier', 'tool_cohortsync'),
            new lang_string('cohortidentifierdesc', 'tool_cohortsync'),
            'idnumber',
            $cohortidentifieroptions));

    $choices = csv_import_reader::get_delimiter_list();
    $settings->add(new admin_setting_configselect('tool_cohortsync/flatfiledelimiter',
        get_string('flatfiledelimiter', 'tool_cohortsync'), '', 'comma', $choices));

    $choices = core_text::get_encodings();
    $settings->add(new admin_setting_configselect('tool_cohortsync/flatfileencoding',
        get_string('encoding', 'tool_cohortsync'), '', 'UTF-8', $choices));
}
