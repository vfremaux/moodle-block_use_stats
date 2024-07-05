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
 * @package     block_use_stats
 * @categroy    blocks
 * @author      Valery Fremaux <valery.fremaux@gmail.com>
 * @copyright   Valery Fremaux <valery.fremaux@gmail.com> (MyLearningFactory.com)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_use_stats;
require_once($CFG->dirroot.'/blocks/use_stats/lib.php');

defined('MOODLE_INTERNAL') || die();

final class local_pro_manager {

    private static $component = 'block_use_stats';
    private static $componentpath = 'blocks/use_stats';

    /**
     * This adds additional settings to the component settings (generic part of the prolib system).
     * @param objectref &$admin
     * @param objectref &$settings
     */
    public static function add_settings(&$admin, &$settings) {
        global $CFG, $PAGE;

        if (block_use_stats_supports_feature('data/activetracking')) {
            $settings->add(new \admin_setting_heading('activetracking', get_string('activetrackingparams', 'block_use_stats'), ''));

            $key = 'block_use_stats/keepalive_enable';
            $label = get_string('configkeepaliveenable', 'block_use_stats');
            $desc = get_string('configkeepaliveenable_desc', 'block_use_stats');
            $settings->add(new \admin_setting_configcheckbox($key, $label, $desc, 0));

            $key = 'block_use_stats/keepalive_delay';
            $label = get_string('configkeepalivedelay', 'block_use_stats');
            $desc = get_string('configkeepalivedelay_desc', 'block_use_stats');
            $settings->add(new \admin_setting_configtext($key, $label, $desc, 600));

            $ctloptions = array();
            $ctloptions['0'] = get_string('allusers', 'block_use_stats');
            $ctloptions['allow'] = get_string('allowrule', 'block_use_stats');
            $ctloptions['deny'] = get_string('denyrule', 'block_use_stats');

            $key = 'block_use_stats/keepalive_rule';
            $label = get_string('configkeepaliverule', 'block_use_stats');
            $desc = get_string('configkeepaliverule_desc', 'block_use_stats');
            $settings->add(new \admin_setting_configselect($key, $label, $desc, 'deny', $ctloptions));

            $options = array();
            $options['capability'] = get_string('capabilitycontrol', 'block_use_stats');
            $options['profilefield'] = get_string('profilefieldcontrol', 'block_use_stats');

            $key = 'block_use_stats/keepalive_control';
            $label = get_string('configkeepalivecontrol', 'block_use_stats');
            $desc = get_string('configkeepalivecontrol_desc', 'block_use_stats');
            $settings->add(new \admin_setting_configselect($key, $label, $desc, 'capability', $options));

            $key = 'block_use_stats/keepalive_control_value';
            $label = get_string('configkeepalivecontrolvalue', 'block_use_stats');
            $desc = get_string('configkeepalivecontrolvalue_desc', 'block_use_stats');
            $settings->add(new \admin_setting_configtext($key, $label, $desc, 'moodle/site:config', PARAM_TEXT));
        }

        if (block_use_stats_supports_feature('data/multidimensionnal')) {
            $settings->add(new \admin_setting_heading('datacubing', get_string('datacubing', 'block_use_stats'), ''));

            $key = 'block_use_stats/enablecompilecube';
            $label = get_string('configenablecompilecube', 'block_use_stats');
            $desc = get_string('configenablecompilecube_desc', 'block_use_stats');
            $settings->add(new \admin_setting_configcheckbox($key, $label, $desc, ''));

            for ($i = 1; $i <= 6; $i++) {
                $key = "block_use_stats/customtag{$i}select";
                $label = get_string('configcustomtagselect', 'block_use_stats').' '.$i;
                $desc = get_string('configcustomtagselect_desc', 'block_use_stats', $i);
                $settings->add(new \admin_setting_configtext($key, $label, $desc, ''));
            }
        }
    }
}