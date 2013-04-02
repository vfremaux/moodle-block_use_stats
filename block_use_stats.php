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
    function user_can_addto($page) {
        global $CFG, $COURSE;

        if (has_capability('moodle/site:config', context_system::instance())){
            return true;
        }        

		$context = context_course::instance($COURSE->id);
        if (!has_capability('block/use_stats:canaddto', $context)){
            return false;
        }
        return true;
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
        $context = get_context_instance(CONTEXT_BLOCK, $this->instance->id);
        $systemcontext = get_context_instance(CONTEXT_SYSTEM);
        if (!has_capability('block/use_stats:view', $context)){
            return $this->content;
        }

        $fromwhen = 30;
        $fromwhen = optional_param('ts_from', $CFG->block_use_stats_fromwhen, PARAM_INT);

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

            if (file_exists("{$CFG->dirroot}/theme/{$CFG->theme}/block_use_stats.css")){
                $this->content->text = "<link rel=\"stylesheet\" href=\"{$CFG->wwwroot}/theme/".current_theme()."/block_use_stats.css\" type=\"text/css\" />";
            } elseif (file_exists("{$CFG->dirroot}/theme/default/block_use_stats.css")){
                $this->content->text = "<link rel=\"stylesheet\" href=\"{$CFG->wwwroot}/theme/default/block_use_stats.css\" type=\"text/css\" />";
            }

            $this->content->text .= "<div class=\"message\">";
            $this->content->text .= " <form style=\"display:inline\" name=\"ts_changeParms\" method=\"post\" action=\"#\">";
            if (has_capability('block/use_stats:seesitedetails', $context, $USER->id)){
                $users = $DB->get_records('user', array('deleted' => 0), 'lastname', 'id, firstname, lastname');
            } 
            else if (has_capability('block/use_stats:seecoursedetails', $context, $USER->id)){
            	$coursecontext = get_context_instance(CONTEXT_COURSE, $COURSE->id);
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
                    $usermenu[$user->id] = fullname($user);
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
            if (has_capability('block/use_stats:seesitedetails', $context, $USER->id)){
                $users = $DB->get_records('user', array('deleted' => '0'), 'lastname', 'id, firstname, lastname');
            }
            else if (has_capability('block/use_stats:seecoursedetails', $context, $USER->id)){
            	$coursecontext = get_context_instance(CONTEXT_COURSE, $COURSE->id);
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
                    $usermenu[$user->id] = fullname($user);
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


	/**
	 * Setup the XMLRPC service, RPC calls and default block parameters.
	 * @return boolean TRUE if the installation is successfull, FALSE otherwise.
	 */
	function after_install() {
		global $CFG, $DB, $OUTPUT;
		
		// Initialising
		$result = true;
		
		/*
		 * Installing use_stats service
		 */
		if (!$DB->get_record('mnet_service', array('name' => 'use_stats'))) {
			// Installing service
			$service = new stdclass;
			$service->name = 'use_stats';
			$service->description = get_string('use_stats_rpc_service_name', 'use_stats');
			$service->apiversion = 1;
			$service->offer = 1;
			if (!$serviceid = insert_record('mnet_service', $service)){
				$OUTPUT->notify('Error installing use_stats service.');
				$result = false;
			}
		}
		
		/*
		 * Installing RPC call 'get_stats'
		 */
		// Checking if it is already installed
		if (!$DB->get_record('mnet_rpc', array('function_name' => 'use_stats_rpc_get_stats'))) {
			
			// Creating RPC call
			$rpc = new stdclass;
			$rpc->function_name = 'use_stats_rpc_get_stats';
			$rpc->xmlrpc_path = 'blocks/use_stats/rpclib.php/use_stats_rpc_get_stats';
			$rpc->parent_type = 'block';  
			$rpc->parent = 'use_stats';
			$rpc->enabled = 0; 
			$rpc->help = 'get remotely use stats information.';
			$rpc->profile = '';
			
			// Adding RPC call
			if (!$rpcid = $DB->insert_record('mnet_rpc', $rpc)) {
				$OUTPUT->notify('Error installing use_stats RPC call "get_stats".');
				$result = false;
			} else {
				// Mapping service and call
				$rpcmap = new stdclass;
				$rpcmap->serviceid = $serviceid;
				$rpcmap->rpcid = $rpcid;
				if (!$DB->insert_record('mnet_service2rpc', $rpcmap)) {
					$OUTPUT->notify('Error mapping RPC call "get_stats" to the "use_stats" service.');
					$result = false;
				}
			}
		}

		if (!$DBè->get_record('mnet_rpc', array('function_name' => 'use_stats_rpc_get_scores'))) {
			
			// Creating RPC call
			$rpc = new stdclass;
			$rpc->function_name = 'use_stats_rpc_get_scores';
			$rpc->xmlrpc_path = 'blocks/use_stats/rpclib.php/use_stats_rpc_get_scores';
			$rpc->parent_type = 'block';  
			$rpc->parent = 'use_stats';
			$rpc->enabled = 0; 
			$rpc->help = 'get remotely scores information.';
			$rpc->profile = '';
			
			// Adding RPC call
			if (!$rpcid = $DB->insert_record('mnet_rpc', $rpc)) {
				$OUTPUT->notify('Error installing use_scores RPC call "get_scores".');
				$result = false;
			} else {
				// Mapping service and call
				$rpcmap = new stdclass;
				$rpcmap->serviceid = $serviceid;
				$rpcmap->rpcid = $rpcid;
				if (!$DB->insert_record('mnet_service2rpc', $rpcmap)) {
					$OUTPUT->notify('Error mapping RPC call "get_scores" to the "use_scores" service.');
					$result = false;
				}
			}
		}
				
		// Returning result
		return $result;    
	}
	
	/**
	 * Remove the XMLRPC service.
	 * @return					boolean				TRUE if the deletion is successfull, FALSE otherwise.
	 */
	function before_delete() {
		global $CFG, $DB;
				
		// Checking if use_stats service is installed
		if (!($service = $DB->get_record('mnet_service', array('name' => 'use_stats'))))
			return true;
		
		// Uninstalling use_stats service
		$DB->delete_records('mnet_host2service', array('serviceid' => $service->id));
		$DB->delete_records('mnet_service2rpc', array('serviceid' => $service->id));
		$DB->delete_records('mnet_rpc', array('parent' => 'use_stats'));
		$DB->delete_records('mnet_service', array('name' => 'use_stats'));
		
		// Returning result
		return true;
	}
}

?>