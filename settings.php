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

defined('MOODLE_INTERNAL') || die;

/**
 * @package    block_use_stats
 * @category   blocks
 * @author     Valery Fremaux (valery.fremaux@gmail.com)
 * @copyright  Valery Fremaux (valery.fremaux@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once $CFG->dirroot.'/blocks/use_stats/adminlib.php';

use \block\use_stats\admin_setting_configdatetime;

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_configtext('block_use_stats/fromwhen', get_string('configfromwhen', 'block_use_stats'),
                       get_string('configfromwhen_desc', 'block_use_stats'), 90));

    $settings->add(new admin_setting_configtext('block_use_stats/filterdisplayunder', get_string('configfilterdisplayunder', 'block_use_stats'),
                       get_string('configfilterdisplayunder_desc', 'block_use_stats'), 60));

    $settings->add(new admin_setting_configcheckbox('block_use_stats/displayothertime', get_string('configdisplayothertime', 'block_use_stats'),
                       get_string('configdisplayothertime_desc', 'block_use_stats'), 1));

    $settings->add(new admin_setting_configtext('block_use_stats/capturemodules', get_string('configcapturemodules', 'block_use_stats'),
                       get_string('configcapturemodules_desc', 'block_use_stats'), ''));

    $settings->add(new admin_setting_configtext('block_use_stats/ignoremodules', get_string('configignoremodules', 'block_use_stats'),
                       get_string('configignoremodules_desc', 'block_use_stats'), ''));

    $settings->add(new admin_setting_configtext('block_use_stats/threshold', get_string('configthreshold', 'block_use_stats'),
                       get_string('configthreshold_desc', 'block_use_stats'), 60));

    $settings->add(new admin_setting_configtext('block_use_stats/lastpingcredit', get_string('configlastpingcredit', 'block_use_stats'),
                       get_string('configlastpingcredit_desc', 'block_use_stats'), 15));

    $settings->add(new admin_setting_configcheckbox('block_use_stats/enablecompilecube', get_string('configenablecompilecube', 'block_use_stats'),
                       get_string('configenablecompilecube_desc', 'block_use_stats'), ''));

    for ($i = 1 ; $i <= 6 ; $i++) {
        $configkey = "block_use_stats/customtag{$i}select";
        $settings->add(new admin_setting_configtext($configkey, get_string('configcustomtagselect', 'block_use_stats').' '.$i,
                           get_string('configcustomtagselect_desc', 'block_use_stats', $i), ''));
    }

    $settings->add(new admin_setting_configdatetime('block_use_stats/lastcompiled', get_string('configlastcompiled', 'block_use_stats'),
                       get_string('configlastcompiled_desc', 'block_use_stats'), ''));

    $settings->add(new admin_setting_heading('activetracking', get_string('activetrackingparams', 'block_use_stats'), ''));

    $settings->add(new admin_setting_configtext('block_use_stats/keepalive_delay', get_string('configkeepalivedelay', 'block_use_stats'),
                   get_string('configkeepalivedelay_desc', 'block_use_stats'), 600));

    $ctloptions = array();
    $ctloptions['0'] = get_string('allusers', 'block_use_stats');
    $ctloptions['allow'] = get_string('allowrule', 'block_use_stats');
    $ctloptions['deny'] = get_string('denyrule', 'block_use_stats');

    $settings->add(new admin_setting_configselect('block_use_stats/keepalive_rule', get_string('configkeepaliverule', 'block_use_stats'),
                   get_string('configkeepaliverule_desc', 'block_use_stats'), 'deny', $ctloptions));

    $options = array();
    $options['capability'] = get_string('capabilitycontrol', 'block_use_stats');
    $options['profilefield'] = get_string('profilefieldcontrol', 'block_use_stats');
    $settings->add(new admin_setting_configselect('block_use_stats/keepalive_control', get_string('configkeepalivecontrol', 'block_use_stats'),
                   get_string('configkeepalivecontrol_desc', 'block_use_stats'), 'capability', $options));

    $settings->add(new admin_setting_configtext('block_use_stats/keepalive_control_value', get_string('configkeepalivecontrolvalue', 'block_use_stats'),
                   get_string('configkeepalivecontrolvalue_desc', 'block_use_stats'), 'moodle/site:config', PARAM_TEXT));
}