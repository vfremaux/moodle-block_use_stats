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

class block_use_stats_renderer extends plugin_renderer_base {

    public function per_course(&$aggregate, &$fulltotal) {
        global $OUTPUT;

        $config = get_config('block_use_stats');

        $fulltotal = 0;
        $eventsunused = 0;

        $usestatsorder = optional_param('usestatsorder', 'name', PARAM_TEXT);

        $tbl = block_use_stats::prepare_coursetable($aggregate, $fulltotal, $eventsunused, $usestatsorder);
        list($displaycourses, $courseshort, $coursefull, $courseelapsed) = $tbl;

        $url = "http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];

        $currentnameurl = new moodle_url($url);
        $currentnameurl->params(array('usestatsorder' => 'name'));

        $currenttimeurl = new moodle_url($url);
        $currenttimeurl->params(array('usestatsorder' => 'time'));

        $str = '<div class="usestats-coursetable">';
        $label = get_string('byname', 'block_use_stats');
        $str .= '<div class="pull-left smalltext"><a href="'.$currentnameurl.'">'.$label.'</a></div>';
        $label = get_string('bytimedesc', 'block_use_stats');
        $str .= '<div class="pull-right smalltext"><a href="'.$currenttimeurl.'">'.$label.'</a></div>';
        $str .= '</div>';

        $str .= '<table width="100%">';
        foreach (array_keys($displaycourses) as $courseid) {
            if (!empty($config->filterdisplayunder)) {
                if ($courseelapsed[$courseid] < $config->filterdisplayunder) {
                    continue;
                }
            }
            $str .= '<tr>';
            $title = htmlspecialchars(format_string($coursefull[$courseid]));
            $str .= '<td class="teacherstatsbycourse" align="left" title="'.$title.'">';
            $str .= $courseshort[$courseid];
            $str .= '</td>';
            $str .= '<td class="teacherstatsbycourse" align="right">';
            $str .= block_use_stats_format_time($courseelapsed[$courseid]);
            $str .= '</td>';
            $str .= '</tr>';
        }

        if (!empty($config->filterdisplayunder)) {
            $title = htmlspecialchars(get_string('isfiltered', 'block_use_stats', $config->filterdisplayunder));
            $pix = '<img src="'.$OUTPUT->pix_url('i/warning').'">';
            $str .= '<tr><td class="teacherstatsbycourse" title="'.$title.'">'.$pix.'</td>';
            $str .= '<td align="right" class="teacherstatsbycourse">';
            if (@$config->displayactivitytimeonly != DISPLAY_FULL_COURSE) {
                $str .= '('.get_string('activities', 'block_use_stats').')';
            }
            $str .= '</td></tr>';
        }

        $str .= '</table>';

        return $str;
    }

    /**
     * @global type $USER
     * @global type $DB
     * @global type $COURSE
     * @param type $context
     * @param type $id
     * @param type $fromwhen
     * @param type $userid
     * @return string
     */
    public function change_params_form($context, $id, $fromwhen, $userid) {
        global $USER, $DB, $COURSE;

        $config = get_config('block_use_stats');

        $str = ' <form style="display:inline" name="ts_changeParms" method="post" action="#">';

        $str .= '<input type="hidden" name="id" value="'.$id.'" />';

        if (has_capability('block/use_stats:seesitedetails', $context, $USER->id) && ($COURSE->id == SITEID)) {
            $users = $DB->get_records('user', array('deleted' => '0'), 'lastname', 'id,'.get_all_user_name_fields(true, ''));
        } else if (has_capability('block/use_stats:seecoursedetails', $context, $USER->id)) {
            $coursecontext = context_course::instance($COURSE->id);
            $users = get_enrolled_users($coursecontext);
        } else if (has_capability('block/use_stats:seegroupdetails', $context, $USER->id)) {
            $mygroupings = groups_get_user_groups($COURSE->id);

            $mygroups = array();
            foreach ($mygroupings as $grouping) {
                $mygroups = $mygroups + $grouping;
            }

            $users = array();
            // Get all users in my groups.
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
            $attrs = array('onchange' => 'document.ts_changeParms.submit();');
            $str .= html_writer::select($usermenu, 'uid', $userid, 'choose', $attrs);
        }

        if (@$config->backtrackmode == 'sliding') {
            if (@$config->backtracksource == 'studentchoice') {
                $str .= ' ';
                $str .= get_string('from', 'block_use_stats');
                foreach (array(5, 15, 30, 60, 90, 180, 365) as $interval) {
                    $timemenu[$interval] = $interval.' '.get_string('days');
                }
                $attrs = array('onchange' => 'document.ts_changeParms.submit();');
                $str .= html_writer::select($timemenu, 'ts_from', $fromwhen, 'choose', $attrs);
            }
        } else {
            if (@$config->backtracksource == 'studentchoice') {
                $userpref = $DB->get_field('user_preferences', 'value', array('userid' => $USER->id, 'name' => 'use_stats_horizon'));
                if (empty($userpref)) {
                    if ($COURSE->id != SITEID) {
                        $userpref = date('Y-m-d', $COURSE->startdate);
                    } else {
                        $userpref = date('Y-m-d', $USER->firstaccess);
                    }
                }
                $str .= '<br/>'.get_string('from', 'block_use_stats');
                $htmlkey = 'ts_horizon'.$context->id;
                $str .= ': <input type="text"
                                  size="10"
                                  id="date-'.$htmlkey.'"
                                  name="'.$htmlkey.'"
                                  value="'.$userpref.'"
                                  />';
                $str .= '<script type="text/javascript">'."\n";
                $str .= 'var '.$htmlkey.'Cal = new dhtmlXCalendarObject(["date-'.$htmlkey.'"]);'."\n";
                $str .= $htmlkey.'Cal.loadUserLanguage(\''.current_language().'_utf8\');'."\n";
                $str .= $htmlkey.'Cal.attachEvent("onChange", function() {
                    document.ts_changeParms.submit();
                });'."\n";
                $str .= '</script>'."\n";
            }
        }
        $str .= "</form><br/>";

        return $str;
    }

    /**
     * @global type $OUTPUT
     * @global type $COURSE
     * @global type $USER
     * @param type $userid
     * @param type $from
     * @param type $to
     * @param type $context
     * @return type
     */
    public function button_pdf($userid, $from, $to, $context) {
        global $OUTPUT, $COURSE, $USER;

        // XSS security.
        $capabilities = array('block/use_stats:seegroupdetails',
                              'block/use_stats:seecoursedetails',
                              'block/use_stats:seesitedetails');
        if (!has_any_capability($capabilities, $context)) {
            // Force report about yourself.
            $userid = $USER->id;
        }

        $config = get_config('block_use_stats');

        $now = time();
        $filename = 'report_user_'.$userid.'_'.date('Ymd_His', $now).'.pdf';

        $reportscope = (@$config->displayactivitytimeonly == DISPLAY_FULL_COURSE) ? 'fullcourse' : 'activities';
        $params = array(
            'id' => $COURSE->id,
            'from' => $from,
            'to' => $to,
            'userid' => $userid,
            'scope' => $reportscope,
            'timesession' => $now,
            'outputname' => $filename);

        $url = new moodle_url('/report/trainingsessions/tasks/userpdfreportallcourses_batch_task.php', $params);

        $str = '';
        $str .= $OUTPUT->single_button($url, get_string('printpdf', 'block_use_stats'));

        return $str;
    }
}