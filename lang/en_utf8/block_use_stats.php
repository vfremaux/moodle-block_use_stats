<?php

global $COURSE;

$string['blockname'] = 'Use Stats For '.$COURSE->teacher;
$string['blocknameforstudents'] = 'Use Stats For '.$COURSE->student;
$string['configcapturemodules'] = 'Modules to consider';
$string['configcapturemodules_desc'] = 'Logging modules that are considered in the detail analysis';
$string['configignoremodules'] = 'Modules to ignore';
$string['configignoremodules_desc'] = 'Ignore log times from this modules';
$string['configfromwhen'] = 'Compilation period ';
$string['configfromwhen_desc'] = 'Default value for the compilation period (in days till today) ';
$string['configlastpingcredit'] = 'Extra time credit on last ping';
$string['configlastpingcredit_desc'] = 'On the last transaction within a working session, no information on the effective use time the user spent on last page is available. This parameter allows adding an extra time (in minutes) credit to the last ping of the user.';
$string['configstudentscanuse'] = 'Students can see the block (for their own)';
$string['configstudentscanuseglobal'] = 'Allow students see the use stat block in global spaces (MyMoodle, out of course, for their own)';
$string['configthreshold'] = 'Activity continuity threshold';
$string['configthreshold_desc'] = 'Above a certain period of inactivity (minutes), we might consider the working session has been splitted';
$string['configlastcompiled'] = 'Last compiled log record date';
$string['configlastcompiled'] = 'Related to automated precompilations';
$string['credittime'] = 'Credit time: '; //used in reports
$string['dimensionitem'] = 'Observable classes';
$string['errornorecords'] = 'No tracking information';
$string['eventscount'] = 'Nombre de hits';
$string['from'] = 'Since ';
$string['modulename'] = 'Activity tracking';
$string['noavailablelogs'] = 'No logs available';
$string['onthisMoodlefrom'] = ' on this Moodle Site since ';
$string['showdetails'] = 'Show details';
$string['timeelapsed'] = 'Elapsed time';
$string['use_stats:seeowndetails'] = 'Can see his own detail';
$string['use_stats:seesitedetails'] = 'Can see detail of all users';
$string['use_stats:seecoursedetails'] = 'Can see detail of all users from his course';
$string['use_stats:seegroupdetails'] = 'Can see detail of all users from his groups';
$string['use_stats:view'] = 'Can see stats';
$string['use_stats_rpc_service'] = 'Remote access to statistics';
$string['use_stats_name'] = 'Remote access to statistics';
$string['use_stats_description'] = 'By publishing this service, you allow remote servers to ask for reading statistics of local users.<br/>When subscribing to this service, you allow your local server to query a remote server about stats on his members.<br/>';
$string['youspent'] = 'You already spent ';
$string['ignored'] = 'Module/Activity ignored in tracking';

