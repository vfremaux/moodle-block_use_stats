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
 * Library for theme notifications.
 *
 * @package   block_livedesk
 * @version   moodle 2.2 to 2.5
 * @copyright 2012 Wafa Adham,, Valery Fremaux
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

function block_use_stats_setup_theme_requires() {
    global $PAGE, $CFG;

    $PAGE->requires->jquery();
}

function block_use_stats_setup_theme_notification() {
    global $CFG, $USER, $COURSE, $DB, $PAGE;

    if (!isloggedin()) {
        return;
    }

    $cm = $PAGE->cm;
    $config = get_config('block_use_stats');

    // Control for adding the code to the footer. This saves performance with non concerned users.
    if (!empty($config->keepalive_rule)) {
        $notallowed = false;
        if (@$config->keepalive_control == 'capability') {
            if (has_capability($config->keepalive_control_value, context_system::instance())) {
                if ($config->keepalive_rule == 'deny') {
                    $notallowed = true;
                }
            } else {
                if ($config->keepalive_rule == 'allow') {
                    $notallowed = true;
                }
            }
        } elseif (@$config->keepalive_control == 'profilefield') {
            $profilefield = $DB->get_record('user_info_field', array('shortname' => @$config->keepalive_control_value));
            $profilevalue = $DB->get_record('user_info_data', array('userid' => $USER->id, 'fieldid' => @$profilefield->id));
            if ($profilevalue && empty($profilevalue->data)) {
                if ($config->keepalive_rule == 'deny') {
                    $notallowed = true;
                }
            } else {
                if ($config->keepalive_rule == 'allow') {
                    $notallowed = true;
                }
            }
        }

        if ($notallowed) {
            return;
        }
    }

    if (!is_null($cm)) {
        return "<script src=\"{$CFG->wwwroot}/blocks/use_stats/js/notif_keepalive.php?id={$COURSE->id}&cmid={$cm->id}\"></script>"; 
    } else {
        return "<script src=\"{$CFG->wwwroot}/blocks/use_stats/js/notif_keepalive.php?id={$COURSE->id}\"></script>"; 
    }
}