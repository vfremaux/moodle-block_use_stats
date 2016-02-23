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
 * @package    block_use_stats
 * @category   blocks
 * @author     Valery Fremaux (valery.fremaux@gmail.com)
 * @copyright  Valery Fremaux (valery.fremaux@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/lib/blocklib.php');
require_once($CFG->dirroot.'/blocks/moodleblock.class.php');
require_once($CFG->dirroot.'/blocks/use_stats/locallib.php');
require_once $CFG->dirroot.'/blocks/use_stats/lib.php';

class block_use_stats extends block_base {

    function init() {
        $this->title = get_string('blockname', 'block_use_stats');
        $this->content_type = BLOCK_TYPE_TEXT;
    }

    /**
     * is the bloc configurable ?
     */
    function has_config() {
        return true;
    }

    /**
     * do we have local config
     */
    function instance_allow_config() {
        global $COURSE;

        return false;
    }

    /**
     * In which course format can we see and add the block.
     */
    function applicable_formats() {
        return array('all' => true);
    }

    /**
     * Produce content for the bloc
     */
    function get_content() {
        global $USER, $CFG, $COURSE, $DB;

        $config = get_config('block_use_stats');

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->text = '';
        $this->content->footer = '';

        if (empty($this->instance)) {
            return $this->content;
        }

        // Know which reader we are working with.
        $logmanager = get_log_manager();
        $readers = $logmanager->get_readers('\core\log\sql_select_reader');
        $reader = reset($readers);
    
        if (empty($reader)) {
            return $this->content; // No log reader found.
        }

        // Get context so we can check capabilities.
        $context = context_block::instance($this->instance->id);
        $systemcontext = context_system::instance();
        if (!has_capability('block/use_stats:view', $context)) {
            return $this->content;
        }

        $id = optional_param('id', 0, PARAM_INT);
        $fromwhen = 30;
        if (!empty($config->fromwhen)) {
            $fromwhen = optional_param('ts_from', $config->fromwhen, PARAM_INT);
        }

        $daystocompilelogs = $fromwhen * DAYSECS;
        $timefrom = time() - $daystocompilelogs;

        if (has_any_capability(array('block/use_stats:seesitedetails', 'block/use_stats:seecoursedetails', 'block/use_stats:seegroupdetails'), $context, $USER->id)) {
            $userid = optional_param('uid', $USER->id, PARAM_INT);
        } else {
            $userid = $USER->id;
        }

        $logs = use_stats_extract_logs($timefrom, time(), $userid);
        $lasttime = $timefrom;
        $totalTime = 0;
        $totalTimeCourse = array();
        $totalTimeModule = array();
        if ($logs) {
            foreach ($logs as $aLog) {

                if ($reader instanceof \logstore_standard\log\store) {
                    // Get module from context
                    use_stats_add_module_from_context($aLog);
                }

                $delta = $aLog->time - $lasttime;
                if ($delta < @$config->threshold * MINSECS) {
                    $totalTime = $totalTime + $delta;

                    if (!array_key_exists($aLog->course, $totalTimeCourse)) {
                        $totalTimeCourse[$aLog->course] = 0;
                    } else {
                        $totalTimeCourse[$aLog->course] = $totalTimeCourse[$aLog->course] + $delta;
                    }
                    if (empty($aLog->module)) $aLog->module = 'system';
                    if (!array_key_exists($aLog->course, $totalTimeModule)) {
                        $totalTimeModule[$aLog->course][$aLog->module] = 0;
                    } elseif (!array_key_exists($aLog->module, $totalTimeModule[$aLog->course])) {
                        $totalTimeModule[$aLog->course][$aLog->module] = 0;
                    } else {
                        $totalTimeModule[$aLog->course][$aLog->module] = $totalTimeModule[$aLog->course][$aLog->module] + $delta;
                    }
                }
                $lasttime = $aLog->time;
            }

            $hours = floor($totalTime/HOURSECS);
            $remainder = $totalTime - $hours * HOURSECS;
            $min = floor($remainder/MINSECS);

            $this->content->text .= '<div class="message">';
            $this->content->text .= " <form style=\"display:inline\" name=\"ts_changeParms\" method=\"post\" action=\"#\">";
            $this->content->text .= '<input type="hidden" name="id" value="'.$id.'" />';
            if (has_capability('block/use_stats:seesitedetails', $context, $USER->id)) {
                $users = $DB->get_records('user', array('deleted' => 0), 'lastname', 'id,'.get_all_user_name_fields(true, ''));
            } elseif (has_capability('block/use_stats:seecoursedetails', $context, $USER->id)) {
                $coursecontext = context_course::instance($COURSE->id);
                $users = get_users_by_capability($coursecontext, 'moodle/course:view', 'u.id,'.get_all_user_name_fields(true, 'u'));
            } elseif (has_capability('block/use_stats:seegroupdetails', $context, $USER->id)) {
                $mygroups = groups_get_user_groups($COURSE->id);
                $users = array();
                // Get all users in my groups.
                foreach ($mygroupids as $mygroupid) {
                    $users = $users + groups_get_members($groupid, 'u.id,'.get_all_user_name_fields(true, 'u'));
                }
            }

            if (!empty($users)) {
                $usermenu = array();
                foreach ($users as $user) {
                    $usermenu[$user->id] = fullname($user, has_capability('moodle/site:viewfullnames', context_system::instance()));
                }
                $this->content->text .= html_writer::select($usermenu, 'uid', $userid, 'choose', array('onchange' => 'document.ts_changeParms.submit();'));
            }

            $this->content->text .= get_string('from', 'block_use_stats');
            $this->content->text .= '<select name="ts_from" onChange="document.ts_changeParms.submit();">';
            foreach (array(5,15,30,60,90,365) as $interval) {
                $selected = ($interval == $fromwhen) ? "selected=\"selected\"" : '';
                $this->content->text .= '<option value="'.$interval.'" '.$selected.' >'.$interval.' '.get_string('days').'</option>';
            }
            $this->content->text .= '</select>';
            $this->content->text .= '</form><br/>';
            $this->content->text .= get_string('youspent', 'block_use_stats');
            $this->content->text .= $hours.' '.get_string('hours').' '.$min.' '.get_string('mins');
            $this->content->text .= get_string('onthismoodlefrom', 'block_use_stats');
            $this->content->text .= userdate($timefrom);
            if (count(array_keys($totalTimeCourse))) {
                $this->content->text .= '<table width="100%">';
                foreach (array_keys($totalTimeCourse) as $aCourseId) {
                    $aCourse = $DB->get_record('course', array('id' => $aCourseId));
                    if ($totalTimeCourse[$aCourseId] < 60) {
                        continue;
                    }
                    if ($aCourse) {
                        $hours = floor($totalTimeCourse[$aCourseId] / HOURSECS);
                        $remainder = $totalTimeCourse[$aCourseId] - $hours * HOURSECS;
                        $min = floor($remainder/MINSECS);
                        $courseelapsed = $hours.' '.get_string('hours').' '.$min.' '.get_string('mins');
                        $this->content->text .= '<tr><td class="teacherstatsbycourse" align="left" title="'.htmlspecialchars(format_string($aCourse->fullname)).'">'.$aCourse->shortname.'</td><td class="teacherstatsbycourse" align="right">'.$courseelapsed.'</td></tr>';
                    }
                }
                $this->content->text .= '</table>';
            }
            $this->content->text .= '</div>';

            if (has_any_capability(array('block/use_stats:seeowndetails', 'block/use_stats:seesitedetails', 'block/use_stats:seecoursedetails', 'block/use_stats:seegroupdetails'), $context, $USER->id)) {
                $showdetailstr = get_string('showdetails', 'block_use_stats');
                $params = array('id' => $this->instance->id, 'userid' => $userid, 'course' => $COURSE->id);
                if (!empty($fromwhen)) {
                     $params['ts_from'] = $fromwhen;
                }
                $viewurl = new moodle_url('/blocks/use_stats/detail.php', $params);
                $this->content->text .= '<a href="'.$viewurl.'">'.$showdetailstr.'</a>';
            }
        } else {
            $this->content->text = '<div class="message">';
            $this->content->text .= get_string('noavailablelogs', 'block_use_stats');
            $this->content->text .= '<br/>';
            $this->content->text .= ' <form style="display:inline" name="ts_changeParms" method="post" action="#">';
            $this->content->text .= '<input type="hidden" name="id" value="'.$id.'" />';
            if (has_capability('block/use_stats:seesitedetails', $context, $USER->id)) {
                $users = $DB->get_records('user', array('deleted' => '0'), 'lastname', 'id,'.get_all_user_name_fields(true, ''));
            } elseif (has_capability('block/use_stats:seecoursedetails', $context, $USER->id)) {
                $coursecontext = context_course::instance($COURSE->id);
                $users = get_users_by_capability($coursecontext, 'moodle/course:view', 'u.id,'.get_all_user_name_fields(true, 'u'));
            } elseif (has_capability('block/use_stats:seegroupdetails', $context, $USER->id)) {
                $mygroupings = groups_get_user_groups($COURSE->id);

                $mygroups = array();
                foreach ($mygroupings as $grouping) {
                    $mygroups = $mygroups + $grouping;
                }

                $users = array();
                // get all users in my groups
                foreach ($mygroups as $mygroupid) {
                    $members = groups_get_members($mygroupid, 'u.id,'.get_all_user_name_fields(true, 'u'));
                    if ($members) {
                        $users = $users + $members;
                    }
                }
            }
            if (!empty($users)) {
                $usermenu = array();
                foreach ($users as $user) {
                    $usermenu[$user->id] = fullname($user, has_capability('moodle/site:viewfullnames', context_system::instance()));
                }
                $this->content->text .= html_writer::select($usermenu, 'uid', $userid, 'choose', array('onchange' => 'document.ts_changeParms.submit();'));
            }
            $this->content->text .= get_string('from', 'block_use_stats');
            $this->content->text .= '<select name="ts_from" onChange="document.ts_changeParms.submit();">';
            foreach (array(5,15,30,60,90,365) as $interval) {
                $selected = ($interval == $fromwhen) ? "selected=\"selected\"" : '' ;
                $this->content->text .= '<option value="'.$interval.'" '.$selected.' >'.$interval.' '.get_string('days').'</option>';
            }
            $this->content->text .= "</select>";
            $this->content->text .= "</form><br/>";
            $this->content->text .= "</div>";
        }

        return $this->content;
    }

    /**
     * Used by the component associated task.
     */
    static function crontask() {
        global $CFG, $DB;

        $config = get_config('block_use_stats');

        $logmanager = get_log_manager();
        $readers = $logmanager->get_readers('\core\log\sql_reader');
        $reader = reset($readers);

        if (empty($reader)) {
            mtrace('No log reader.');
            return false; // No log reader found.
        }

        if ($reader instanceof \logstore_standard\log\store) {
            $courseparm = 'courseid';
        } elseif($reader instanceof \logstore_legacy\log\store) {
            $courseparm = 'course';
        } else {
            mtrace('Unsupported log reader.');
            return;
        }

        if (!isset($config->lastcompiled)) {
            set_config('lastcompiled', '0', 'block_use_stats');
            $config->lastcompiled = 0;
        }

        mtrace("\n".'... Compiling gaps from : '.$config->lastcompiled);

        // Feed the table with log gaps.
        $previouslog = array();
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
                 timecreated > ?
               ORDER BY
                 timecreated
            ";
            $rs = $DB->get_recordset_sql($sql, array($config->lastcompiled));
        } elseif ($reader instanceof \logstore_legacy\log\store) {
            $rs = $DB->get_recordset_select('log', " time > ? ", array($config->lastcompiled), 'time', 'id,time,userid,course,cmid');
        } else {
            mtrace("this logstore is not supported");
            return;
        }

        if ($rs) {

            $r = 0;

            $starttime = time();

            while ($rs->valid()) {
                $log = $rs->current();
                $gaprec = new StdClass;
                $gaprec->logid = $log->id;
                $gaprec->userid = $log->userid;
                $gaprec->time = $log->time;
                $gaprec->course = $log->course;

                for ($ci = 1 ; $ci <= 6; $ci++) {
                    $key = 'customtag'.$ci;
                    $gaprec->$key = '';
                    if (!empty($config->enablecompilecube)) {
                        $customselectkey = "customtag{$ci}select";
                        if (!empty($config->$customselectkey)) {
                            $customsql = str_replace('<%%LOGID%%>', $log->id, stripslashes($config->$customselectkey));
                            $customsql = str_replace('<%%USERID%%>', $log->userid, $customsql);
                            $customsql = str_replace('<%%COURSEID%%>', $log->course, $customsql);
                            $customsql = str_replace('<%%CMID%%>', $log->cmid, $customsql);
                            $gaprec->$key = $DB->get_field_sql($customsql, array());
                        }
                    }
                }

                $gaprec->gap = 0;
                if (!$DB->record_exists('block_use_stats_log', array('logid' => $log->id))) {
                    $DB->insert_record('block_use_stats_log', $gaprec);
                }
                // Is there a last log found before actual compilation session ?
                if (!array_key_exists($log->userid, $previouslog)) {
                    $maxlasttime = $DB->get_field_select('log', 'MAX(time)', ' time < ? ', array($config->lastcompiled));
                    $previouslog[$log->userid] = $DB->get_record('log', array('time' => $maxlasttime));
                }
                $DB->set_field('block_use_stats_log', 'gap', $log->time - (0 + @$previouslog[$log->userid]->time), array('logid' => @$previouslog[$log->userid]->id));
                $previouslog[$log->userid] = $log;
                $lasttime = $log->time;
                $r++;

                if ($r %10 == 0) {
                    echo '.';
                    $processtime = time();
                    if (($processtime > $starttime + 60 * 15) || ($r > 100000)) {
                        break; // Do not process more than 15 minutes.
                    }
                }
                if ($r %1000 == 0) {
                    // Store intermediary track points.
                    if (!empty($lasttime)) {
                        set_config('lastcompiled', $lasttime, 'block_use_stats');
                    }
                }
                $rs->next();
            }
            $rs->close();

            mtrace("\n... $r logs gapped");
            // Register last log time for cron further updates.
            if (!empty($lasttime)) {
                set_config('lastcompiled', $lasttime, 'block_use_stats');
            }
        }
    }

    /**
     * to cleanup some logs to delete.
     */
    static function cleanup_task() {
        global $CFG, $DB;

        $logmanager = get_log_manager();
        $readers = $logmanager->get_readers('\core\log\sql_reader');
        $reader = reset($readers);

        if (empty($reader)) {
            mtrace('No log reader.');
            return false; // No log reader found.
        }

        if ($reader instanceof \logstore_standard\log\store) {
            $sql = "DELETE FROM
                        {block_use_stats_log}
                    WHERE
                        logid NOT IN(SELECT id FROM {log})
            ";
        } elseif ($reader instanceof \logstore_legacy\log\store) {
            $sql = "DELETE FROM
                        {block_use_stats_log}
                    WHERE
                        logid NOT IN(SELECT id FROM {logstore_standard_log})
            ";
        } else {
            mtrace('Unsupported log reader.');
            return;
        }

        $DB->execute($sql);
    }
}

global $PAGE;
if ($PAGE->state < moodle_page::STATE_PRINTING_HEADER) {
    block_use_stats_setup_theme_requires();
}