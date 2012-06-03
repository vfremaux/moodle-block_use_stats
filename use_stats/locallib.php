<?php

/**
* @version Moodle 2.2
* @param int $from
* @param int $to
*/
function use_stats_extract_logs($from, $to, $for = null, $course = null){
    global $CFG, $USER, $DB;

    $for = (is_null($for)) ? $USER->id : $for ;
    
    if (is_array($for)){
        $userlist = implode("','", $for);
        $userclause = " userid IN ('{$userlist}') AND ";
    } else {
        $userclause = " userid = {$for} AND ";
    }
    
    $courseclause = (!is_null($course)) ? " AND course = $course " : '' ;

    $sql = "
       SELECT
         id,
         course,
         time,
         module,
         userid,
         cmid
       FROM
         {log}
       WHERE
         $userclause
         time > ? AND 
         time < ?
         $courseclause
       ORDER BY
         time
    ";
    
    if($rs = $DB->get_recordset_sql($sql, array($from, $to))){
        $logs = array();
        foreach($rs as $log){
            $logs[] = $log;
        }
        $rs->close($rs);
        return $logs;
    }
    return array();    
}

/**
* given an array of log records, make a displayable aggregate
* @param array $logs
* @param string $dimension
*/
function use_stats_aggregate_logs($logs, $dimension, $origintime = 0){
    global $CFG, $DB, $OUTPUT;

    if (isset($CFG->block_use_stats_capturemodules)){
        $modulelist = explode(',', $CFG->block_use_stats_capturemodules);
    } else {
        print_error('errornotinitialized', 'block_use_stats');
    }

    if (isset($CFG->block_use_stats_ignoremodules)){
        $ignoremodulelist = explode(',', $CFG->block_use_stats_ignoremodules);
    } else {
    	$ignoremodulelist = array();
    }
    
    $currentuser = 0;

    $aggregate = array();        
    if (!empty($logs)){
        $logs = array_values($logs);

        $memlap = 0; // will store the accumulated time for in the way but out of scope laps.

        for($i = 0 ; $i < count($logs) - 2 ; $i++){
            $log = $logs[$i];
        	$currentuser = $log->userid;
            $lognext = $logs[$i + 1];
            $lap = $lognext->time - $log->time;

			if (in_array($log->$dimension, $ignoremodulelist)){
				$lap = 0;
			}

            if ($lap == 0) continue;

            // do not loose last lap, but set sometime to it
            // if ($lap > $CFG->block_use_stats_threshold * MINSECS) $lap = ($CFG->block_use_stats_threshold * MINSECS) / 2;
            if ($lap > $CFG->block_use_stats_threshold * MINSECS) $lap = 600;

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
                $OUTPUT->notice('unknown dimension');
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
    }
    
    // we need finally adjust some times from time recording activities
    
	if (array_key_exists('scorm', $aggregate)){
		foreach(array_keys($aggregate['scorm']) as $cmid){
			if ($cm = get_record('course_modules', 'id', $cmid)){ // these are all scorms

				// scorm activities have their accurate recorded time
				$realtotaltime = 0;
				if ($realtimes = $DB->get_records_select('scorm_scoes_track', " element = 'cmi.core.total_time' AND scormid = $cm->instance AND userid = $currentuser ", 'id', 'id,element,value')){
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
    global $CFG, $DB, $OUTPUT;

    if (isset($CFG->block_use_stats_capturemodules)){
        $modulelist = explode(',', $CFG->block_use_stats_capturemodules);
    } else {
        print_error('errornotinitiaized', 'block_use_stats');
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
				$lap[$userid] = 600;
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
                $OUTPUT->notice('unknown dimension');
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
            
            // $origintime = $log[$userid]->time;
        }
    }
    return $aggregate;    
}
