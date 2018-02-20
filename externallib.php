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
 * @category    blocks
 * @author      Valery Fremaux (valery.fremaux@gmail.com)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * This file is a proxy class to the "pro" real implementation of moodle web services.
 * Web services will be actually registered in all distributions.
 */
defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/blocks/use_stats/locallib.php');

class block_use_stats_external extends external_api {

    public static function get_user_stats_parameters() {

        return new external_function_parameters (
            array(
                'uidsource' => new external_value(PARAM_ALPHA, 'Source for user id '),
                'uid' => new external_value(PARAM_TEXT, 'User identifier'),
                'cidsource' => new external_value(PARAM_ALPHA, 'course id', VALUE_DEFAULT, 'idnumber', true),
                'cid' => new external_value(PARAM_TEXT, 'Course identifier', VALUE_DEFAULT, 0),
                'from' => new external_value(PARAM_INT, 'period start timestamp', VALUE_DEFAULT, 0, true),
                'to' => new external_value(PARAM_INT, 'period end timestamp', VALUE_DEFAULT, 0, true),
                'score' => new external_value(PARAM_INT, 'Get course score back', VALUE_DEFAULT, 1, true),
            )
        );
    }

    public static function get_user_stats($uidsource, $uid, $cidsource, $cid, $from, $to, $score = 0) {
        global $DB, $CFG;

        if (block_use_stats_supports_feature('api/ws')) {
            include_once($CFG->dirroot.'/blocks/use_stats/pro/externallib.php');
            return block_use_stats_external_extended::get_user_stats($uidsource, $uid, $cidsource, $cid, $from, $to, $score);
        }

        throw new moodle_exception('WS Not available in this distribution');
    }

    public static function get_user_stats_returns() {
        return new external_single_structure(
            array(
                'user' => new external_single_structure(
                    array(
                        'id' => new external_value(PARAM_INT, 'User id'),
                        'idnumber' => new external_value(PARAM_TEXT, 'User idnumber'),
                        'username' => new external_value(PARAM_TEXT, 'User username'),
                    )
                ),

                'query' => new external_single_structure(
                    array(
                        'from' => new external_value(PARAM_INT, 'From date'),
                        'to' => new external_value(PARAM_INT, 'To date'),
                    )
                ),

                'sessions' => new external_single_structure(
                    array(
                        'sessions' => new external_value(PARAM_INT, 'Number of sessions', VALUE_OPTIONAL, 0, true),
                        'firstsession' => new external_value(PARAM_INT, 'First session date', VALUE_OPTIONAL, 0, true),
                        'lastsession' => new external_value(PARAM_INT, 'Last session date', VALUE_OPTIONAL, 0, true),
                        'sessionmin' => new external_value(PARAM_INT, 'Min session duration', VALUE_OPTIONAL, 0, true),
                        'sessionmax' => new external_value(PARAM_INT, 'Max session duration', VALUE_OPTIONAL, 0, true),
                        'meansession' => new external_value(PARAM_INT, 'Mean session duration', VALUE_OPTIONAL, 0, true),
                    )
                ),

                'courses' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'id' => new external_value(PARAM_INT, 'Course id'),
                            'idnumber' => new external_value(PARAM_TEXT, 'Course idnumber'),
                            'shortname' => new external_value(PARAM_TEXT, 'Course shortname'),
                            'fullname' => new external_value(PARAM_TEXT, 'Course fullname'),
                            'activitytime' => new external_value(PARAM_INT, 'Elapsed time in activities'),
                            'coursetime' => new external_value(PARAM_INT, 'Elapsed time in course outside activities'),
                            'coursetotal' => new external_value(PARAM_INT, 'Elapsed time in course (all times)'),
                            'othertime' => new external_value(PARAM_INT, 'Elapsed time in system areas'),
                            'sitecoursetime' => new external_value(PARAM_INT, 'Elapsed time in site course during session'),
                            'score' => new external_value(PARAM_TEXT, 'Final course grade', VALUE_OPTIONAL),
                       )
                    )
                ),
            )
        );
    }

    /* *************************** Bulk data ************************* */

    public static function get_users_stats_parameters() {

        $statsfields = 'elapsed,events,courseelapsed,courseevents,otherelapsed,otherevents';

        return new external_function_parameters (
            array(
                'uidsource' => new external_value(PARAM_ALPHA, 'The source for user identifier'),
                'uids' => new external_multiple_structure(
                     new external_value(PARAM_TEXT, 'an uid')
                 ),
                'cidsource' => new external_value(PARAM_ALPHA, 'course id', VALUE_DEFAULT, 'idnumber', true),
                'cid' => new external_value(PARAM_TEXT, 'Course identifier', VALUE_DEFAULT, 0),
                'from' => new external_value(PARAM_INT, 'period start timestamp', VALUE_DEFAULT, 0, true),
                'to' => new external_value(PARAM_INT, 'period end timestamp', VALUE_DEFAULT, 0, true),
                'score' => new external_value(PARAM_BOOL, 'Get course score bask', VALUE_DEFAULT, true, true),
            )
        );
    }

    public static function get_users_stats($uidsource, $uids, $cidsource, $cid, $from, $to, $score) {

        if (block_use_stats_supports_feature('api/ws')) {
            include_once($CFG->dirroot.'/blocks/use_stats/pro/externallib.php');
            return block_use_stats_external_extended::get_users_stats($uidsource, $uids, $cidsource, $cid, $from, $to, $score);
        }

        throw new moodle_exception('WS Not available in this distribution');
    }

    public static function get_users_stats_returns() {
        return new external_multiple_structure(
            self::get_user_stats_returns()
        );
    }
}