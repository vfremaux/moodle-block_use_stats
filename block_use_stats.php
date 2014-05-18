<?PHP //$Id: block_use_stats.php,v 1.7 2011-07-29 09:02:11 vf Exp $

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/blocks/use_stats/locallib.php');

/**
*/
class block_use_stats extends block_base {

    function init() {
        $this->title = get_string('blockname','block_use_stats');
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

    function applicable_formats() {
        return array('all' => true);
    }
    
    /**
    *
    */
    function user_can_edit() {
        global $CFG, $COURSE;

        return false;
    }

    /**
    * Produce content for the bloc
    */
    function get_content() {
        global $USER, $CFG, $COURSE, $DB;

        if ($this->content !== NULL) {
            return $this->content;
        }

        $this->content = new stdClass;
        $this->content->text = '';
        $this->content->footer = '';
        
        if (empty($this->instance)) {
            return $this->content;
        }
        
        // Get context so we can check capabilities.
        $context = context_block::instance($this->instance->id);
        $systemcontext = context_system::instance();
        if (!has_capability('block/use_stats:view', $context)){
            return $this->content;
        }

        $id = optional_param('id', 0, PARAM_INT);
        $fromwhen = 30;
        if (!empty($CFG->block_use_stats_fromwhen)) {
			$fromwhen = optional_param('ts_from', $CFG->block_use_stats_fromwhen, PARAM_INT);
		}

        $daystocompilelogs = $fromwhen * DAYSECS;
        $timefrom = time() - $daystocompilelogs;
        
        if (has_any_capability(array('block/use_stats:seesitedetails', 'block/use_stats:seecoursedetails', 'block/use_stats:seegroupdetails'), $context, $USER->id)){
            $userid = optional_param('uid', $USER->id, PARAM_INT);
        } else {
            $userid = $USER->id;
        }

        $logs = use_stats_extract_logs($timefrom, time(), $userid);
        $lasttime = $timefrom;
        $totalTime = 0;
        $totalTimeCourse = array();
        $totalTimeModule = array();
        if ($logs){
            foreach($logs as $aLog){
                $delta = $aLog->time - $lasttime;
                if ($delta < @$CFG->block_use_stats_threshold * MINSECS){
                    $totalTime = $totalTime + $delta;

                    if (!array_key_exists($aLog->course, $totalTimeCourse))
                        $totalTimeCourse[$aLog->course] = 0;
                    else
                        $totalTimeCourse[$aLog->course] = $totalTimeCourse[$aLog->course] + $delta;

                    if (!array_key_exists($aLog->course, $totalTimeModule))
                        $totalTimeModule[$aLog->course][$aLog->module] = 0;
                    elseif (!array_key_exists($aLog->module, $totalTimeModule[$aLog->course]))
                        $totalTimeModule[$aLog->course][$aLog->module] = 0;
                    else
                        $totalTimeModule[$aLog->course][$aLog->module] = $totalTimeModule[$aLog->course][$aLog->module] + $delta;
                }
                $lasttime = $aLog->time;
            }
            
            $hours = floor($totalTime/HOURSECS);
            $remainder = $totalTime - $hours * HOURSECS;
            $min = floor($remainder/MINSECS);

            $this->content->text .= "<div class=\"message\">";
            $this->content->text .= " <form style=\"display:inline\" name=\"ts_changeParms\" method=\"post\" action=\"#\">";
            $this->content->text .= "<input type=\"hidden\" name=\"id\" value=\"$id\" />";
            if (has_capability('block/use_stats:seesitedetails', $context, $USER->id)){
                $users = $DB->get_records('user', array('deleted' => 0), 'lastname', 'id, firstname, lastname');
            } 
            else if (has_capability('block/use_stats:seecoursedetails', $context, $USER->id)){
            	$coursecontext = context_course::instance($COURSE->id);
                $users = get_users_by_capability($coursecontext, 'moodle/course:view', 'u.id, firstname, lastname');
            }
            else if (has_capability('block/use_stats:seegroupdetails', $context, $USER->id)){
            	$mygroups = groups_get_user_groups($COURSE->id);
            	$users = array();
            	// get all users in my groups
            	foreach($mygroupids as $mygroupid){
	            	$users = $fellows + groups_get_members($groupid, 'u.id, firstname, lastname');
	            }
            }

            if (!empty($users)){
                $usermenu = array();
                foreach($users as $user){
                    $usermenu[$user->id] = fullname($user, has_capability('moodle/site:viewfullnames', context_system::instance()));
                }
	            $this->content->text .= html_writer::select($usermenu, 'uid', $userid, 'choose', array('onchange' => 'document.ts_changeParms.submit();'));
            }
            $this->content->text .= get_string('from', 'block_use_stats');
            $this->content->text .= "<select name=\"ts_from\" onChange=\"document.ts_changeParms.submit();\">";
            foreach(array(5,15,30,60,90,365) as $interval){
                $selected = ($interval == $fromwhen) ? "selected=\"selected\"" : '' ;
                $this->content->text .= "<option value=\"{$interval}\" {$selected} >{$interval} ".get_string('days')."</option>";
            }
            $this->content->text .= "</select>";
            $this->content->text .= "</form><br/>";
            $this->content->text .= get_string('youspent', 'block_use_stats');
            $this->content->text .= $hours . ' ' . get_string('hours') . ' ' . $min . ' ' . get_string('mins'); 
            $this->content->text .= get_string('onthisMoodlefrom', 'block_use_stats');
            $this->content->text .= userdate($timefrom);
            if (count(array_keys($totalTimeCourse))){
                $this->content->text .= "<table width=\"100%\">";
                foreach(array_keys($totalTimeCourse) as $aCourseId){
                    $aCourse = $DB->get_record('course', array('id' => $aCourseId));
                    if ($totalTimeCourse[$aCourseId] < 60) continue;
                    if ($aCourse){
                        $hours = floor($totalTimeCourse[$aCourseId] / HOURSECS);
                        $remainder = $totalTimeCourse[$aCourseId] - $hours * HOURSECS;
                        $min = floor($remainder/MINSECS);
                        $courseelapsed = $hours . ' ' . get_string('hours') . ' ' . $min . ' ' . get_string('mins'); 
                        $this->content->text .= "<tr><td class=\"teacherstatsbycourse\" align=\"left\" title=\"".htmlspecialchars(format_string($aCourse->fullname))."\">{$aCourse->shortname}</td><td class=\"teacherstatsbycourse\" align=\"right\">{$courseelapsed}</td></tr>";
                    }
                }
                $this->content->text .= "</table>";
            }
            $this->content->text .= "</div>";

            if (has_any_capability(array('block/use_stats:seeowndetails', 'block/use_stats:seesitedetails', 'block/use_stats:seecoursedetails', 'block/use_stats:seegroupdetails'), $context, $USER->id)){
                $showdetailstr = get_string('showdetails', 'block_use_stats');
                $fromclause = (!empty($fromwhen)) ? "&amp;ts_from={$fromwhen}" : '' ;
                $this->content->text .= "<a href=\"{$CFG->wwwroot}/blocks/use_stats/detail.php?id={$this->instance->id}&amp;userid={$userid}{$fromclause}&course={$COURSE->id}\">$showdetailstr</a>";
            }
        } else {
            $this->content->text = "<div class=\"message\">";
            $this->content->text .= get_string('noavailablelogs', 'block_use_stats');
            $this->content->text .= "<br/>";
            $this->content->text .= " <form style=\"display:inline\" name=\"ts_changeParms\" method=\"post\" action=\"#\">";
            $this->content->text .= "<input type=\"hidden\" name=\"id\" value=\"$id\" />";
            if (has_capability('block/use_stats:seesitedetails', $context, $USER->id)){
                $users = $DB->get_records('user', array('deleted' => '0'), 'lastname', 'id, firstname, lastname');
            }
            else if (has_capability('block/use_stats:seecoursedetails', $context, $USER->id)){
            	$coursecontext = context_course::instance($COURSE->id);
                $users = get_users_by_capability($coursecontext, 'moodle/course:view', 'u.id, firstname, lastname');
            }
            else if (has_capability('block/use_stats:seegroupdetails', $context, $USER->id)){
            	$mygroupings = groups_get_user_groups($COURSE->id);

				$mygroups = array();            	
            	foreach($mygroupings as $grouping){
            		$mygroups = $mygroups + $grouping;
            	}
            	
            	$users = array();
            	// get all users in my groups
            	foreach($mygroups as $mygroupid){
            		$members = groups_get_members($mygroupid, 'u.id, firstname, lastname');
            		if ($members){
		            	$users = $users + $members;
		            }
	            }
            }
            if (!empty($users)){
                $usermenu = array();
                foreach($users as $user){
                    $usermenu[$user->id] = fullname($user, has_capability('moodle/site:viewfullnames', context_system::instance()));
                }
            	$this->content->text .= html_writer::select($usermenu, 'uid', $userid, 'choose', array('onchange' => 'document.ts_changeParms.submit();'));
            }
            $this->content->text .= get_string('from', 'block_use_stats');
            $this->content->text .= "<select name=\"ts_from\" onChange=\"document.ts_changeParms.submit();\">";
            foreach(array(5,15,30,60,90,365) as $interval){
                $selected = ($interval == $fromwhen) ? "selected=\"selected\"" : '' ;
                $this->content->text .= "<option value=\"{$interval}\" {$selected} >{$interval} ".get_string('days')."</option>";
            }
            $this->content->text .= "</select>";
            $this->content->text .= "</form><br/>";
            $this->content->text .= "</div>";
        }        

        return $this->content;
    }

	function cron(){
		global $CFG, $DB;
		
		if (empty($CFG->block_use_stats_enablecompilelogs)) return;
		
		if (!isset($CFG->block_use_stats_lastcompiled)) $CFG->block_use_stats_lastcompiled = 0;
		
		mtrace("\n".'... Compiling gaps from : '.$CFG->block_use_stats_lastcompiled);
		
	/// feed the table with log gaps
		$previouslog = array();
		$rs = $DB->get_recordset_select('log', " time > ? ", array($CFG->block_use_stats_lastcompiled), 'time', 'id,time,userid,course,cmid');
	    if($rs){
	    	
	    	$r = 0;
	    	
	    	$starttime = time();
	        
	        while($rs->valid()){
	        	$log = $rs->current();
        		$gaprec = new StdClass;
        		$gaprec->logid = $log->id;
        		$gaprec->userid = $log->userid;
        		$gaprec->time = $log->time;
        		$gaprec->course = $log->course;

				for($ci = 1 ; $ci <= 6; $ci++){
					$key = "customtag".$ci;
					$gaprec->$key = '';
					if (!empty($CFG->block_use_stats_enablecompilecube)){
						$customselectkey = "block_use_stats_customtag{$ci}select";
			    		if (!empty($CFG->$customselectkey)){
			        		$customsql = str_replace('<%%LOGID%%>', $log->id, stripslashes($CFG->$customselectkey));
			        		$customsql = str_replace('<%%USERID%%>', $log->userid, $customsql);
			        		$customsql = str_replace('<%%COURSEID%%>', $log->course, $customsql);
			        		$customsql = str_replace('<%%CMID%%>', $log->cmid, $customsql);
							$gaprec->$key = $DB->get_field_sql($customsql, array());
			        	}
			        }
				}
				        		        		
        		$gaprec->gap = 0;
        		if (!$DB->record_exists('block_use_stats_log', array('logid' => $log->id))){
	        		$DB->insert_record('block_use_stats_log', $gaprec);
	        	}
	        	// is there a last log found before actual compilation session ?
	        	if (!array_key_exists($log->userid, $previouslog)){
	        		$maxlasttime = $DB->get_field_select('log', 'MAX(time)', ' time < ? ', array($CFG->block_use_stats_lastcompiled));
	        		$previouslog[$log->userid] = $DB->get_record('log', array('time' => $maxlasttime));
	        	}
        		$DB->set_field('block_use_stats_log', 'gap', $log->time - (0 + @$previouslog[$log->userid]->time), array('logid' => @$previouslog[$log->userid]->id));
        		$previouslog[$log->userid] = $log;
        		$lasttime = $log->time;
        		$r++;
				if ($r %10 == 0){
	        		$processtime = time();
					if (($processtime > $starttime + 60 * 15) || ($r > 100000)) break; // do not process more than 15 minutes
				}
				$rs->next();
	        }
	        $rs->close();
	        
	        mtrace("... $r logs gapped");
	        // register last log time for cron further updates
	        if (!empty($lasttime)) set_config('block_use_stats_lastcompiled', $lasttime);
	    }
	}
}
