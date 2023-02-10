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
 * @package    block_use_stats
 * @category   blocks
 * @author     Valery Fremaux (valery.fremaux@gmail.com)
 * @copyright  Valery Fremaux (valery.fremaux@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/blocks/use_stats/adminlib.php');

use \block\use_stats\admin_setting_configdatetime;

if ($ADMIN->fulltree) {

    $settings->add(new admin_setting_heading('blockdisplay', get_string('blockdisplay', 'block_use_stats'), ''));

    $daystr = get_string('days');
    $fromwhenoptions = array('5' => '5 '.$daystr,
                             '15' => '15 '.$daystr,
                             '30' => '30 '.$daystr,
                             '60' => '60 '.$daystr,
                             '90' => '90 '.$daystr,
                             '180' => '180 '.$daystr,
                             '365' => '365 '.$daystr,
                             );

    $key = 'block_use_stats/fromwhen';
    $label = get_string('configfromwhen', 'block_use_stats');
    $desc = get_string('configfromwhen_desc', 'block_use_stats');
    $settings->add(new admin_setting_configselect($key, $label, $desc, 60, $fromwhenoptions));

    $backtrackmodeoptions = array('sliding' => get_string('sliding', 'block_use_stats'),
        'fixeddate' => get_string('fixeddate', 'block_use_stats')
     );
    $key = 'block_use_stats/backtrackmode';
    $label = get_string('configbacktrackmode', 'block_use_stats');
    $desc = get_string('configbacktrackmode_desc', 'block_use_stats');
    $settings->add(new admin_setting_configselect($key, $label, $desc, 'sliding', $backtrackmodeoptions));

    $backtracksourceoptions = array('studentchoice' => get_string('studentchoice', 'block_use_stats'),
        'fixedchoice' => get_string('fixedchoice', 'block_use_stats')
     );
    $key = 'block_use_stats/backtracksource';
    $label = get_string('configbacktracksource', 'block_use_stats');
    $desc = get_string('configbacktracksource_desc', 'block_use_stats');
    $settings->add(new admin_setting_configselect($key, $label, $desc, 'studentchoice', $backtracksourceoptions));

    $key = 'block_use_stats/filterdisplayunder';
    $label = get_string('configfilterdisplayunder', 'block_use_stats');
    $desc = get_string('configfilterdisplayunder_desc', 'block_use_stats');
    $settings->add(new admin_setting_configtext($key, $label, $desc, 60));

    $key = 'block_use_stats/displayothertime';
    $label = get_string('configdisplayothertime', 'block_use_stats');
    $desc = get_string('configdisplayothertime_desc', 'block_use_stats');
    $settings->add(new admin_setting_configcheckbox($key, $label, $desc, 1));

    $displayopts = array(DISPLAY_FULL_COURSE => get_string('displaycoursetime', 'block_use_stats'),
                         DISPLAY_TIME_ACTIVITIES => get_string('displayactivitiestime', 'block_use_stats'));

    $key = 'block_use_stats/displayactivitytimeonly';
    $label = get_string('configdisplayactivitytimeonly', 'block_use_stats');
    $desc = get_string('configdisplayactivitytimeonly_desc', 'block_use_stats');
    $settings->add(new admin_setting_configselect($key, $label, $desc, 0, $displayopts));

    $options = array('dhx_web' => 'web',
                     'dhx_blue' => 'blue',
                     'dhx_black' => 'black',
                     'dhx_skyblue' => 'skyblue',
                     'dhx_terrace' => 'terrace',
                     'omega' => 'omega');
    $key = 'block_use_stats/calendarskin';
    $label = get_string('configcalendarskin', 'block_use_stats');
    $desc = get_string('configcalendarskin_desc', 'block_use_stats');
    $settings->add(new admin_setting_configselect($key, $label, $desc, 'web', $options));

    $settings->add(new admin_setting_heading('loganalysisparams', get_string('loganalysisparams', 'block_use_stats'), ''));

    $key = 'block_use_stats/threshold';
    $label = get_string('configthreshold', 'block_use_stats');
    $desc = get_string('configthreshold_desc', 'block_use_stats');
    $settings->add(new admin_setting_configtext($key, $label, $desc, 60));

    $key = 'block_use_stats/onesessionpercourse';
    $label = get_string('configonesessionpercourse', 'block_use_stats');
    $desc = get_string('configonesessionpercourse_desc', 'block_use_stats');
    $settings->add(new admin_setting_configcheckbox($key, $label, $desc, 0));

    $key = 'block_use_stats/lastpingcredit';
    $label = get_string('configlastpingcredit', 'block_use_stats');
    $desc = get_string('configlastpingcredit_desc', 'block_use_stats');
    $settings->add(new admin_setting_configtext($key, $label, $desc, 15));

    $key = 'block_use_stats/enrolmentfilter';
    $label = get_string('configenrolmentfilter', 'block_use_stats');
    $desc = get_string('configenrolmentfilter_desc', 'block_use_stats');
    $settings->add(new admin_setting_configcheckbox($key, $label, $desc, 1));

    $key = 'block_use_stats/lastcompiled';
    $label = get_string('configlastcompiled', 'block_use_stats');
    $desc = get_string('configlastcompiled_desc', 'block_use_stats');
    $settings->add(new admin_setting_configdatetime($key, $label, $desc, ''));

    if (block_use_stats_supports_feature('emulate/community') == 'pro') {
        // This will accept any.
        include_once($CFG->dirroot.'/blocks/use_stats/pro/prolib.php');
        $promanager = block_use_stats\pro_manager::instance();
        $promanager->add_settings($ADMIN, $settings);
    } else {
        $label = get_string('plugindist', 'block_use_stats');
        $desc = get_string('plugindist_desc', 'block_use_stats');
        $settings->add(new admin_setting_heading('plugindisthdr', $label, $desc));
    }
}
