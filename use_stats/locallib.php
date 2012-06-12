<?php
/**
*
* @param int $from
* @param int $to
* @param int $for a user ID 
* @param int $courseid
*/
function use_stats_extract_logs($from, $to, $for = null, $courseid = null){
    global $CFG, $USER;
    
    if (!isset($CFG->block_use_stats_lastpingcredit)){
    	set_config('block_use_stats_lastpingcredit', 5);
    }

    $for = (is_null($for)) ? $USER->id : $for ;

    if ($courseid){
	    $coursecontext = get_context_instance(CONTEXT_COURSE, $courseid);
	        
	    // we search first enrol time for this user
	    $sql = "
			SELECT
				id,
				MIN(timestart) as timestart
			FROM
				{$CFG->prefix}role_assignments ra
			WHERE
				contextid = $coursecontext->id AND
				userid = $for
	    ";
	    $firstenrol = get_record_sql($sql);
    
	    $from = max($from, $firstenrol->timestart);
	}
    
    if (is_array($for)){
        $userlist = implode("','", $for);
        $userclause = " userid IN ('{$userlist}') AND ";
    } else {
        $userclause = " userid = {$for} AND ";
    }
    
    $courseclause = (!is_null($courseid)) ? " AND course = $courseid " : '' ;

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
         {$CFG->prefix}log
       WHERE
         $userclause
         time > $from AND 
         time < $to AND
         ((course = 1 AND action = 'login') OR
          1
          $courseclause)
       ORDER BY
         time
    ";
    
    if($rs = get_recordset_sql($sql)){
        $logs = array();
        while($log = rs_fetch_next_record($rs)){
            $logs[] = $log;
        }
        rs_close($rs);
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
function use_stats_aggregate_logs($logs, $dimension, $origintime = 0){
    global $CFG, $COURSE;
    
    // will record session aggregation state as current session ordinal
    $sessionid = 0;

    if (isset($CFG->block_use_stats_capturemodules)){
        $modulelist = explode(',', $CFG->block_use_stats_capturemodules);
    } else {
        error("The module is not initialized. Please setup global configuration for the use_sats module.");
    }

    if (isset($CFG->block_use_stats_ignoremodules)){
        $ignoremodulelist = explode(',', $CFG->block_use_stats_ignoremodules);
    } else {
    	$ignoremodulelist = array();
    }
    
    $currentuser = 0;

    $aggregate = array();        
	$aggregate['sessions'] = array();
	
    if (!empty($logs)){
        $logs = array_values($logs);

        $memlap = 0; // will store the accumulated time for in the way but out of scope laps.
        
        for($i = 0 ; $i < count($logs) ; $i++){
            $log = $logs[$i];
        	$currentuser = $log->userid; // we "guess" here the real identity of the log's owner.
        	
        	if (isset($logs[$i + 1])){
	            $lognext = $logs[$i + 1];
	            $lap = $lognext->time - $log->time;
	        } else {
	        	$lap = $CFG->block_use_stats_lastpingcredit * MINSECS;
	        }

			if (in_array($log->$dimension, $ignoremodulelist)){
				$lap = 0;
			}

            if ($lap == 0) continue;

            // do not loose last lap, but set sometime to it
            // if ($lap > $CFG->block_use_stats_threshold * MINSECS) $lap = ($CFG->block_use_stats_threshold * MINSECS) / 2;
            $sessionpunch = false;
            if ($lap > $CFG->block_use_stats_threshold * MINSECS){
            	$lap = $CFG->block_use_stats_lastpingcredit * MINSECS;
            	if ($lognext->action != 'login' && $log->action != 'login') $sessionpunch = true;
            }

            switch($dimension){
                case 'module':{
                    if (!in_array($log->$dimension, $modulelist)){
                        $memlap += $lap;
                        continue;
                    } else {
                        $lap += $memlap;
                        $memlap = 0;
                    }
                    break;
                }
            }

            if (!isset($log->$dimension)){
                notice('unknown dimension');
            }
            
           	/// Per login session aggregation
           	if ($log->action != 'login' && @$lognext->action == 'login'){
           		// repair first visible session track that has no login
           		if (!isset($aggregate['sessions'][$sessionid]->sessionstart)){
           			 $aggregate['sessions'][$sessionid]->sessionstart = $logs[0]->time;
           		}
           		$aggregate['sessions'][$sessionid]->sessionend = $log->time + ($CFG->block_use_stats_lastpingcredit * MINSECS);
           	}
           	if ($log->action == 'login'){
           		if (@$lognext->action != 'login'){
	           		$sessionid++;
	           		$aggregate['sessions'][$sessionid]->elapsed = $lap;
	           		$aggregate['sessions'][$sessionid]->sessionstart = $log->time;
           		}
           		else {
           			continue;
           		}
           	} else {
           		if ($sessionpunch){
           			// this record is the last one of the current session.
           			$aggregate['sessions'][$sessionid]->sessionend = $log->time + ($CFG->block_use_stats_lastpingcredit * MINSECS);
	           		$sessionid++;
           			$aggregate['sessions'][$sessionid]->sessionstart = $lognext->time;
           			$aggregate['sessions'][$sessionid]->elapsed = $lap;
           		} else {
	           		if (!isset($aggregate['sessions'][$sessionid])){
	           			$aggregate['sessions'][$sessionid]->sessionstart = $log->time;
		           		$aggregate['sessions'][$sessionid]->elapsed = $lap;
	           		} else {
		           		$aggregate['sessions'][$sessionid]->elapsed += $lap;
		           	}
		        }
        	}
                        
           /// Standard global lap aggregation
            if (array_key_exists($log->$dimension, $aggregate) && array_key_exists($log->cmid, $aggregate[$logs[$i]->$dimension])){
                $aggregate[$log->$dimension][$log->cmid]->elapsed += $lap;
                $aggregate[$log->$dimension][$log->cmid]->events += 1;
            } else {
                $aggregate[$log->$dimension][$log->cmid]->elapsed = $lap;
                $aggregate[$log->$dimension][$log->cmid]->events = 1;
            }
            
            $origintime = $log->time;
        }
    
	    // we need check if time credits are used and override by credit earned
		if (file_exists($CFG->dirroot.'/mod/checklist/xlib.php')){
			include_once($CFG->dirroot.'/mod/checklist/xlib.php');
			$checklists = checklist_get_instances($COURSE->id, true); // get timecredit enabled ones
			
			foreach($checklists as $ckl){
				if ($credittimes = checklist_get_credittimes($ckl->id, 0, $currentuser)){
					foreach($credittimes as $credittime){
						if (!empty($CFG->checklist_strict_credits)){
							// if strict credits, do override time even if real time is higher 
							$aggregate[$credittime->modname][$credittime->cmid]->elapsed = $credittime->credittime;
							$aggregate[$credittime->modname][$credittime->cmid]->timesource = 'credit';
						} else {
							// this processes validated modules that although have no logs
							if (!isset($aggregate[$credittime->modname][$credittime->cmid])){
								$aggregate[$credittime->modname][$credittime->cmid] = new StdClass;
								$aggregate[$credittime->modname][$credittime->cmid]->elapsed = 0;
								$aggregate[$credittime->modname][$credittime->cmid]->events = 0;
							}
							if ($aggregate[$credittime->modname][$credittime->cmid]->elapsed <= $credittime->credittime){
								$aggregate[$credittime->modname][$credittime->cmid]->elapsed = $credittime->credittime;
								$aggregate[$credittime->modname][$credittime->cmid]->timesource = 'credit';
							}
						}
					}
				}
			}
		}
	}
    
    // we need finally adjust some times from time recording activities
	if (array_key_exists('scorm', $aggregate)){
		foreach(array_keys($aggregate['scorm']) as $cmid){
			if ($cm = get_record('course_modules', 'id', $cmid)){ // these are all scorms

				// scorm activities have their accurate recorded time
				$realtotaltime = 0;
				if ($realtimes = get_records_select('scorm_scoes_track', " element = 'cmi.core.total_time' AND scormid = $cm->instance AND userid = $currentuser ", 'id', 'id,element,value')){
					foreach($realtimes as $rt){
						$realcomps = preg_match("/(\d\d):(\d\d):(\d\d)\./", $rt->value, $matches);
						$realtotaltime += $matches[1] * 3600 + $matches[2] * 60 + $matches[3];
					}
				}
				if ($aggregate['scorm'][$cmid]->elapsed < $realtotaltime) $aggregate['scorm'][$cmid]->elapsed = $realtotaltime;
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
function use_stats_aggregate_logs_per_user($logs, $dimension, $origintime = 0){
    global $CFG;

    if (isset($CFG->block_use_stats_capturemodules)){
        $modulelist = explode(',', $CFG->block_use_stats_capturemodules);
    } else {
        error("The module is not initialized. Please setup global configuration for the use_sats module.");
    }

    $aggregate = array();        
    if (!empty($logs)){
        $logs = array_values($logs);

        $memlap = array(); // will store the accumulated time for in the way but out of scope laps.

		$end = count($logs) - 2;

		
        for($i = 0 ; $i < $end ; $i++){
        	$userid = $logs[$i]->userid;
            $log[$userid] = $logs[$i];

			// we fetch the next receivable log for this user
            $j = $i + 1;
            while( ($logs[$j]->userid != $userid) && $j < $end && (($logs[$j]->time - $log[$userid]->time) < $CFG->block_use_stats_threshold * MINSECS)){
            	$j++;
            }
            if ($j < $end && (($logs[$j]->time - $log[$userid]->time) < $CFG->block_use_stats_threshold * MINSECS)){
	            $lognext[$userid] = $logs[$j];
            	$lap[$userid] = $lognext[$userid]->time - $log[$userid]->time;
	        } else {
				$lap[$userid] = $CFG->block_use_stats_lastpingcredit * MINSECS;
			}
			
			if ($lap[$userid] == 0) continue;

            switch($dimension){
                case 'module':{
                    if (!in_array($log[$userid]->$dimension, $modulelist)){
                        $memlap[$userid] = 0 + @$memlap[$userid] + $lap[$userid];
                        continue;
                    } else {
                        $lap[$userid] += 0 + @$memlap[$userid];
                        $memlap[$userid] = 0;
                    }
                    break;
                }
            }


            if (!isset($log[$userid]->$dimension)){
                notice('unknown dimension');
            }
            
            if (!isset($aggregate[$userid])){
            	$aggregate[$userid] = array();
            }
            
           /// Standard global lap aggregation
            if (array_key_exists($log[$userid]->$dimension, $aggregate[$userid]) && array_key_exists($log[$userid]->cmid, $aggregate[$userid][$logs[$i]->$dimension])){
                $aggregate[$userid][$log[$userid]->$dimension][$log[$userid]->cmid]->elapsed += $lap[$userid];
                $aggregate[$userid][$log[$userid]->$dimension][$log[$userid]->cmid]->events += 1;
            } else {
                $aggregate[$userid][$log[$userid]->$dimension][$log[$userid]->cmid]->elapsed = $lap[$userid];
                $aggregate[$userid][$log[$userid]->$dimension][$log[$userid]->cmid]->events = 1;
            }

           	/// Per login session aggregation
           	if ($log[$userid]->action != 'login' && @$lognext[$userid]->action == 'login'){
           		$aggregate[$userid]['sessions'][$sessionid]->sessionend = $log[$userid]->time + ($CFG->block_use_stats_lastpingcredit * MINSECS);
           	}
           	if ($log[$userid]->action == 'login'){
           		if (@$lognext[$userid]->action != 'login'){
	           		$sessionid++;
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
            
            
            // $origintime = $log[$userid]->time;
        }
    }
    return $aggregate;    
}
