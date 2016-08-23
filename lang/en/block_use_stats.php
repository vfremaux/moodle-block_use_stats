<?php

// Capabilities
$string['use_stats:addinstance'] = 'Can add an instance';
$string['use_stats:myaddinstance'] = 'Can add an instance to My Page';
$string['use_stats:seecoursedetails'] = 'Can see detail of all users from his course';
$string['use_stats:seegroupdetails'] = 'Can see detail of all users from his groups';
$string['use_stats:seeowndetails'] = 'Can see his own detail';
$string['use_stats:seesitedetails'] = 'Can see detail of all users';
$string['use_stats:view'] = 'Can see stats';

$string['activetrackingparams'] = 'Active tracking settings';
$string['activities'] = 'Activities';
$string['allowrule'] = 'Allow sending when matching rule';
$string['allusers'] = 'All users';
$string['blockdisplay'] = 'Block display tuning';
$string['blockname'] = 'Use Stats';
$string['byname'] = 'By name';
$string['bytimedesc'] = 'By time';
$string['capabilitycontrol'] = 'Capability';
$string['configcapturemodules'] = 'Capture modules list';
$string['configcapturemodules_desc'] = 'Modules that are considered in the detail analysis';
$string['configcustomtagselect'] = 'Select for custom tag';
$string['configcustomtagselect_desc'] = 'This query needs returning one unique result per log row. this result will feed the customtag {$a} column.';
$string['configdisplayactivitytimeonly'] = 'Choose what reference time to display';
$string['configdisplayactivitytimeonly_desc'] = 'You can choose what is the reference learning time to display';
$string['configdisplayothertime'] = 'Display "Out of course" time';
$string['configdisplayothertime_desc'] = 'Is set, displays the "Out of course" time course line';
$string['configenablecompilecube'] = 'Enable cube compilation';
$string['configenablecompilecube_desc'] = 'When enabled, additional dimensions are calculated using defined selects';
$string['configenablecompilelogs'] = 'Enable gaps compilation';
$string['configenablecompilelogs_desc'] = 'When enabled, use stat compile logs and gaps on cron';
$string['configfilterdisplayunder'] = 'Filter display under';
$string['configfilterdisplayunder_desc'] = 'If not nul, only course times above the given limit (in seconds) will be displayed in the block';
$string['configfromwhen'] = 'Since ';
$string['configfromwhen_desc'] = 'Compilation period (in days till today) ';
$string['configignoremodules'] = 'Ignore modules list';
$string['configignoremodules_desc'] = 'Ignore times from this modules';
$string['configkeepalivecontrol'] = 'Control method';
$string['configkeepalivecontrol_desc'] = 'internal data used to control sending capability';
$string['configkeepalivecontrolvalue'] = 'Control item name';
$string['configkeepalivecontrolvalue_desc'] = 'will match the rule if capability is allowed or if profile field has not null value. The default setting excludes admins.';
$string['configkeepalivedelay'] = 'Session keepalive period';
$string['configkeepalivedelay_desc'] = 'Delay between two keepalive log traces for connected users (seconds). Keep as big as possible to lower server load when many users are connected, while keeping tracking tracks consistant.';
$string['configkeepaliverule'] = 'Send keepalive if';
$string['configkeepaliverule_desc'] = 'Rule to control keepalive ajax sending';
$string['configlastcompiled'] = 'Last compiled log record date';
$string['configlastcompiled_desc'] = 'On change of this track date, the cron will recalculate all logs following the given date';
$string['configlastpingcredit'] = 'Extra time credit on last ping';
$string['configlastpingcredit_desc'] = 'This amount of time (in minutes) will be systematically added to log track time count for each time a session closure or discontinuity is guessed';
$string['configonesessionpercourse'] = 'One session per course';
$string['configonesessionpercourse_desc'] = 'When enabled, use stat will split sessions each time the track changes the currrent course. If disabled, a session represents a working sequence that may use several courses.';
$string['configstudentscanuse_desc'] = 'Students can see the block (for their own)';
$string['configstudentscanuseglobal_desc'] = 'Allow students see the use stat block in global spaces (MyMoodle, out of course, for their own)';
$string['configthreshold'] = 'Threshold';
$string['configthreshold_desc'] = 'Activity continuity threshold (minutes). Above this gap time between two successive tracks in the log, the user is considered as deconnected. Arbitrary "Last Ping Credit" time will be added to his time count.';
$string['credittime'] = 'Credit time';
$string['datacubing'] = 'Data cubing';
$string['declaredtime'] = 'Declared time';
$string['denyrule'] = 'Allow sending unless matching rule';
$string['dimensionitem'] = 'Observable classes';
$string['displayactivitiestime'] = 'Only time assigned to effective activities in the course';
$string['displaycoursetime'] = 'Course real time (all time spend in all contexts of the course)';
$string['errornorecords'] = 'No tracking information';
$string['errornotinitialized'] = 'The module is not initialized. Contact administrator.';
$string['eventscount'] = 'Hits';
$string['eventusestatskeepalive'] = 'Session keep alive';
$string['from'] = 'Since ';
$string['ignored'] = 'Module/Activity ignored in tracking';
$string['isfiltered'] = 'Only time above {$a} secs are displayed';
$string['keepuseralive'] = 'User {$a} is still in session';
$string['lastcompiled'] = 'Last compiled log record';
$string['loganalysisparams'] = 'Log analysis parameters';
$string['modulename'] = 'Activity tracking';
$string['noavailablelogs'] = 'No logs available';
$string['onthismoodlefrom'] = ' here since ';
$string['other'] = 'Other out of course presence';
$string['othershort'] = 'OTHER';
$string['pluginname'] = 'Use Stats';
$string['printpdf'] = 'Print PDF';
$string['profilefieldcontrol'] = 'Profile Field';
$string['showdetails'] = 'Show details';
$string['studentscansee'] = 'Students can see';
$string['task_cache_ttl'] = 'Aggregate Cache TTL';
$string['task_cleanup'] = 'Time gaps cleanup';
$string['task_compile'] = 'Time gaps compilation';
$string['timeelapsed'] = 'Time elapsed';
$string['use_stats_description'] = 'By publishing this service, you allow remote servers to ask for reading statistics of local users.<br/>When subscribing to this service, you allow your local server to query a remote server about stats on his members.<br/>';
$string['use_stats_name'] = 'Remote access to statistics';
$string['use_stats_rpc_service'] = 'Remote access to statistics';
$string['use_stats_rpc_service_name'] = 'Remote access to statistics';
$string['youspent'] = 'You spent ';
$string['cachedef_aggregate'] = 'Time aggregates';
