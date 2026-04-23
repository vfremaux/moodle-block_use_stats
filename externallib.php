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
<<<<<<< HEAD
 * @package     block_use_stats
 * @author      Valery Fremaux (valery.fremaux@gmail.com)
 * @copyright  Valery Fremaux (valery.fremaux@gmail.com)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
=======
 * Web service implementation.
 * @package    block_use_stats
 * @author     Valery Fremaux (valery.fremaux@gmail.com)
 * @copyright  Valery Fremaux (valery.fremaux@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
>>>>>>> MOODLE_405_STABLE
 *
 * This file is a proxy class to the "pro" real implementation of moodle web services.
 * Web services will be actually registered in all distributions.
 */
defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/blocks/use_stats/locallib.php');
require_once($CFG->dirroot.'/blocks/use_stats/lib.php');

/**
<<<<<<< HEAD
 * Standard WS definition.
=======
 * Standard Web service exposition.
 * Web service are delegated to Pro Zone, but needs standard exposition to
 * be registered in Moodle.
>>>>>>> MOODLE_405_STABLE
 */
class block_use_stats_external extends external_api {

    /**
<<<<<<< HEAD
     * Parameters for get user stats
=======
     * Public wrapper to Parameters.
>>>>>>> MOODLE_405_STABLE
     */
    public static function get_user_stats_parameters() {

        return new external_function_parameters (
            [
                'uidsource' => new external_value(PARAM_ALPHA, 'Source for user id '),
                'uid' => new external_value(PARAM_TEXT, 'User identifier'),
                'cidsource' => new external_value(PARAM_ALPHA, 'course id', VALUE_DEFAULT, 'idnumber', true),
                'cid' => new external_value(PARAM_TEXT, 'Course identifier', VALUE_DEFAULT, 0),
                'from' => new external_value(PARAM_INT, 'period start timestamp', VALUE_DEFAULT, 0, true),
                'to' => new external_value(PARAM_INT, 'period end timestamp', VALUE_DEFAULT, 0, true),
                'score' => new external_value(PARAM_INT, 'Get course score back', VALUE_DEFAULT, 1, true),
            ]
        );
    }

    /**
<<<<<<< HEAD
     * Gets user's stats
     * @param string $uidsource
     * @param mixed $uid
     * @param string $cidsource
     * @param mixed $cid
     * @param int $from
     * @param int $to
     * @param int $score
=======
     * Get stats for a user
     * @param string $uidsource source field of the user identifier
     * @param string|int $uid a user identifier depending on uidsource value.
     * @param string $cidsource source field of the course
     * @param string|int $cid identifier of the course, depending on cidsource value
     * @param int $from
     * @param int $to
     * @param boolean $score
>>>>>>> MOODLE_405_STABLE
     */
    public static function get_user_stats($uidsource, $uid, $cidsource, $cid, $from, $to, $score = 0) {
        global $CFG;

        if (block_use_stats_supports_feature('api/ws')) {
            include_once($CFG->dirroot.'/blocks/use_stats/pro/externallib.php');
            return block_use_stats_external_extended::get_user_stats($uidsource, $uid, $cidsource, $cid, $from, $to, $score);
        }

        throw new moodle_exception('WS Not available in this distribution');
    }

    /**
<<<<<<< HEAD
     * Returns definition
=======
     * Public exposition of Return.
>>>>>>> MOODLE_405_STABLE
     */
    public static function get_user_stats_returns() {
        return new external_single_structure(
            [
                'user' => new external_single_structure(
                    [
                        'id' => new external_value(PARAM_INT, 'User id'),
                        'idnumber' => new external_value(PARAM_TEXT, 'User idnumber'),
                        'username' => new external_value(PARAM_TEXT, 'User username'),
                    ]
                ),

                'query' => new external_single_structure(
                    [
                        'from' => new external_value(PARAM_INT, 'From date'),
                        'to' => new external_value(PARAM_INT, 'To date'),
                    ]
                ),

                'sessions' => new external_single_structure(
                    [
                        'sessions' => new external_value(PARAM_INT, 'Number of sessions', VALUE_OPTIONAL, 0, true),
                        'firstsession' => new external_value(PARAM_INT, 'First session date', VALUE_OPTIONAL, 0, true),
                        'lastsession' => new external_value(PARAM_INT, 'Last session date', VALUE_OPTIONAL, 0, true),
                        'sessionmin' => new external_value(PARAM_INT, 'Min session duration', VALUE_OPTIONAL, 0, true),
                        'sessionmax' => new external_value(PARAM_INT, 'Max session duration', VALUE_OPTIONAL, 0, true),
                        'meansession' => new external_value(PARAM_INT, 'Mean session duration', VALUE_OPTIONAL, 0, true),
                    ]
                ),

                'courses' => new external_multiple_structure(
                    new external_single_structure(
                        [
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
<<<<<<< HEAD
                       ]
=======
                        ]
>>>>>>> MOODLE_405_STABLE
                    )
                ),
            ]
        );
    }

    /* *************************** Simple bulk users response ************************* */

    /**
     * Same input API than get_users_stats()
     * Only response structure changes
     */
    public static function get_users_course_stats_parameters() {
        return self::get_users_stats_parameters();
    }

    /**
<<<<<<< HEAD
     * Get stats for a course
     * @param string $uidsource
     * @param mixed $uids
     * @param string $cidsource
     * @param mixed $cid
     * @param int $from
     * @param int $to
     * àparam int $score
     */
    public static function get_users_course_stats($uidsource, $uids, $cidsource, $cid, $from, $to, $score) {
=======
     * Public wrapper to Pro zone.
     */
    public static function get_users_course_stats($uidsource, $uids, $cidsource,
            $cid, $from, $to, $score) {
>>>>>>> MOODLE_405_STABLE
        global $CFG;

        if (block_use_stats_supports_feature('api/ws')) {
            include_once($CFG->dirroot.'/blocks/use_stats/pro/externallib.php');
            return block_use_stats_external_extended::get_users_course_stats($uidsource, $uids, $cidsource,
                    $cid, $from, $to, $score);
        }

        throw new moodle_exception('WS Not available in this distribution');
    }

    /**
<<<<<<< HEAD
     * Return description for course stats.
=======
     * Public wrapper to Return.
>>>>>>> MOODLE_405_STABLE
     */
    public static function get_users_course_stats_returns() {
        return new external_multiple_structure(
            self::get_user_course_stats_returns()
        );
    }

    /* *************************** Bulk data ************************* */
<<<<<<< HEAD

    /**
     * Parameters description for multiple users stats.
=======
    /**
     * Parameters for get users.
>>>>>>> MOODLE_405_STABLE
     */
    public static function get_users_stats_parameters() {

        return new external_function_parameters (
            [
                'uidsource' => new external_value(PARAM_ALPHA, 'The source for user identifier'),
                'uids' => new external_multiple_structure(
                     new external_value(PARAM_TEXT, 'an uid')
                 ),
                'cidsource' => new external_value(PARAM_ALPHA, 'course id', VALUE_DEFAULT, 'idnumber', true),
                'cid' => new external_value(PARAM_TEXT, 'Course identifier', VALUE_DEFAULT, 0),
                'from' => new external_value(PARAM_INT, 'period start timestamp', VALUE_DEFAULT, 0, true),
                'to' => new external_value(PARAM_INT, 'period end timestamp', VALUE_DEFAULT, 0, true),
                'score' => new external_value(PARAM_BOOL, 'Get course score bask', VALUE_DEFAULT, true, true),
            ]
        );
    }

    /**
<<<<<<< HEAD
     * Returns stats for a bunch of users. This might be costfull
     * @param string $uidsource
     * @param mixed $uids
     * @param string $cidsource
     * @param mixed $cid
     * @param int $from
     * @param int $to
     * @param int $score
=======
     * Get user stats, public wrapper to pro
     * @param string $uidsource source field of the user identifier
     * @param array $uids an array of user identifiers depending on uidsource value.
     * @param string $cidsource source field of the course
     * @param string|int $cid identifier of the course, depending on cidsource value
     * @param int $from
     * @param int $to
     * @param boolean $score
>>>>>>> MOODLE_405_STABLE
     */
    public static function get_users_stats($uidsource, $uids, $cidsource, $cid, $from, $to, $score) {
        global $CFG;

        if (block_use_stats_supports_feature('api/ws')) {
            include_once($CFG->dirroot.'/blocks/use_stats/pro/externallib.php');
            return block_use_stats_external_extended::get_users_stats($uidsource, $uids, $cidsource, $cid, $from, $to, $score);
        }

        throw new moodle_exception('WS Not available in this distribution');
    }

    /**
<<<<<<< HEAD
     * Return description for multiple users stats.
=======
     * Public wrapper to Return.
>>>>>>> MOODLE_405_STABLE
     */
    public static function get_users_stats_returns() {
        return new external_multiple_structure(
            self::get_user_stats_returns()
        );
    }

    /* *************** Common functions ******************* */

    /**
<<<<<<< HEAD
     * Return description for multiple user_course stats
=======
     * Public wrapper to Return.
>>>>>>> MOODLE_405_STABLE
     */
    protected static function get_user_course_stats_returns() {
        return new external_single_structure(
            [
                'user' => new external_single_structure(
                    [
                        'id' => new external_value(PARAM_INT, 'User id'),
                        'idnumber' => new external_value(PARAM_TEXT, 'User idnumber'),
                        'username' => new external_value(PARAM_TEXT, 'User username'),
                    ]
                ),

                'coursedata' => new external_single_structure(
                    [
                        'id' => new external_value(PARAM_INT, 'Course id'),
                        'idnumber' => new external_value(PARAM_TEXT, 'Course idnumber'),
                        'shortname' => new external_value(PARAM_TEXT, 'Course shortname'),
                        'fullname' => new external_value(PARAM_TEXT, 'Course fullname'),
                        'activitytime' => new external_value(PARAM_INT, 'Elapsed time in activities'),
                        'coursetime' => new external_value(PARAM_INT, 'Elapsed time in course outside activities'),
                        'coursetotal' => new external_value(PARAM_INT, 'Elapsed time in course (all times)'),
                        'othertime' => new external_value(PARAM_INT, 'Elapsed time in system areas'),
                        'sitecoursetime' => new external_value(PARAM_INT, 'Elapsed time in site course during session'),
                        'firstsession' => new external_value(PARAM_INT, 'First session date', VALUE_OPTIONAL),
                        'lastsession' => new external_value(PARAM_INT, 'Last session date', VALUE_OPTIONAL),
                        'score' => new external_value(PARAM_TEXT, 'Final course grade', VALUE_OPTIONAL),
                    ]
                ),
            ]
        );
    }
}
