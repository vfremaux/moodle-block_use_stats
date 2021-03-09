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
 * Master block class for use_stats compiler
 *
 * @package    blocks_use_stats
 * @category   blocks
 * @author     Valery Fremaux (valery.fremaux@gmail.com)
 * @copyright  Valery Fremaux (valery.fremaux@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

if (!function_exists('debug_trace')) {
    // Protect foreign implementations of missing tracing tools.
    function debug_trace() {
    }
}

define('DISPLAY_FULL_COURSE', 0);
define('DISPLAY_TIME_ACTIVITIES', 1);

function use_stats_get_reader() {
    return '\core\log\sql_reader';
}

/**
 * Extracts a log thread from the first accessible logstore
 * @param int $from
 * @param int $to
 * @param mixed $for a user ID or an array of user IDs
 * @param int $course a course object or array of courses
 */
function use_stats_extract_logs($from, $to, $for = null, $course = null) {
    global $USER, $DB;

    $config = get_config('block_use_stats');

    $logmanager = get_log_manager();
    $readers = $logmanager->get_readers(use_stats_get_reader());
    $reader = reset($readers);

    if (empty($reader)) {
        return false; // No log reader found.
    }

    if ($reader instanceof \logstore_standard\log\store) {
        $courseparm = 'courseid';
    } else if ($reader instanceof \logstore_legacy\log\store) {
        $courseparm = 'course';
    } else {
        return;
    }

    if (!isset($config->lastpingcredit)) {
        set_config('lastpingcredit', 15, 'block_use_stats');
        $config->lastpingcredit = 15;
    }

    $for = (is_null($for)) ? $USER->id : $for;

    if (is_array($for)) {
        $userlist = implode("','", $for);
        $userclause = " AND userid IN ('{$userlist}') ";
    } else {
        $userclause = " AND userid = {$for} ";
    }

    $courseclause = '';
    $courseenrolclause = '';
    $inparams = array();

    if (!empty($config->enrolmentfilter)) {
        // We search first enrol time still active for this user.
        $sql = "
            SELECT
                MIN(timestart) as timestart
            FROM
                {enrol} e,
                {user_enrolments} ue
            WHERE
                $courseenrolclause
                e.id = ue.enrolid AND
                (ue.timeend = 0 OR ue.timeend > ".time().")
                $userclause
        ";
        $firstenrol = $DB->get_record_sql($sql, $inparams);

        $from = max($from, $firstenrol->timestart);
    }

    if ($reader instanceof \logstore_standard\log\store) {
        $sql = "
           SELECT
             id,
             courseid as course,
             action,
             target,
             timecreated as time,
             userid,
             contextid,
             contextinstanceid,
             contextlevel
           FROM
             {logstore_standard_log}
           WHERE
             origin != 'cli' AND
             timecreated > ? AND
             timecreated < ? AND
             action != 'failed' AND
             ((courseid = 0 AND action = 'loggedin') OR
             (courseid = 0 AND action = 'loggedout') OR
              (1=1
              $courseclause))
            $userclause AND realuserid IS NULL
           ORDER BY
             timecreated
        ";
    } else if ($reader instanceof \logstore_legacy\log\store) {
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
             (course = 1 AND action = 'logout') OR
              (1=1
              $courseclause))
            $userclause
           ORDER BY
             time
        ";
    } else {
        assert(false);
        // External DB logs is NOT supported.
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
 * @param string $from
 * @param string $to
 * @param string $progress
 * @param string $nosessions
 * @param string $currentcourse // for use with learningtimecheck module only, to integrate time overrides from LTC credit time model.
 */
function use_stats_aggregate_logs($logs, $from = 0, $to = 0, $progress = '', $nosessions = false, $currentcourse = null) {
    global $CFG, $DB, $OUTPUT, $USER, $COURSE;
    static $CMSECTIONS = array();

    if (is_null($currentcourse)) {
        $currentcourse = $COURSE;
    }

    $backdebug = 0;
    $dimension = 'module';

    $config = get_config('block_use_stats');
    if (file_exists($CFG->dirroot.'/mod/learningtimecheck/xlib.php')) {
        $ltcconfig = get_config('learningtimecheck');
    }

    // Will record session aggregation state as current session ordinal.
    $sessionid = 0;

    if (!empty($config->capturemodules)) {
        $modulelist = explode(',', $config->capturemodules);
    }

    if (isset($config->ignoremodules)) {
        $ignoremodulelist = explode(',', $config->ignoremodules);
    } else {
        $ignoremodulelist = array();
    }

    $threshold = (0 + @$config->threshold) * MINSECS;
    $lastpingcredit = (0 + @$config->lastpingcredit) * MINSECS;

    $currentuser = 0;
    $automatondebug = optional_param('debug', 0, PARAM_BOOL) && (is_siteadmin() || !empty($USER->realuser));

    $aggregate = array();
    $aggregate['sessions'] = array();

    $logmanager = get_log_manager();
    $readers = $logmanager->get_readers(use_stats_get_reader());
    $reader = reset($readers);

    $logbuffer = '';
    $lastcourseid  = 0;

    if (!empty($logs)) {
        $logs = array_values($logs);

        $memlap = 0; // Will store the accumulated time for in the way but out of scope laps.

        $logsize = count($logs);

        if ($logsize > 15000) {
            raise_memory_limit(MEMORY_EXTRA);
        }

        for ($i = 0; $i < $logsize; $i = $nexti) {

            if ($progress) {
                $logbuffer .= "\r".str_replace('%%PROGRESS%%', '('.(0 + @$nexti).'/'.$logsize.')', $progress);
            }

            $log = $logs[$i];
            // We "guess" here the real identity of the log's owner.
            $currentuser = $log->userid;

            // Let's get lap time to next log in track.
            $nexti = $i + 1;
            $lognext = false;
            if (isset($logs[$i + 1])) {
                /*
                 * Fetch ahead possible jumps over some non significant logs
                 * that will be counted within the current log context.
                 */
                list($lognext, $lap, $nexti) = use_stats_fetch_ahead($logs, $i, $reader);
            } else {
                // Cap the lastpingcredit to "passed" time only.
                $endtime = $log->time + $lastpingcredit;
                $now = time();
                if ($endtime < $now) {
                    $lap = $lastpingcredit;
                } else {
                    $lap = $lastpingcredit - ($endtime - $now);
                }
            }

            // Adjust "module" for new logstore if using the standard log. Do it only for current course.
            // Other logs deeper info not needed.
            if ($reader instanceof \logstore_standard\log\store) {
                if (($log->course == $currentcourse->id) || ($log->contextlevel != CONTEXT_MODULE)) {
                    use_stats_add_module_from_context($log);
                } else {
                    $log->module = 'outoftargetcourse';
                    $log->cmid = 0;
                }
            }

            $isactionlogin = block_use_stats_is_login_event($log->action);
            $isnextactionlogin = block_use_stats_is_login_event(@$lognext->action);

            // Fix session breaks over the threshold time.
            $sessionpunch = false;
            if ($lap > $threshold) {

                $endtime = $log->time + $lastpingcredit;
                $now = time();
                if ($endtime < $now) {
                    $lap = $lastpingcredit;
                } else {
                    $lap = $lastpingcredit - ($endtime - $now);
                }

                if ($lognext && !$isnextactionlogin) {
                    $sessionpunch = true;
                }
            }

            if ($automatondebug || $backdebug) {
                if ($log->module != 'course') {
                    $logbuffer .= "[S-$sessionid/$log->id:{$log->module}>{$log->cmid}:";
                } else {
                    $logbuffer .= "[S-$sessionid/$log->id:course>{$log->course}:";
                }
                $logbuffer .= "{$log->action}] (".date('Y-m-d H:i:s', $log->time)." | $lap) ";
            }

            // Discard unsignificant cases.
            if (block_use_stats_is_logout_event($log->action)) {
                @$aggregate['sessions'][$sessionid]->elapsed += $memlap;
                @$aggregate['sessions'][$sessionid]->sessionend = $log->time;
                $memlap = 0;
                if ($automatondebug || $backdebug) {
                    $logbuffer .= " ... (X) finish session on clean loggout\n";
                }
                continue;
            }

            if ($log->$dimension == 'system' and $log->action == 'failed') {
                $memlap = 0;
                continue;
            }

            // This is the most usual case...
            if ($dimension == 'module' && !$isactionlogin) {
                $continue = false;
                if (!empty($config->capturemodules) && !in_array($log->$dimension, $modulelist)) {
                    // If not eligible module for aggregation, just add the intermediate laps.
                    $memlap = $memlap + $lap;
                    if ($automatondebug || $backdebug) {
                        $logbuffer .= " ... (I) Not in accepted, time lapped \n";
                    }
                    $continue = true;
                }

                if (!empty($config->ignoremodules) && in_array($log->$dimension, $ignoremodulelist)) {
                    // If ignored module for aggregations, just add the intermediate time.
                    $memlap = $memlap + $lap;
                    if ($automatondebug || $backdebug) {
                        $logbuffer .= " ... (I) Ignored, time lapped \n";
                    }
                    $continue = true;
                }

                if ($continue) {
                    if ($lognext && $isnextactionlogin) {
                        // We are the last action before a new login.
                        @$aggregate['sessions'][$sessionid]->elapsed += $lap + $memlap;
                        @$aggregate['sessions'][$sessionid]->sessionend = $log->time + $lap + $memlap;
                        $memlap = 0;
                        if ($automatondebug || $backdebug) {
                            $logbuffer .= " ... (X) finish session. Implicit logout on non elligible : Next is $lognext->action\n";
                        }
                    }
                    continue;
                }
            }

            $lap = $lap + $memlap;
            $memlap = 0;

            if (!empty($dimension) && !isset($log->$dimension)) {
                echo $OUTPUT->notification('unknown dimension');
            }

            // Per login session aggregation.

            // Repair inconditionally first visible session track that has no login.
            $preinit = false;
            if ($sessionid == 0) {
                if (!isset($aggregate['sessions'][0]->sessionstart)) {
                    if ($automatondebug || $backdebug) {
                        $logbuffer .= 'Initiating session 0 / First record repair ';
                    }
                    @$aggregate['sessions'][0]->sessionstart = $logs[0]->time;
                    $preinit = true;
                }
            }

            // Next visible log is a login. So current session ends.
            // This will collect all visited course ids during this session.
            @$aggregate['sessions'][$sessionid]->courses[$log->course] = $log->course;
            // If "one session per course" option is on, then there should be only one item here.

            if (!$isactionlogin && $isnextactionlogin) {
                // We are the last action before a new login.
                @$aggregate['sessions'][$sessionid]->elapsed += $lap;
                @$aggregate['sessions'][$sessionid]->sessionend = $log->time + $lap;
                if ($automatondebug || $backdebug) {
                    $logbuffer .= " ... (X) finish session. Implicit logout. next : {$lognext->action}\n";
                }
            } else {
                // All other cases : login or non login.
                if ($isactionlogin) {
                    // We are explicit login.
                    if ($lognext && !$isnextactionlogin) {
                        if (!$preinit || $sessionid) {
                            // Not session 0, must increment.
                            if ($automatondebug || $backdebug) {
                                $logbuffer .= " ... increment ... ";
                            }
                            $preinit = false;
                            $sessionid++;
                        }
                        @$aggregate['sessions'][$sessionid]->elapsed = $lap;
                        @$aggregate['sessions'][$sessionid]->sessionstart = $log->time;
                        if ($automatondebug || $backdebug) {
                            $logbuffer .= " ... (O) login. Next : {$lognext->action}. Start session\n";
                        }
                    } else {
                        if ($automatondebug || $backdebug) {
                            $logbuffer .= " ... (O) not true session next : {$lognext->action}. ignoring\n";
                        }
                        continue;
                    }
                } else {
                    // All other cases.

                    /*
                     * Adding Sadge's proposal of per course punching.
                     * Check "one session per course" option and punch if changing course
                     */
                    if (!empty($config->onesessionpercourse)) {
                        if ($lastcourseid && ($lastcourseid != $log->course)) {
                            $sessionpunch = true;
                        }
                    }

                    if ($automatondebug || $backdebug) {
                        if ($sessionpunch) {
                            $logbuffer .= " ... (P) session punch in : {$lognext->action} ";
                        }
                    }
                    if ($sessionpunch || !$lognext || $isnextactionlogin) {
                        // This record is the last one of the current session.
                        @$aggregate['sessions'][$sessionid]->sessionend = $log->time + $lap;
                        @$aggregate['sessions'][$sessionid]->elapsed += $lap;
                        if ($automatondebug || $backdebug) {
                            $logbuffer .= " ... before a login, finish session ";
                        }
                        if ($sessionpunch &&
                                (!$isnextactionlogin &&
                                        (@$lognext->action != 'failed'))) {
                            $sessionid++;
                            @$aggregate['sessions'][$sessionid]->sessionstart = $lognext->time;
                            @$aggregate['sessions'][$sessionid]->elapsed = 0;
                            if ($automatondebug || $backdebug) {
                                $logbuffer .= " ... start simulated session.\n";
                            }
                        } else {
                            if ($automatondebug || $backdebug) {
                                $logbuffer .= "\n";
                            }
                        }
                    } else {
                        if (!isset($aggregate['sessions'][$sessionid])) {
                            @$aggregate['sessions'][$sessionid]->sessionstart = $log->time;
                            @$aggregate['sessions'][$sessionid]->elapsed = $lap;
                            if ($automatondebug || $backdebug) {
                                $logbuffer .= " ... first session record\n";
                            }
                        } else {
                            $printabletime = block_use_stats_format_time(0 + @$aggregate['sessions'][$sessionid]->elapsed);
                            @$aggregate['sessions'][$sessionid]->elapsed += $lap;
                            if ($automatondebug || $backdebug) {
                                $logbuffer .= " ... simple record adding $lap >> ".$printabletime."\n";
                            }
                        }
                    }
                    // Register course in sesssion. This will serve when registering session in DB cache.
                    @$aggregate['sessions'][$sessionid]->courses[$log->course] = 1;
                }
            }

            // Standard global lap aggregation.
            if ($log->$dimension == 'course') {
                if (array_key_exists(''.$log->$dimension, $aggregate) &&
                        array_key_exists($log->course, $aggregate[$log->$dimension])) {
                    @$aggregate['course'][$log->course]->elapsed += $lap;
                    @$aggregate['course'][$log->course]->events += 1;
                    @$aggregate['course'][$log->course]->lastaccess = $log->time;
                } else {
                    @$aggregate['course'][$log->course]->elapsed = $lap;
                    @$aggregate['course'][$log->course]->events = 1;
                    @$aggregate['course'][$log->course]->firstaccess = $log->time;
                    @$aggregate['course'][$log->course]->lastaccess = $log->time;
                }
            } else {
                if (array_key_exists(''.$log->$dimension, $aggregate) &&
                        array_key_exists($log->cmid, $aggregate[$log->$dimension])) {
                    @$aggregate[$log->$dimension][$log->cmid]->elapsed += $lap;
                    @$aggregate[$log->$dimension][$log->cmid]->events += 1;
                    @$aggregate[$log->$dimension][$log->cmid]->lastaccess = $log->time;
                } else {
                    @$aggregate[$log->$dimension][$log->cmid]->elapsed = $lap;
                    @$aggregate[$log->$dimension][$log->cmid]->events = 1;
                    @$aggregate[$log->$dimension][$log->cmid]->firstaccess = $log->time;
                    @$aggregate[$log->$dimension][$log->cmid]->lastaccess = $log->time;
                }
            }

            // Standard non course level aggregation.
            if ($log->$dimension != 'course') {
                if ($log->cmid) {
                    $key = 'activities';
                    if (!array_key_exists($log->cmid, $CMSECTIONS)) {
                        $CMSECTIONS[$log->cmid] = $DB->get_field('course_modules', 'section', array('id' => $log->cmid));
                    }
                } else {
                    $key = 'other';
                }
                if (array_key_exists($key, $aggregate) && array_key_exists($log->course, $aggregate[$key])) {
                    $aggregate[$key][$log->course]->elapsed += $lap;
                    $aggregate[$key][$log->course]->events += 1;
                } else {
                    $aggregate[$key][$log->course] = new StdClass();
                    $aggregate[$key][$log->course]->elapsed = $lap;
                    $aggregate[$key][$log->course]->events = 1;
                }

                if ($key == 'activities') {
                    // Aggregate by section.
                    $sectionid = $CMSECTIONS[0 + $log->cmid];
                    if (array_key_exists('section', $aggregate) && is_numeric($sectionid) && array_key_exists($sectionid, $aggregate['section'])) {
                        $aggregate['section'][$sectionid]->elapsed += $lap;
                        $aggregate['section'][$sectionid]->events += 1;
                        $aggregate['realsection'][$sectionid]->elapsed += $lap;
                        $aggregate['realsection'][$sectionid]->events += 1;
                    } else {
                        $aggregate['section'][$sectionid] = new StdClass();
                        $aggregate['section'][$sectionid]->elapsed = $lap;
                        $aggregate['section'][$sectionid]->events = 1;
                        $aggregate['realsection'][$sectionid] = new StdClass();
                        $aggregate['realsection'][$sectionid]->elapsed = $lap;
                        $aggregate['realsection'][$sectionid]->events = 1;
                    }
                }
            }

            // Standard course level lap aggregation.
            if (array_key_exists('coursetotal', $aggregate) && array_key_exists($log->course, $aggregate['coursetotal'])) {
                @$aggregate['coursetotal'][$log->course]->elapsed += $lap;
                @$aggregate['coursetotal'][$log->course]->events += 1;
                @$aggregate['coursetotal'][$log->course]->firstaccess = $log->time;
                @$aggregate['coursetotal'][$log->course]->lastaccess = $log->time;
            } else {
                @$aggregate['coursetotal'][$log->course]->elapsed = $lap;
                @$aggregate['coursetotal'][$log->course]->events = 1;
                if (!isset($aggregate['coursetotal'][$log->course]->firstaccess)) {
                    @$aggregate['coursetotal'][$log->course]->firstaccess = $log->time;
                }
                @$aggregate['coursetotal'][$log->course]->lastaccess = $log->time;
            }
            $origintime = $log->time;
            $lastcourseid = $log->course;
        }
    }

    // Check assertions.
    if (!empty($aggregate['coursetotal'])) {
        foreach (array_keys($aggregate['coursetotal']) as $courseid) {
            $c = @$aggregate['course'][$courseid]->events;
            $a = @$aggregate['activities'][$courseid]->events;
            $o = @$aggregate['other'][$courseid]->events;
            if ($aggregate['coursetotal'][$courseid]->events != $c + $a + $o) {
                echo "Bad sumcheck on events for course $courseid <br/>";
            }
            $c = @$aggregate['course'][$courseid]->elapsed;
            $a = @$aggregate['activities'][$courseid]->elapsed;
            $o = @$aggregate['other'][$courseid]->elapsed;
            if ($aggregate['coursetotal'][$courseid]->elapsed != $c + $a + $o) {
                echo "Bad sumcheck on time for course $courseid <br/>";
            }
        }
    }

    if (!$nosessions) {

        $params = array('userid' => $currentuser);
        $sessionsids = array();
        $usersessions = array();
        if ($allsessions = $DB->get_records('block_use_stats_session', $params, 'sessionstart', 'id,sessionstart,sessionend')) {
            foreach ($allsessions as $sess) {
                $usersessions[] = $sess->sessionstart;
                $sessionsids[$sess->sessionstart] = $sess;
            }
        }

        // Finish last session.
        if (!empty($aggregate['sessions'])) {
            $lastkey = @array_pop(array_keys($aggregate['sessions']));
            if (!empty($lastkey)) {
                @$aggregate['sessions'][$lastkey]->sessionend = $log->time + $lap;
            }
        }

        // Explicit session dates.
        if (!empty($aggregate['sessions'])) {
            foreach ($aggregate['sessions'] as $sessid => $session) {
                $aggregate['sessions'][$sessid]->start = date('Y-m-d H:i:s', 0 + @$session->sessionstart);
                $aggregate['sessions'][$sessid]->end = date('Y-m-d H:i:s', 0 + @$session->sessionend);
                $dt = block_use_stats_format_time(@$session->sessionend - @$session->sessionstart);
                $aggregate['sessions'][$sessid]->duration = $dt;
            }

            // Store sessions in base.
            foreach ($aggregate['sessions'] as $session) {
                if (empty($session->sessionstart)) {
                    continue;
                }

                $transaction = $DB->start_delegated_transaction();
                $params = array('sessionstart' => 0 + $session->sessionstart,
                                'sessionend' => 0 + @$session->sessionend,
                                'userid' => $currentuser);
                $oldrec = $DB->get_record('block_use_stats_session', $params, '*', IGNORE_MULTIPLE);
                if (empty($oldrec)) {
                    $rec = new StdClass;
                    $rec->userid = $currentuser;
                    $rec->sessionstart = $session->sessionstart;
                    $rec->sessionend = 0 + @$session->sessionend;
                    if (!empty($session->courses)) {
                        $rec->courses = implode(',', array_keys($session->courses));
                    }
                    $DB->insert_record('block_use_stats_session', $rec);
                } else {
                    if (!empty($session->sessionend) && ($session->sessionend > @$sessionsids[$session->sessionstart]->sessionend)) {
                        $oldrec->sessionend = $session->sessionend;
                        $DB->update_record('block_use_stats_session', $oldrec);
                    }
                }
                $transaction->allow_commit();
            }
        }
    }

    // This is our last change to guess a user when no logs available.
    if (empty($currentuser)) {
        $currentuser = optional_param('userid', $USER->id, PARAM_INT);
    }

    // We need check if time credits are used and override by credit earned.
    if (file_exists($CFG->dirroot.'/mod/learningtimecheck/xlib.php')) {
        include_once($CFG->dirroot.'/mod/learningtimecheck/xlib.php');
        $checklists = learningtimecheck_get_instances($currentcourse->id, true); // Get timecredit enabled ones.

        if ($checklists) {
            foreach ($checklists as $ckl) {
                if ($currentuser == 0) {
                    continue;
                }
                if ($credittimes = learningtimecheck_get_credittimes($ckl->id, 0, $currentuser)) {

                    foreach ($credittimes as $credittime) {

                        if (empty($credittime->enablecredit)) {
                            continue;
                        }

                        // If credit time is assigned to NULL course module, we assign it to the checklist itself.
                        if (!$credittime->cmid) {
                            $cklcm = get_coursemodule_from_instance('learningtimecheck', $ckl->id);
                            $credittime->cmid = $cklcm->id;
                        }
                        $sectionid = @$CMSECTIONS[$credittime->cmid];

                        $cond = 0;

                        if (!empty($ltcconfig->strictcredits)) {
                            /*
                             * If strict credits, the reported time cannot be higher to the standard time credit for the
                             * item. The user is credited with the real time if lower then the credit, or the credit
                             * as peak cutoff. This does not care of the item being checked or not.
                             */
                            if (isset($aggregate[$credittime->modname][$credittime->cmid])) {
                                if ($ltcconfig->strictcredits == 1) {
                                    $cond = ($credittime->credittime >= $aggregate[$credittime->modname][$credittime->cmid]->elapsed) &&
                                                !empty($aggregate[$credittime->modname][$credittime->cmid]->elapsed);
                                } else if ($ltcconfig->strictcredits == 2) {
                                    // if credit is under real time, apply credit.
                                    $cond = $credittime->credittime <= $aggregate[$credittime->modname][$credittime->cmid]->elapsed;
                                } else {
                                    // Apply cedit anyway.
                                    if (!empty($aggregate[$credittime->modname][$credittime->cmid]->elapsed)) {
                                        $cond = 1;
                                    }
                                }
                            } else {
                                // Course module counter never initialized, but it has been marked as completed.
                                if ($credittime->credittime && $credittime->ismarked) {
                                    $cond = 1;
                                    $aggregate[$credittime->modname][$credittime->cmid] = new StdClass;
                                    $aggregate[$credittime->modname][$credittime->cmid]->elapsed = 0;
                                    $aggregate[$credittime->modname][$credittime->cmid]->events = 0;
                                }
                            }

                            if ($cond) {
                                // Cut over with credit time.
                                $diff = $credittime->credittime - @$aggregate[$credittime->modname][$credittime->cmid]->elapsed;
                                @$aggregate[$credittime->modname][$credittime->cmid]->real = @$aggregate[$credittime->modname][$credittime->cmid]->elapsed;
                                @$aggregate[$credittime->modname][$credittime->cmid]->elapsed = $credittime->credittime;
                                @$aggregate[$credittime->modname][$credittime->cmid]->timesource = 'credit';

                                // Fix the global aggregators accordingly.
                                @$aggregate['coursetotal'][$ckl->course]->elapsed += $diff;
                                @$aggregate['activities'][$ckl->course]->elapsed += $diff;
                                @$aggregate['section'][$sectionid]->elapsed += $diff;
                            } else {
                                if (!empty($credittime->credittime)) {
                                    $aggregate[$credittime->modname][$credittime->cmid]->credit = $credittime->credittime;
                                }
                            }
                        } else {
                            if ($credittime->ismarked) {

                                // This processes validated modules that although have no logs.
                                if (!isset($aggregate[$credittime->modname][$credittime->cmid])) {
                                    // Initiate value.
                                    $diff = $credittime->credittime;
                                    $aggregate[$credittime->modname][$credittime->cmid] = new StdClass;
                                    $aggregate[$credittime->modname][$credittime->cmid]->elapsed = 0;
                                    $aggregate[$credittime->modname][$credittime->cmid]->events = 0;
                                    $fa = @$aggregate[$credittime->modname][$credittime->cmid]->firstaccess;
                                    $aggregate[$credittime->modname][$credittime->cmid]->firstaccess = $fa;
                                    $aggregate[$credittime->modname][$credittime->cmid]->lastaccess = 0;

                                    // Fix the global aggregators accordingly.
                                    @$aggregate['coursetotal'][$ckl->course]->elapsed += $diff;
                                    @$aggregate['activities'][$ckl->course]->elapsed += $diff;
                                    @$aggregate['section'][$sectionid]->elapsed += $diff;
                                }

                                if ($aggregate[$credittime->modname][$credittime->cmid]->elapsed <= $credittime->credittime) {
                                    // Override value if not enough spent time.
                                    $diff = $credittime->credittime - $aggregate[$credittime->modname][$credittime->cmid]->elapsed;
                                    $aggregate[$credittime->modname][$credittime->cmid]->elapsed = $credittime->credittime;
                                    $aggregate[$credittime->modname][$credittime->cmid]->timesource = 'credit';
                                    $fa = @$aggregate[$credittime->modname][$credittime->cmid]->lastaccess;
                                    $aggregate[$credittime->modname][$credittime->cmid]->lastaccess = $fa;

                                    // Fix the global aggregators accordingly.
                                    @$aggregate['coursetotal'][$ckl->course]->elapsed += $diff;
                                    @$aggregate['activities'][$ckl->course]->elapsed += $diff;
                                    @$aggregate['section'][$sectionid]->elapsed += $diff;
                                }
                            }
                        }
                    }
                }

                if ($declarativetimes = learningtimecheck_get_declaredtimes($ckl->id, 0, $currentuser)) {
                    foreach ($declarativetimes as $declaredtime) {

                        // If declared time is assigned to NULL course module, we assign it to the checklist itself.
                        if (!$declaredtime->cmid) {
                            $cklcm = get_coursemodule_from_instance('learningtimecheck', $ckl->id);
                            $declaredtime->cmid = $cklcm->id;
                        }

                        if (!empty($ltcconfig->strict_declared)) {
                            // If strict declared, do override time even if real time is higher.
                            $diff = $declaredtime->declaredtime - $aggregate[$declaredtime->modname][$declaredtime->cmid]->elapsed;
                            $aggregate[$declaredtime->modname][$declaredtime->cmid]->elapsed = $declaredtime->declaredtime;
                            $aggregate[$declaredtime->modname][$declaredtime->cmid]->timesource = 'declared';

                            // Fix the global aggregators accordingly.
                            @$aggregate['coursetotal'][$ckl->course]->elapsed += $diff;
                            @$aggregate['activities'][$ckl->course]->elapsed += $diff;
                            @$aggregate['section'][$sectionid]->elapsed += $diff;
                        } else {
                            // This processes validated modules that although have no logs.
                            if (!isset($aggregate[$declaredtime->modname][$declaredtime->cmid])) {
                                $diff = $declaredtime->declaredtime;
                                $aggregate[$declaredtime->modname][$declaredtime->cmid] = new StdClass;
                                $aggregate[$declaredtime->modname][$declaredtime->cmid]->elapsed = 0;
                                $aggregate[$declaredtime->modname][$declaredtime->cmid]->events = 0;
                                $fa = @$aggregate[$credittime->modname][$credittime->cmid]->firstaccess;
                                $aggregate[$declaredtime->modname][$declaredtime->cmid]->firstaccess = $fa;
                                $aggregate[$declaredtime->modname][$declaredtime->cmid]->lastaccess = 0;

                                // Fix the global aggregators accordingly.
                                @$aggregate['coursetotal'][$ckl->course]->elapsed += $diff;
                                @$aggregate['activities'][$ckl->course]->elapsed += $diff;
                                @$aggregate['section'][$sectionid]->elapsed += $diff;
                            }
                            if ($aggregate[$declaredtime->modname][$declaredtime->cmid]->elapsed <= $declaredtime->declaredtime) {
                                $aggregate[$declaredtime->modname][$declaredtime->cmid]->elapsed = $declaredtime->declaredtime;
                                $aggregate[$declaredtime->modname][$declaredtime->cmid]->timesource = 'declared';
                                $fa = @$aggregate[$credittime->modname][$credittime->cmid]->lastaccess;
                                $aggregate[$declaredtime->modname][$declaredtime->cmid]->lastaccess = $fa;

                                // Fix the global aggregators accordingly.
                                @$aggregate['coursetotal'][$ckl->course]->elapsed += $diff;
                                @$aggregate['activities'][$ckl->course]->elapsed += $diff;
                                @$aggregate['section'][$sectionid]->elapsed += $diff;
                            }
                        }
                    }
                }
            }
        } else {
            if (function_exists('debug_trace')) {
                debug_trace("Trainingsessions : No checklist found ");
            }
        }
    }

    // We need finally adjust some times from time recording activities.

    if (array_key_exists('scorm', $aggregate)) {
        foreach (array_keys($aggregate['scorm']) as $cmid) {
            if ($cm = $DB->get_record('course_modules', array('id' => $cmid))) {
                // These are all scorms.

                // Scorm activities have their accurate recorded time.
                $realtotaltime = 0;
                $select = "
                    element = 'cmi.core.total_time' AND
                    scormid = $cm->instance AND
                    userid = $currentuser
                ";
                if ($from) {
                    $select .= " AND timemodified >= $from ";
                }
                if ($to) {
                    $select .= " AND timemodified <= $to ";
                }
                if ($realtimes = $DB->get_records_select('scorm_scoes_track', $select, array(), 'id, element, value')) {
                    foreach ($realtimes as $rt) {
                        preg_match("/(\d\d):(\d\d):(\d\d)\./", $rt->value, $matches);
                        $realtotaltime += $matches[1] * 3600 + $matches[2] * 60 + $matches[3];
                    }
                }
                if ($aggregate['scorm'][$cmid]->elapsed < $realtotaltime) {
                    $diff = $realtotaltime - $aggregate['scorm'][$cmid]->elapsed;
                    $aggregate['scorm'][$cmid]->elapsed += $diff;
                    if (!array_key_exists($cm->course, $aggregate['coursetotal'])) {
                        $aggregate['coursetotal'][$cm->course] = new StdClass();
                    }
                    @$aggregate['coursetotal'][$cm->course]->elapsed += $diff;
                    @$aggregate['activities'][$cm->course]->elapsed += $diff;
                }
            }
        }
    }

    // Add some control values.
    if (!empty($aggregate['coursetotal'])) {
        foreach ($aggregate['coursetotal'] as $courseid => $stat) {
            $t = block_use_stats_format_time($aggregate['coursetotal'][$courseid]->elapsed);
            $aggregate['coursetotal'][$courseid]->elapsedhtml = $t;
        }
    }
    if (!empty($aggregate['activities'])) {
        foreach ($aggregate['activities'] as $courseid => $stat) {
            $t = block_use_stats_format_time($aggregate['activities'][$courseid]->elapsed);
            $aggregate['activities'][$courseid]->elapsedhtml = $t;
        }
    }
    if (!empty($aggregate['other'])) {
        foreach ($aggregate['other'] as $courseid => $stat) {
            $t = block_use_stats_format_time($aggregate['other'][$courseid]->elapsed);
            $aggregate['other'][$courseid]->elapsedhtml = $t;
        }
    }
    if (!empty($aggregate['course'])) {
        foreach ($aggregate['course'] as $courseid => $stat) {
            $t = block_use_stats_format_time($aggregate['course'][$courseid]->elapsed);
            $aggregate['course'][$courseid]->elapsedhtml = $t;
        }
    }

    if ($backdebug) {
        debug_trace($logbuffer);
    }
    if ($automatondebug) {
        echo '<pre>';
        echo $logbuffer;
        echo '</pre>';
    }

    if ($automatondebug) {
        echo '<h2>Aggregator output</h2>';
        block_use_stats_render_aggregate($aggregate);
    }

    return $aggregate;
}

/**
 * fetch ahead the next valid log to be considered
 * @param arrayref $logs the logs track array
 * @param int $i the current log track index
 * @param object $reader the actual valid log reader
 * @return an array containing the next log record, the cumulated lap time for the current
 * processed record and the next log index
 */
function use_stats_fetch_ahead(&$logs, $i, $reader) {

    $log = $logs[$i];
    $lastlog = $logs[$i + 1];
    $lognext = @$logs[$i + 2];
    $j = $i + 1;

    // Resolve the "graded" bias.
    if ($reader instanceof \logstore_standard\log\store) {
        while (isset($lognext) && ($lastlog->action == 'graded') && ($lastlog->target == 'user')) {
            $j++;
            $lastlog = $logs[$j];
            $lognext = @$logs[$j + 1];
        }
    }
    $lap = $lastlog->time - $log->time;
    return array($lastlog, $lap, $j);
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
function use_stats_site_aggregate_time(&$result, $from = 0, $to = 0, $users = null, $courses = null,
                                       $dimensions = 'course,user,institution') {
    global $DB;

    $config = get_config('block_use_stats');

    $logmanager = get_log_manager();
    $readers = $logmanager->get_readers(use_stats_get_reader());
    $reader = reset($readers);

    if (empty($reader)) {
        return false; // No log reader found.
    }

    if ($reader instanceof \logstore_standard\log\store) {
        $coursefield = 'courseid';
    } else if ($reader instanceof \logstore_legacy\log\store) {
        $coursefield = 'course';
    }

    // Make quick accessible memory variables to test.
    $dimensionsarr = explode(',', $dimensions);
    $courseresult = in_array('course', $dimensionsarr);
    $userresult = in_array('user', $dimensionsarr);
    $institutionresult = in_array('institution', $dimensionsarr);

    if ($to == 0) {
        $to = time();
    }

    $userclause = '';
    if (!empty($users)) {
        $userclause = ' AND userid IN ('.implode(',', $users).' )';
    }

    $courseclause = '';
    if (!empty($courses)) {
        $courseclause = ' AND '.$coursefield.' IN ('.implode(',', $courses).' )';
    }

    if ($reader instanceof \logstore_standard\log\store) {
        $sql = "
            SELECT
                l.id,
                l.timecreated as time,
                l.userid,
                l.courseid as course,
                usl.gap,
                u.institution,
                u.department,
                u.city,
                u.country
            FROM
                {logstore_standard_log} l,
                {use_stats_log} usl,
                {user} u
            WHERE
                u.id = l.userid AND
                time >= ? AND
                time <= ?
                $courseclause
                $userclause
        ";
    } else if ($reader instanceof \logstore_legacy\log\store) {
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
    }

    // Pre_loop structure inits.
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

    $threshold = $config->threshold * MINSECS;
    $lastpingcredit = $config->lastpingcredit * MINSECS;

    $rs = $DB->get_recordset_sql($sql, array($from, $to));
    if ($rs) {

        while ($rs->valid()) {
            $gap = $rs->current();

            if ($gap->gap > $threshold) {
                $gap->gap = $lastpingcredit;
            }

            // Overall.
            @$result->all->events += 1;
            @$result->all->elapsed += $gap->gap;
            if (!isset($result->all->firsthit)) {
                $result->all->firsthit = $gap->time;
            }
            $result->all->lasthit = $gap->time;

            // Course detail.
            if ($courseresult) {
                @$result->course[$gap->course]->events += 1;
                @$result->course[$gap->course]->elapsed += $gap->gap;
                if (!isset($result->course[$gap->course]->firsthit)) {
                    $result->all->firsthit = $gap->time;
                }
                $result->course[$gap->course]->lasthit = $gap->time;
            }

            // User detail.
            if ($userresult) {
                @$result->user[$gap->userid]->events += 1;
                @$result->user[$gap->userid]->elapsed += $gap->gap;
                if (!isset($result->user[$gap->userid]->firsthit)) {
                    $result->user[$gap->userid]->firsthit = $gap->time;
                }
                $result->user[$gap->userid]->lasthit = $gap->time;
            }

            // User detail.
            if ($institutionresult) {
                if (!array_key_exists($gap->institution, $result->institutions)) {
                    $result->institutions[$gap->institution] = $institutionid;
                }
                $gapinstitutionid = $result->institutions[$gap->institution];
                @$result->institution[$gapinstitutionid]->events += 1;
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
 * for debugging purpose only. May not be used in
 * stable code.
 * @param array $sessions
 */
function use_stats_render($sessions) {
    if ($sessions) {
        foreach ($sessions as $s) {
            echo userdate(@$s->sessionstart).' / '.userdate(@$s->sessionend).' / ';
            echo floor(@$s->elapsed / 60). ':'.(@$s->elapsed % 60);
            echo ' diff('.(@$s->sessionend - @$s->sessionstart).'='.@$s->elapsed.') <br/>';
        }
    }
}

/**
 * when working with standard log records, get sufficent information about course
 * module from context when context of the trace (event) is inside a course module.
 * this unifies the perception of the use_stats when using either logging method.
 * loggedin event is bound to old login action.
 * @param object $log a log record
 */
function use_stats_add_module_from_context(&$log) {
    global $DB;
    static $cmnames = array();
    static $contexttocmids = array();

    $log->module = 'undefined';
    switch ($log->contextlevel) {
        case CONTEXT_SYSTEM:
            if ($log->action == 'loggedin') {
                $log->module = 'user';
                $log->action = 'login';
            } else {
                $log->module = 'system';
            }
            $log->cmid = 0;
            break;
        case CONTEXT_USER:
            $log->module = 'user';
            $log->cmid = 0;
            break;
        case CONTEXT_MODULE:
            if (!array_key_exists($log->contextid, $contexttocmids)) {
                $contexttocmids[$log->contextid] = $DB->get_field('context', 'instanceid', array('id' => $log->contextid));
            }
            if (!array_key_exists($log->contextid, $cmnames)) {
                $moduleid = $DB->get_field('course_modules', 'module', array('id' => $contexttocmids[$log->contextid]));
                $cmnames[$log->contextid] = $DB->get_field('modules', 'name', array('id' => $moduleid));
            }

            $log->module = $cmnames[$log->contextid];
            $log->cmid = 0 + $contexttocmids[$log->contextid]; // Protect in case of faulty module.
            break;
        default:
            $log->cmid = 0;
            $log->module = 'course';
            break;
    }
}

/**
 * special time formating,
 * @see report/trainingsessions/locallib.php�report_trainingsessions_format_time();
 */
function block_use_stats_format_time($timevalue) {
    if ($timevalue) {
        $secs = $timevalue % 60;
        $mins = floor($timevalue / 60);
        $hours = floor($mins / 60);
        $mins = $mins % 60;

        if ($hours > 0) {
            return "{$hours}h {$mins}m {$secs}s";
        }
        if ($mins > 0) {
            return "{$mins}m {$secs}s";
        }
        return "{$secs}s";
    }
    return '0s';
}

/**
 * Obsolete: unused function
 * @todo Remove this function.
 */
function block_use_stats_render_aggregate(&$aggregate) {
    global $DB;

    echo '<div style="background-color:#f0f0f0;padding:10px;border:1px solid #c0c0c0;border-radius:5px">';
    echo '<h3>User</h3>';
    echo '<table width="100%">';
    if (array_key_exists('user', $aggregate)) {
        foreach ($aggregate['user'] as $courseid => $usertotal) {
            echo '<tr>';
            echo '<td width="40%"></td>';
            echo '<td width="20%">'.$usertotal->elapsed.'</td>';
            echo '<td width="20%">'.block_use_stats_format_time($usertotal->elapsed).'</td>';
            echo '<td width="20%">'.$usertotal->events.'</td>';
            echo '</tr>';
        }
    }
    echo '</table>';

    echo '<h3>Course total</h3>';
    echo '<table width="100%">';
    if (array_key_exists('coursetotal', $aggregate)) {
        foreach ($aggregate['coursetotal'] as $courseid => $coursetotal) {
            $short = $DB->get_field('course', 'shortname', array('id' => $courseid));
            echo '<tr>';
            echo '<td width="40%">['.$courseid.'] '.$short.'</td>';
            echo '<td width="20%">'.$coursetotal->elapsed.'</td>';
            echo '<td width="20%">'.block_use_stats_format_time($coursetotal->elapsed).'</td>';
            echo '<td width="20%">'.$coursetotal->events.'</td>';
            echo '</tr>';
        }
    }
    echo '</table>';

    echo '<h3>In course</h3>';
    echo '<table width="100%">';
    if (array_key_exists('course', $aggregate)) {
        foreach ($aggregate['course'] as $courseid => $coursetotal) {
            $short = $DB->get_field('course', 'shortname', array('id' => $courseid));
            echo '<tr>';
            echo '<td width="40%">['.$courseid.'] '.$short.'</td>';
            echo '<td width="20%">'.$coursetotal->elapsed.'</td>';
            echo '<td width="20%">'.block_use_stats_format_time($coursetotal->elapsed).'</td>';
            echo '<td width="20%">'.$coursetotal->events.'</td>';
            echo '</tr>';
        }
    }
    echo '</table>';

    echo '<h3>Activities</h3>';
    echo '<table width="100%">';
    if (array_key_exists('activities', $aggregate)) {
        foreach ($aggregate['activities'] as $courseid => $activitytotal) {
            $short = $DB->get_field('course', 'shortname', array('id' => $courseid));
            echo '<tr>';
            echo '<td width="40%">['.$courseid.'] '.$short.'</td>';
            echo '<td width="20%">'.$activitytotal->elapsed.'</td>';
            echo '<td width="20%">'.block_use_stats_format_time($activitytotal->elapsed).'</td>';
            echo '<td width="20%">'.$activitytotal->events.'</td>';
            echo '</tr>';
        }
    }
    echo '</table>';

    echo '<h3>Other</h3>';
    echo '<table width="100%">';
    if (array_key_exists('other', $aggregate)) {
        foreach ($aggregate['other'] as $courseid => $othertotal) {
            $short = $DB->get_field('course', 'shortname', array('id' => $courseid));
            echo '<tr>';
            echo '<td width="40%">['.$courseid.'] '.$short.'</td>';
            echo '<td width="20%">'.$othertotal->elapsed.'</td>';
            echo '<td width="20%">'.block_use_stats_format_time($othertotal->elapsed).'</td>';
            echo '<td width="20%">'.$othertotal->events.'</td>';
            echo '</tr>';
        }
    }
    echo '</table>';

    $notdisplay = array('coursetotal', 'activities', 'other', 'sessions', 'user', 'course', 'system');
    foreach ($aggregate as $key => $subs) {
        if (in_array($key, $notdisplay)) {
            continue;
        }
        echo '<h3>'.$key.'</h3>';
        echo '<table width="100%">';
        foreach ($subs as $cmid => $cmtotal) {
            if (!in_array($key, array('realsection', 'section', 'outoftargetcourse'))) {
                $instanceid = $DB->get_field('course_modules', 'instance', array('id' => $cmid));
                $instancename = $DB->get_field($key, 'name', array('id' => $instanceid));
                echo '<tr>';
                echo '<td width="40%">CM'.$cmid.' '.$instancename.'</td>';
            } else {
                $instancename = $DB->get_field('course_sections', 'name', array('id' => $cmid));
                $sectionsection = $DB->get_field('course_sections', 'section', array('id' => $cmid));
                echo '<tr>';
                echo '<td width="40%">SECTION'.$sectionsection.' '.$instancename.'</td>';
            }
            echo '<td width="20%">'.$cmtotal->elapsed.'</td>';
            echo '<td width="20%">'.block_use_stats_format_time($cmtotal->elapsed).'</td>';
            echo '<td width="20%">'.$cmtotal->events.'</td>';
            echo '</tr>';
        }
        echo '</table>';
    }
    echo '</div>';
}

function block_use_stats_is_login_event($action) {
    return (($action == 'login') || ($action == 'loggedin'));
}

function block_use_stats_is_logout_event($action) {
    return (($action == 'logout') || ($action == 'loggedout'));
}

/**
 * Gets the significant log range for this user
 * @param int $userid
 * @param int $from Unix timestamp
 * @param int $to Unix timestamp
 * @return an object with min and max fields.
 */
function block_use_stats_get_log_range($userid, $from, $to) {
    global $DB;

    if (!$params = block_use_stats_get_sql_params()) {
        return false;
    }

    $logrange = new StdClass;
    $field = 'MIN('.$params->timeparam.')';
    $select = ' userid = ? AND '.$params->timeparam.' > ?';
    $logrange->min = $DB->get_field_select($params->tablename, $field, $select, array($userid, $from));
    $field = 'MAX('.$params->timeparam.')';
    $select = ' userid = ? AND '.$params->timeparam.' < ?';
    $logrange->max = $DB->get_field_select($params->tablename, $field, $select, array($userid, $to));

    return $logrange;
}

/**
 * Get adequate SQL elements depending on the active log reader.
 */
function block_use_stats_get_sql_params() {

    $logmanager = get_log_manager();
    $readers = $logmanager->get_readers(use_stats_get_reader());
    $reader = reset($readers);

    if (empty($reader)) {
        return false; // No log reader found.
    }

    if ($reader instanceof \logstore_standard\log\store) {
        $params = new StdClass;
        $params->courseparam = 'courseid';
        $params->timeparam = 'timecreated';
        $params->tablename = 'logstore_standard_log';
    } else if ($reader instanceof \logstore_legacy\log\store) {
        $params = new StdClass;
        $params->courseparam = 'course';
        $params->timeparam = 'time';
        $params->tablename = 'log';
    } else {
        // Unsupported logstore.
        return false;
    }

    return $params;
}

function use_stats_fix_last_course_access($userid, $courseid) {
    global $DB;

    $logmanager = get_log_manager();
    $readers = $logmanager->get_readers(use_stats_get_reader());
    $reader = reset($readers);

    if (empty($reader)) {
        return false; // No log reader found.
    }

    if ($reader instanceof \logstore_standard\log\store) {
        $table = '{logstore_standard_log}';
        $courseparam = 'courseid';
        $timeparam = 'timecreated';
    } else if ($reader instanceof \logstore_legacy\log\store) {
        $table = '{log}';
        $courseparam = 'course';
        $timeparam = 'time';
    } else {
        return;
    }

    $sql = "
        SELECT
            MAX($timeparam) as lastdate
        FROM
            $table
        WHERE
            userid = ? AND
            $courseparam = ?
    ";

    if ($lastdaterec = $DB->get_record_sql($sql, [$userid, $courseid])) {
        if (!is_null($lastdaterec->lastdate)) {
            if ($oldrec = $DB->get_record('user_lastaccess', ['userid' => $userid, 'courseid' => $courseid])) {
                $oldrec->timeaccess = $lastdaterec->lastdate;
                $DB->update_record('user_lastaccess', $oldrec);
            } else {
                $newrec = new StdClass;
                $newrec->userid = $userid;
                $newrec->courseid = $courseid;
                $newrec->timeaccess = $lastdaterec->lastdate;
                $DB->insert_record('user_lastaccess', $newrec);
            }
        }
    }
}