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

require_once($CFG->dirroot.'/blocks/use_stats/block_use_stats.php');

function block_use_stats_get_coursetable(&$aggregate, &$fulltotal, &$fullevents) {
    return block_use_stats::prepare_coursetable($aggregate, $fulltotal, $fullevents);
}

/**
 * Compact function to get full time calculation of a user in a course.
 * @param int $courseid
 * @param int $userid
 */
function block_use_stats_get_user_course_time($courseid, $userid) {
    use_stats_fix_last_course_access($userid, $courseid);
    $logs = use_stats_extract_logs(0, time(), $userid, $courseid);
    return use_stats_aggregate_logs($logs, 0, time());
}

function block_use_stats_x_format_time($timevalue) {
    return block_use_stats_format_time($timevalue);
}