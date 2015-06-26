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
 * Master block ckass for use_stats compiler
 *
 * @package    blocks
 * @subpackage use_stats
 * @author     Valery Fremaux (valery.fremaux@gmail.com)
 * @copyright  Valery Fremaux (valery.fremaux@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Extracts a log thread from the first accessible logstore
 * @param int $from
 * @param int $to
 * @param mixed $for a user ID or an array of user IDs
 * @param int $course a course object or array of courses
 */
function use_stats_extract_logs($from, $to, $for = null, $course = null) {
    global $CFG, $USER, $DB;

    $logmanger = get_log_manager();
    $readers = $logmanger->get_readers('\core\log\sql_select_reader');
    $reader = reset($readers);

    if (empty($reader)) {
        return false; // No log reader found.
    }

    if ($reader instanceof \logstore_standard\log\store) {
        $courseparm = 'courseid';
    } elseif($reader instanceof \logstore_legacy\log\store) {
        $courseparm = 'course';
    } else{
        return;
    }

    if (!isset($CFG->block_use_stats_lastpingcredit)) {
        set_config('block_use_stats_lastpingcredit', 5);
    }

    $for = (is_null($for)) ? $USER->id : $for ;

    if (is_array($for)) {
        $userlist = implode("','", $for);
        $userclause = " AND userid IN ('{$userlist}') ";
    } else {
        $userclause = " AND userid = {$for} ";
    }

    if (is_object($course) && !empty($course->id)) {
        $coursecontext = context_course::instance($course->id);

        // we search first enrol time for this user
        $sql = "
            SELECT
                id,
                MIN(timestart) as timestart
            FROM
                {role_assignments} ra
            WHERE
                contextid = $coursecontext->id
                $userclause
        ";
        $firstenrol = $DB->get_record_sql($sql);

        $from = max($from, $firstenrol->timestart);
    }

    $courseclause = '';
    if (is_object($course)) {

        if (!empty($course->id)) {
            $coursecontext = context_course::instance($course->id);

            // We search first enrol time for this user.
            $sql = "
                SELECT
                    id,
                    MIN(timestart) as timestart
                FROM
                    {role_assignments} ra
                WHERE
                    contextid = $coursecontext->id
                    $userclause
            ";
            $firstenrol = $DB->get_record_sql($sql);

            $from = max($from, $firstenrol->timestart);

            $courseclause = " AND {$courseparm} = $course->id " ;
        }

    } elseif (is_array($course)) {

        // Finish solving from value as MIN(firstassignement).

        foreach ($course as $c) {
            $cids[] = $c->id;
        }
        $courseclause = " AND {$courseparm} IN('".implode("','", $cids)."') ";

    }

    if ($reader instanceof \logstore_standard\log\store) {
        $sql = "
           SELECT
             id,
             courseid as course,
             action,
             timecreated as time,
             target as module,
             userid,
             objectid as cmid
           FROM
             {logstore_standard_log}
           WHERE
             timecreated > ? AND
             timecreated < ? AND
             ((courseid = 1 AND action = 'login') OR
              (1
              $courseclause))
            $userclause
           ORDER BY
             timecreated
        ";
    } elseif ($reader instanceof \logstore_legacy\log\store) {
        $sql = "
           SELECT
             id,
             course,
             action,
             time,
             module,
             userid,
             cmid
           FROM
             {log}
           WHERE
             time > ? AND
             time < ? AND
             ((course = 1 AND action = 'login') OR
              (1
              $courseclause))
            $userclause
           ORDER BY
             time
        ";
    } else {
    }

    if ($rs = $DB->get_recordset_sql($sql, array($from, $to))) {
        $logs = array();
        foreach ($rs as $log) {
            $logs[] = $log;
        }
        $rs->close($rs);
        return $logs;
    }
    return array();
}

/**
 * given an array of log records, make a displayable aggregate. Needs a single
 * user log extraction. User will be guessed out from log records.
 * @param array $logs
 * @param string $dimension
 */
function use_stats_aggregate_logs($logs, $dimension, $origintime = 0) {
    global $CFG, $DB, $OUTPUT, $USER, $COURSE;

    // will record session aggregation state as current session ordinal
    $sessionid = 0;

    if (!empty($CFG->block_use_stats_capturemodules)) {
        $modulelist = explode(',', $CFG->block_use_stats_capturemodules);
    }

    if (isset($CFG->block_use_stats_ignoremodules)) {
        $ignoremodulelist = explode(',', $CFG->block_use_stats_ignoremodules);
    } else {
        $ignoremodulelist = array();
    }

    $currentuser = 0;
    $automatondebug = optional_param('debug', 0, PARAM_BOOL);

    $aggregate = array();
    $aggregate['sessions'] = array();

    if (!empty($logs)) {
        $logs = array_values($logs);

        $memlap = 0; // will store the accumulated time for in the way but out of scope laps.

        for ($i = 0 ; $i < count($logs) ; $i++) {
            $log = $logs[$i];

            // We "guess" here the real identity of the log's owner.
            $currentuser = $log->userid;

            // Let's get lap time to next log in track
            if (isset($logs[$i + 1])) {
                $lognext = $logs[$i + 1];
                $lap = $lognext->time - $log->time;
            } else {
                $lap = $CFG->block_use_stats_lastpingcredit * MINSECS;
            }

            // Fix session breaks over the threshold time.
            $sessionpunch = false;
            if ($lap > $CFG->block_use_stats_threshold * MINSECS) {
                $lap = $CFG->block_use_stats_lastpingcredit * MINSECS;
                if ($lognext->action != 'login') {
                    $sessionpunch = true;
                }
            }

            // This is the most usual case...
            if ($dimension == 'module' && ($log->action != 'login')) {
                $continue = false;
                if (!empty($CFG->block_use_stats_capturemodules) && !in_array($log->$dimension, $modulelist)) {
                    // If not eligible module for aggregation, just add the intermediate laps.
                    $memlap = $memlap + $lap;
                    if ($automatondebug) {
                        mtrace("out 1 ");
                    }
                    $continue = true;
                }

                if (!empty($CFG->block_use_stats_ignoremodules) && in_array($log->$dimension, $ignoremodulelist)) {
                    // If ignored module for aggregations, just add the intermediate time.
                    $memlap = $memlap + $lap;
                    if ($automatondebug) {
                        mtrace("out 2 ");
                    }
                    $continue = true;
                }

                if ($continue) {
                    if ('login' == @$lognext->action) {
                        // We are the last action before a new login 
                        @$aggregate['sessions'][$sessionid]->elapsed += $lap + $memlap;
                        @$aggregate['sessions'][$sessionid]->sessionend = $log->time + $lap + $memlap;
                        $memlap = 0;
                        if ($automatondebug) {
                            echo(" <span style=\"background-color:#FF8080\">implicit logout on non elligible. next : {$lognext->action}</span> ".format_time($lap)." at ".userdate($log->time).' >> '.format_time($aggregate['sessions'][$sessionid]->elapsed).'<br/>');
                        }
                    }

                    continue;
                }
            }

            $lap = $lap + $memlap;
            $memlap = 0;

            if (!isset($log->$dimension)) {
                echo $OUTPUT->notification('unknown dimension');
            }

            // Per login session aggregation.

            // Repair inconditionally first visible session track that has no login
            if ($sessionid == 0) {
                if (!isset($aggregate['sessions'][0]->sessionstart)) {
                    if ($automatondebug) {
                        mtrace('First record repair</br>');
                    }
                    @$aggregate['sessions'][0]->sessionstart = $logs[0]->time;
                }
            }

            if ($automatondebug) {
                echo "$i<br/>";
                echo "current is $log->action<br/>";
            }
            // Next visible log is a login. So current session ends
            @$aggregate['sessions'][$sessionid]->courses[$log->course] = $log->course; // this will collect all visited course ids during this session
            if (($log->action != 'login') && ('login' == @$lognext->action)) {
                // We are the last action before a new login 
                @$aggregate['sessions'][$sessionid]->elapsed += $lap;
                @$aggregate['sessions'][$sessionid]->sessionend = $log->time + $lap;
                if ($automatondebug) {
                    echo(" <span style=\"background-color:#FF8080\">implicit logout. next : {$lognext->action}</span> ".format_time($lap)." at <span style=\"background-color:#f0f0f0\">".userdate($log->time).'</span> >> '.format_time($aggregate['sessions'][$sessionid]->elapsed).'<br/>');
                }
            } else {
                // all other cases : login or non login
                if ($log->action == 'login') {
                    // We are explicit login
                    if (@$lognext->action != 'login') {
                       $sessionid++;
                       @$aggregate['sessions'][$sessionid]->elapsed = $lap;
                       @$aggregate['sessions'][$sessionid]->sessionstart = $log->time;
                       if ($automatondebug) {
                            echo(" <span style=\"background-color:#80FF80\">login</span> ".format_time($lap)." at <span style=\"background-color:#f0f0f0\">".userdate($log->time).'</span> >> '.format_time($aggregate['sessions'][$sessionid]->elapsed).'<br/>');
                       }
                   } else {
                       if ($automatondebug) {
                            echo(" not true session. next : {$lognext->action} ".userdate($log->time).'<br/>');
                       }
                       // continue;
                   }
                } else {
                    // all other cases
                    if ($automatondebug) {
                        echo "punch : $sessionpunch and next is $lognext->action <br/>";
                    }
                    if ($sessionpunch || @$lognext->action == 'login') {
                        // this record is the last one of the current session.
                        @$aggregate['sessions'][$sessionid]->sessionend = $log->time + $lap;
                        @$aggregate['sessions'][$sessionid]->elapsed += $lap;
                        if ($automatondebug) {
                            $punch = ($sessionpunch) ? 'punchout' : '' ;
                            echo("$punch beforelogin $lap session end ".userdate($log->time).' +'.$CFG->block_use_stats_lastpingcredit.'mins <br/>');
                        }
                        if ($sessionpunch) {
                            // $logs[$i + 1]->action = 'login';
                            $sessionid++;
                            @$aggregate['sessions'][$sessionid]->sessionstart = $lognext->time;
                            @$aggregate['sessions'][$sessionid]->elapsed = 0;
                        }
                        // $sessionid++;
                        // @$aggregate['sessions'][$sessionid]->sessionstart = $lognext->time;
                        // @$aggregate['sessions'][$sessionid]->elapsed = $lap;
                    } else {
                        if (!isset($aggregate['sessions'][$sessionid])) {
                            @$aggregate['sessions'][$sessionid]->sessionstart = $log->time;
                            @$aggregate['sessions'][$sessionid]->elapsed = $lap;
                            if ($automatondebug) {
                                echo(" firstrecord ".format_time($lap)." ".userdate($log->time).'<br/>');
                            }
                        } else {
                            @$aggregate['sessions'][$sessionid]->elapsed += $lap;
                            if ($automatondebug) {
                                echo(" simple record ".format_time($lap)." at <span style=\"background-color:#f0f0f0\">".userdate($log->time).'</span> >> '.format_time($aggregate['sessions'][$sessionid]->elapsed).'<br/>');
                            }
                        }
                    }
                }
            }

            // Standard global lap aggregation.
            if (array_key_exists($log->$dimension, $aggregate) && array_key_exists($log->cmid, $aggregate[$logs[$i]->$dimension])){
                @$aggregate[$log->$dimension][$log->cmid]->elapsed += $lap;
                @$aggregate[$log->$dimension][$log->cmid]->events += 1;
                @$aggregate[$log->$dimension][$log->cmid]->firstaccess = $log->time;
                @$aggregate[$log->$dimension][$log->cmid]->lastaccess = $log->time;
            } else {
                @$aggregate[$log->$dimension][$log->cmid]->elapsed = $lap;
                @$aggregate[$log->$dimension][$log->cmid]->events = 1;
                @$aggregate[$log->$dimension][$log->cmid]->lastaccess = $log->time;
            }

            /// Standard in-activity level lap aggregation
            if ($log->cmid && !preg_match('/label$/', $log->$dimension) && ($log->$dimension != 'course')){
                if (array_key_exists('activities', $aggregate)){
                    @$aggregate['activities'][$log->course]->elapsed += $lap;
                    @$aggregate['activities'][$log->course]->events += 1;
                } else {
                    @$aggregate['activities'][$log->course]->elapsed = $lap;
                    @$aggregate['activities'][$log->course]->events = 1;
                }
            }

            /// Standard course level lap aggregation
            if (array_key_exists('coursetotal', $aggregate) && array_key_exists($log->course, $aggregate['coursetotal'])){
                @$aggregate['coursetotal'][$log->course]->elapsed += $lap;
                @$aggregate['coursetotal'][$log->course]->events += 1;
                @$aggregate['coursetotal'][$log->course]->firstaccess = $log->time;
                @$aggregate['coursetotal'][$log->course]->lastaccess = $log->time;
            } else {
                @$aggregate['coursetotal'][$log->course]->elapsed = $lap;
                @$aggregate['coursetotal'][$log->course]->events = 1;
                if (!isset($aggregate['coursetotal'][$log->course]->firstaccess)){
                    @$aggregate['coursetotal'][$log->course]->firstaccess = $log->time;
                }
                @$aggregate['coursetotal'][$log->course]->lastaccess = $log->time;
            }
            $origintime = $log->time;
        }
    }

    // finish last session
    @$aggregate['sessions'][$sessionid]->sessionend = $log->time + $lap;

    // this is our last change to guess a user when no logs available
    if (empty($currentuser)) $currentuser = optional_param('userid', $USER->id, PARAM_INT);
    
    // we need check if time credits are used and override by credit earned
    if (file_exists($CFG->dirroot.'/mod/learningtimecheck/xlib.php')){
        include_once($CFG->dirroot.'/mod/learningtimecheck/xlib.php');
        $checklists = learningtimecheck_get_instances($COURSE->id, true); // get timecredit enabled ones

        foreach ($checklists as $ckl) {
            if ($credittimes = learningtimecheck_get_credittimes($ckl->id, 0, $currentuser)){
                foreach ($credittimes as $credittime) {

                    // if credit time is assigned to NULL course module, we assign it to the checklist itself
                    if (!$credittime->cmid) {
                        $cklcm = get_coursemodule_from_instance('learningtimecheck', $ckl->id);
                        $credittime->cmid = $cklcm->id;
                    }

                    if (!empty($CFG->learningtimecheck_strict_credits)) {
                        // If strict credits, do override time even if real time is higher.
                        $aggregate[$credittime->modname][$credittime->cmid]->elapsed = $credittime->credittime;
                        $aggregate[$credittime->modname][$credittime->cmid]->timesource = 'credit';
                    } else {
                        // This processes validated modules that although have no logs.
                        if (!isset($aggregate[$credittime->modname][$credittime->cmid])) {
                            $aggregate[$credittime->modname][$credittime->cmid] = new StdClass;
                            $aggregate[$credittime->modname][$credittime->cmid]->elapsed = 0;
                            $aggregate[$credittime->modname][$credittime->cmid]->events = 0;
                            $aggregate[$credittime->modname][$credittime->cmid]->firstaccess = $log->time;
                            $aggregate[$credittime->modname][$credittime->cmid]->lastaccess = $log->time;
                        }
                        if ($aggregate[$credittime->modname][$credittime->cmid]->elapsed <= $credittime->credittime) {
                            $aggregate[$credittime->modname][$credittime->cmid]->elapsed = $credittime->credittime;
                            $aggregate[$credittime->modname][$credittime->cmid]->timesource = 'credit';
                            $aggregate[$credittime->modname][$credittime->cmid]->lastaccess = $log->time;
                        }
                    }
                }
            }

            if ($declarativetimes = learningtimecheck_get_declaredtimes($ckl->id, 0, $currentuser)) {
                foreach ($declarativetimes as $declaredtime) {

                    // if declared time is assigned to NULL course module, we assign it to the checklist itself
                    if (!$declaredtime->cmid) {
                        $cklcm = get_coursemodule_from_instance('learningtimecheck', $ckl->id);
                        $declaredtime->cmid = $cklcm->id;
                    }

                    if (!empty($CFG->checklist_strict_declared)) {
                        // If strict declared, do override time even if real time is higher.
                        $aggregate[$declaredtime->modname][$declaredtime->cmid]->elapsed = $declaredtime->declaredtime;
                        $aggregate[$declaredtime->modname][$declaredtime->cmid]->timesource = 'declared';
                    } else {
                        // This processes validated modules that although have no logs.
                        if (!isset($aggregate[$declaredtime->modname][$declaredtime->cmid])) {
                            $aggregate[$declaredtime->modname][$declaredtime->cmid] = new StdClass;
                            $aggregate[$declaredtime->modname][$declaredtime->cmid]->elapsed = 0;
                            $aggregate[$declaredtime->modname][$declaredtime->cmid]->events = 0;
                            $aggregate[$declaredtime->modname][$declaredtime->cmid]->firstaccess = $log->time;
                            $aggregate[$declaredtime->modname][$declaredtime->cmid]->lastaccess = 0;
                        }
                        if ($aggregate[$declaredtime->modname][$declaredtime->cmid]->elapsed <= $declaredtime->declaredtime) {
                            $aggregate[$declaredtime->modname][$declaredtime->cmid]->elapsed = $declaredtime->declaredtime;
                            $aggregate[$declaredtime->modname][$declaredtime->cmid]->timesource = 'declared';
                            $aggregate[$declaredtime->modname][$declaredtime->cmid]->lastaccess = $log->time;
                        }
                    }
                }
            }
        }
    }

    // we need finally adjust some times from time recording activities

    if (array_key_exists('scorm', $aggregate)) {
        foreach (array_keys($aggregate['scorm']) as $cmid) {
            if ($cm = $DB->get_record('course_modules', array('id'=> $cmid))) {
                // These are all scorms.

                // scorm activities have their accurate recorded time
                $realtotaltime = 0;
                if ($realtimes = $DB->get_records_select('scorm_scoes_track', " element = 'cmi.core.total_time' AND scormid = $cm->instance AND userid = $currentuser ",array(),'id,element,value')) {
                    foreach ($realtimes as $rt) {
                        $realcomps = preg_match("/(\d\d):(\d\d):(\d\d)\./", $rt->value, $matches);
                        $realtotaltime += $matches[1] * 3600 + $matches[2] * 60 + $matches[3];
                    }
                }
                if ($aggregate['scorm'][$cmid]->elapsed < $realtotaltime) {
                    $aggregate['scorm'][$cmid]->elapsed = $realtotaltime;
                }
            }
        }
    }

    return $aggregate;
}

/**
 * given an array of log records, make a displayable aggregate
 * @param array $logs
 * @param string $dimension
 */
function use_stats_aggregate_logs_per_user($logs, $dimension, $origintime = 0) {
    global $CFG, $DB, $OUTPUT;

    $config = get_config('block_use_stats');

    if (file_exists($CFG->dirroot.'/mod/learningtimecheck/xlib.php')) {
        $ltcconfig = get_config('learningtimecheck');
    }

    if (isset($config->capturemodules)) {
        $modulelist = explode(',', $config->capturemodules);
    }

    if (isset($config->ignoremodules)) {
        $ignoremodulelist = explode(',', $config->ignoremodules);
    } else {
        $ignoremodulelist = array();
    }

    $aggregate = array();

    if (!empty($logs)) {
        $logs = array_values($logs);

        // Will store the accumulated time for in the way but out of scope laps.
        $memlap = array();

        $end = count($logs) - 2;

        for ($i = 0 ; $i < $end ; $i++) {
            $userid = $logs[$i]->userid;
            $log[$userid] = $logs[$i];

            // Prepare aggregation for this user.
            if (!isset($aggregate[$userid])) {
                $aggregate[$userid] = array();
            }

            // we fetch the next receivable log for this user
            $j = $i + 1;
            while (($logs[$j]->userid != $userid) && $j < $end && (($logs[$j]->time - $log[$userid]->time) < $CFG->block_use_stats_threshold * MINSECS)) {
                $j++;
            }
            if ($j < $end && (($logs[$j]->time - $log[$userid]->time) < $CFG->block_use_stats_threshold * MINSECS)) {
                $lognext[$userid] = $logs[$j];
                $lap[$userid] = $lognext[$userid]->time - $log[$userid]->time;
            } else {
                $lap[$userid] = $config->lastpingcredit * MINSECS;
            }

            if ($lap[$userid] == 0) {
                continue;
            }

            // this is the most usual case...
            if (!isset($log[$userid]->$dimension)) {
                echo $OUTPUT->notification('unknown dimension');
            }

            if ($log[$userid]->dimension == 'module' && ($log[$userid]->action != 'login')) {
                $continue = false;
                if (!empty($config->capturemodules) && !in_array($log[$userid]->$dimension, $modulelist)) {
                    // If not eligible module for aggregation, just add the intermediate laps.
                $memlap[$userid] = $memlap[$userid] + $lap[$userid];
                    if ($automatondebug) {
                        mtrace("out 1 ");
                    }
                    $continue = true;
                }

                if (!empty($config->ignoremodules) && in_array($log[$userid]->$dimension, $ignoremodulelist)) {
                    // If ignored module for aggregations, just add the intermediate time.
                    $memlap[$userid] = $memlap[$userid] + $lap[$userid];
                    if ($automatondebug) {
                        mtrace("out 2 ");
                    }
                    $continue = true;
                }

                if ($continue) {
                    if ('login' == @$lognext->action) {
                        // We are the last action before a new login 
                        @$aggregate[$userid]['sessions'][$sessionid]->elapsed += $lap[$userid] + $memlap[$userid];
                        @$aggregate[$userid]['sessions'][$sessionid]->sessionend = $log[$userid]->time + $lap[$userid] + $memlap[$userid];
                        $memlap[$userid] = 0;
                        if ($automatondebug) {
                            echo(" <span style=\"background-color:#FF8080\">implicit logout on non elligible. next : {$lognext[$userid]->action}</span> ".format_time($lap[$userid])." at ".userdate($log[$userid]->time).' >> '.format_time($aggregate[$userid]['sessions'][$sessionid]->elapsed).'<br/>');
                        }
                    }

                    continue;
                }
            }

            $lap[$userid] = $lap[$userid] + $memlap[$userid];
            $memlap[$userid] = 0;

           /// Standard global lap aggregation
            if (array_key_exists($log[$userid]->$dimension, $aggregate[$userid]) && array_key_exists($log[$userid]->cmid, $aggregate[$userid][$logs[$i]->$dimension])){
                $aggregate[$userid][$log[$userid]->$dimension][$log[$userid]->cmid]->elapsed += $lap[$userid];
                $aggregate[$userid][$log[$userid]->$dimension][$log[$userid]->cmid]->events += 1;
                if (!isset($aggregate[$userid][$log[$userid]->$dimension][$log[$userid]->cmid]->firstaccess)){
                    $aggregate[$userid][$log[$userid]->$dimension][$log[$userid]->cmid]->firstaccess = $log->time;
                }
                $aggregate[$userid][$log[$userid]->$dimension][$log[$userid]->cmid]->lastaccess = $log->time;
            } else {
                $aggregate[$userid][$log[$userid]->$dimension][$log[$userid]->cmid]->elapsed = $lap[$userid];
                $aggregate[$userid][$log[$userid]->$dimension][$log[$userid]->cmid]->events = 1;
                if (!isset($aggregate[$userid][$log[$userid]->$dimension][$log[$userid]->cmid]->firstaccess)){
                    $aggregate[$userid][$log[$userid]->$dimension][$log[$userid]->cmid]->firstaccess = $log->time;
                }
                $aggregate[$userid][$log[$userid]->$dimension][$log[$userid]->cmid]->lastaccess = $log->time;
            }

           // Per login session aggregation.
           if ($log[$userid]->action != 'login' && @$lognext[$userid]->action == 'login'){
               $aggregate[$userid]['sessions'][$sessionid]->sessionend = $log[$userid]->time + ($config->lastpingcredit * MINSECS);
           }
           if ($log[$userid]->action == 'login') {
               if (@$lognext[$userid]->action != 'login') {
                   $sessionid = 0 + @$sessionid + 1;
                   $aggregate[$userid]['sessions'][$sessionid]->elapsed = 0; // do not use first login time
                   $aggregate[$userid]['sessions'][$sessionid]->sessionstart = $log[$userid]->time;
               }
           } else {
               if (!isset($aggregate['sessions'][$sessionid])){
                   $aggregate[$userid]['sessions'][$sessionid]->sessionstart = $log[$userid]->time;
                   $aggregate[$userid]['sessions'][$sessionid]->elapsed = $lap[$userid];
               } else {
                   $aggregate[$userid]['sessions'][$sessionid]->elapsed += $lap[$userid];
               }
            }

            // We need check if time credits are used and override by credit earned.
            if (file_exists($CFG->dirroot.'/mod/learningtimecheck/xlib.php')) {
                include_once($CFG->dirroot.'/mod/learningtimecheck/xlib.php');
                $checklists = learningtimecheck_get_instances($COURSE->id, true); // get timecredit enabled ones

                foreach ($checklists as $ckl) {
                    if ($credittimes = learningtimecheck_get_credittimes($ckl->id, 0, $userid)) {
                        foreach ($credittimes as $credittime) {

                            // if credit time is assigned to NULL course module, we assign it to the checklist itself
                            if (!$credittime->cmid){
                                $cklcm = get_coursemodule_from_instance('learningtimecheck', $ckl->id);
                                $credittime->cmid = $cklcm->id;
                            }

                            if (!empty($ltcconfig->strict_credits)) {
                                // if strict credits, do override time even if real time is higher 
                                $aggregate[$userid][$credittime->modname][$credittime->cmid]->elapsed = $credittime->credittime;
                                $aggregate[$userid][$credittime->modname][$credittime->cmid]->timesource = 'credit';
                            } else {
                                // this processes validated modules that although have no logs
                                if (!isset($aggregate[$userid][$credittime->modname][$credittime->cmid])){
                                    $aggregate[$userid][$credittime->modname][$credittime->cmid] = new StdClass;
                                    $aggregate[$credittime->modname][$credittime->cmid]->elapsed = 0;
                                    $aggregate[$credittime->modname][$credittime->cmid]->events = 0;
                                    if (!isset($aggregate[$credittime->modname][$credittime->cmid]->firstaccess)){
                                        $aggregate[$credittime->modname][$credittime->cmid]->firstaccess = $log[$userid]->time;
                                    }
                                    $aggregate[$credittime->modname][$credittime->cmid]->lastaccess = $log[$userid]->time;
                                }
                                if (@$aggregate[$userid][$credittime->modname][$credittime->cmid]->elapsed <= $credittime->credittime){
                                    $aggregate[$userid][$credittime->modname][$credittime->cmid]->elapsed = $credittime->credittime;
                                    $aggregate[$userid][$credittime->modname][$credittime->cmid]->timesource = 'credit';
                                    if (!isset($aggregate[$userid][$credittime->modname][$credittime->cmid]->lastaccess)){
                                        $aggregate[$userid][$credittime->modname][$credittime->cmid]->lastaccess = $log[$userid]->time;
                                    }
                                    $aggregate[$userid][$credittime->modname][$credittime->cmid]->lastaccess = $log[$userid]->time;
                                }
                            }
                        }
                    }

                    if ($declarativetimes = learningtimecheck_get_declaredtimes($ckl->id, 0, $userid)) {
                        foreach ($declarativetimes as $declaredtime) {

                            // if declared time is assigned to NULL course module, we assign it to the checklist itself
                            if (!$declaredtime->cmid) {
                                $cklcm = get_coursemodule_from_instance('learningtimecheck', $ckl->id);
                                $declaredtime->cmid = $cklcm->id;
                            }

                            if (!empty($ltcconfig->strict_declared)) {
                                // if strict declared, do override time even if real time is higher 
                                $aggregate[$userid][$declaredtime->modname][$declaredtime->cmid]->elapsed = $declaredtime->declaredtime;
                                $aggregate[$userid][$declaredtime->modname][$declaredtime->cmid]->timesource = 'declared';
                                if (!isset($aggregate[$userid][$declaredtime->modname][$declaredtime->cmid]->firstaccess)){
                                    $aggregate[$userid][$declaredtime->modname][$declaredtime->cmid]->firstaccess = $log->time;
                                }
                                $aggregate[$userid][$declaredtime->modname][$declaredtime->cmid]->lastaccess = $log->time;
                            } else {
                                // this processes validated modules that although have no logs
                                if (!isset($aggregate[$userid][$declaredtime->modname][$declaredtime->cmid])) {
                                    $aggregate[$userid][$declaredtime->modname][$declaredtime->cmid] = new StdClass;
                                    $aggregate[$userid][$declaredtime->modname][$declaredtime->cmid]->elapsed = 0;
                                    $aggregate[$userid][$declaredtime->modname][$declaredtime->cmid]->events = 0;
                                    if (!isset($aggregate[$userid][$declaredtime->modname][$declaredtime->cmid]->firstaccess)) {
                                        $aggregate[$userid][$declaredtime->modname][$declaredtime->cmid]->firstaccess = $log->time;
                                    }
                                    $aggregate[$userid][$declaredtime->modname][$declaredtime->cmid]->lastaccess = $log->time;
                                }
                                if ($aggregate[$userid][$declaredtime->modname][$declaredtime->cmid]->elapsed <= $declaredtime->declaredtime) {
                                    $aggregate[$userid][$declaredtime->modname][$declaredtime->cmid]->elapsed = $declaredtime->declaredtime;
                                    $aggregate[$userid][$declaredtime->modname][$declaredtime->cmid]->timesource = 'declared';
                                    if (!isset($aggregate[$userid][$declaredtime->modname][$declaredtime->cmid]->firstaccess)){
                                        $aggregate[$userid][$declaredtime->modname][$declaredtime->cmid]->firstaccess = $log->time;
                                    }
                                    $aggregate[$userid][$declaredtime->modname][$declaredtime->cmid]->lastaccess = $log->time;
                                }
                            }
                        }
                    }
                }
            }

            // $origintime = $log[$userid]->time;
        }
    }
    return $aggregate;
}

/**
 * this new function uses the log storage enhancement with precalculated gaps
 * in order to extract multicourse time aggregations
 * @param object ref $result to be filled in
 * @param string $from
 * @param string $to
 * @param string $users
 * @param string $courses
 * @param string $dimensions
 */
function use_stats_site_aggregate_time(&$result, $from = 0, $to = 0, $users = null, $courses = null, $dimensions = 'course,user,institution') {
    global $CFG, $COURSE, $DB;

    $config = get_config('use_stats');

    // make quick accessible memory variables to test
    $dimensionsarr = explode(',', $dimensions);
    $courseresult = in_array('course', $dimensionsarr);
    $userresult = in_array('user', $dimensionsarr);
    $institutionresult = in_array('institution', $dimensionsarr);

    if ($to == 0) {
        $to = time;
    }

    $userclause = '';
    if (!empty($users)) {
        $userclause = ' AND userid IN ('.implode(',', $users).' )';
    }

    $courseclause = '';
    if (!empty($courses)) {
        $courseclause = ' AND course IN ('.implode(',', $courses).' )';
    }

    $sql = "
        SELECT
            l.id,
            l.time,
            l.userid,
            l.course,
            usl.gap,
            u.institution,
            u.department,
            u.city,
            u.country
        FROM
            {log} l,
            {use_stats_log} usl,
            {user} u
        WHERE
            u.id = l.userid AND
            time >= ? AND
            time <= ?
            $courseclause
            $userclause
    ";

    // pre_loop structure inits
    if ($institutionresult) {
        $result->institutions = array();
        $institutionid = 1;
    }
    
    if (!isset($config->threshold)) {
        set_config('threshold', 15, 'block_use_stats');
        $config->threshold = 15;
    }

    if (!isset($config->lastpingcredit)) {
        set_config('lastpingcredit', 15, 'block_use_stats');
        $config->lastpingcredit = 15;
    }

    $threshold = 15 * MINSECS;
    $lastpingcredit = 15 * MINSECS;

    $rs = get_recordset_sql($sql, array($from, $to));
    if ($rs) {

        while ($rs->valid()) {
            $gap = $rs->current();

            if ($gap->gap > $threshold) {
                $gap->gap = $lastpingcredit;
            }

            // overall
            @$result->all->hits += 1;
            @$result->all->elapsed += $gap->gap;
            if (!isset($result->all->firsthit)) $result->all->firsthit = $gap->time; 
            $result->all->lasthit = $gap->time; 

            // course detail
            if ($courseresult) {
                @$result->course[$gap->course]->hits += 1; 
                @$result->course[$gap->course]->elapsed += $gap->gap; 
                if (!isset($result->course[$gap->course]->firsthit)) $result->all->firsthit = $gap->time; 
                $result->course[$gap->course]->lasthit = $gap->time; 
            }

            // user detail
            if ($userresult) {
                @$result->user[$gap->userid]->hits += 1; 
                @$result->user[$gap->userid]->elapsed += $gap->gap; 
                if (!isset($result->user[$gap->userid]->firsthit)) $result->user[$gap->userid]->firsthit = $gap->time; 
                $result->user[$gap->userid]->lasthit = $gap->time;
            }

            // user detail
            if ($institutionresult){
                if (!array_key_exists($gap->institution, $result->institutions)) {
                    $result->institutions[$gap->institution] = $institutionid;
                }
                $gapinstitutionid = $result->institutions[$gap->institution];
                @$result->institution[$gapinstitutionid]->hits += 1;
                @$result->institution[$gapinstitutionid]->elapsed += $gap->gap;
                if (!isset($result->institution[$gapinstitutionid]->firsthit)) {
                    $result->institution[$gapinstitutionid]->firsthit = $gap->time;
                }
                $result->institution[$gapinstitutionid]->lasthit = $gap->time;
            }
            $rs->next();
        }
    $rs->close();
    }
}

/**
 * for debuggin purpose only
 */
function use_stats_render($sessions) {
    if ($sessions) {
        foreach ($sessions as $s) {
            echo userdate(@$s->sessionstart).' / '.userdate(@$s->sessionend).' / '.floor(@$s->elapsed / 60). ':'.(@$s->elapsed % 60).' diff('.(@$s->sessionend - @$s->sessionstart).'='.@$s->elapsed.') <br/>';
        }
    }
}