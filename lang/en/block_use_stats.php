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
 * Lang file for en
 *
 * @package   block_use_stats
 * @copyright 2006 Valery Fremaux <valery.fremaux@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Capabilities.
$string['use_stats:addinstance'] = 'Can add an instance'; // Is a @DYNAKEY.
$string['use_stats:myaddinstance'] = 'Can add an instance to My Page'; // Is a @DYNAKEY.
$string['use_stats:seecoursedetails'] = 'Can see detail of all users from his course'; // Is a @DYNAKEY.
$string['use_stats:seegroupdetails'] = 'Can see detail of all users from his groups'; // Is a @DYNAKEY.
$string['use_stats:seeowndetails'] = 'Can see his own detail'; // Is a @DYNAKEY.
$string['use_stats:seesitedetails'] = 'Can see detail of all users'; // Is a @DYNAKEY.
$string['use_stats:view'] = 'Can see stats'; // Is a @DYNAKEY.
$string['use_stats:export'] = 'Can export as pdf (needs trainingsessions report)'; // Is a @DYNAKEY.

// Privacy.
$string['privacy:metadata'] = "The Use Stats Block does not directely store any data belonging to users";

$string['activetrackingparams'] = 'Active tracking settings';
$string['activities'] = 'Activities';
$string['allowrule'] = 'Allow sending when matching rule';
$string['allusers'] = 'All users';
$string['blockdisplay'] = 'Block display tuning';
$string['blockname'] = 'Use Stats';
$string['byname'] = 'By name';
$string['bytimedesc'] = 'By time';
$string['cachedef_aggregate'] = 'Aggregates';
$string['capabilitycontrol'] = 'Capability';
$string['choose'] = 'Choose an option...';
$string['configbacktrackmode'] = 'Back track mode';
$string['configbacktrackmode_desc'] = 'Selects how the blocks chooses from when backtracking times.';
$string['configbacktracksource'] = 'Back track source';
$string['configbacktracksource_desc'] = 'Selects who tells the blocks the backtracking time reference.';
$string['configcalendarskin'] = 'Calendar skin';
$string['configcalendarskin_desc'] = 'Changes the calendar apparence.';
$string['configcustomtagselect'] = 'Select for custom tag';
$string['configcustomtagselect_desc'] = 'This query needs returning one unique result per log row. this result will feed the customtag {$a} column.';
$string['configdisplayactivitytimeonly'] = 'Choose what reference time to display';
$string['configdisplayactivitytimeonly_desc'] = 'You can choose what is the reference learning time to display';
$string['configdisplayothertime'] = 'Display "Out of course" time';
$string['configdisplayothertime_desc'] = 'Is set, displays the "Out of course" time course line';
$string['configenablecompilecube'] = 'Enable cube compilation';
$string['configenablecompilecube_desc'] = 'When enabled, additional dimensions are calculated using defined selects';
$string['configenrolmentfilter'] = 'Filter enrolled periods';
$string['configenrolmentfilter_desc'] = 'If active, logs will be analysed from the first available active enrolment date, or the course start as earliest. If disabled, the course start will be the only earliest limit.';
$string['configfilterdisplayunder'] = 'Filter display under';
$string['configfilterdisplayunder_desc'] = 'If not nul, only course times above the given limit (in seconds) will be displayed in the block';
$string['configfromwhen'] = 'Since ';
$string['configfromwhen_desc'] = 'Compilation period (in days till today) ';
$string['configkeepalivecontrol'] = 'Control method';
$string['configkeepalivecontrol_desc'] = 'internal data used to control sending capability';
$string['configkeepalivecontrolvalue'] = 'Control item name';
$string['configkeepalivecontrolvalue_desc'] = 'will match the rule if capability is allowed or if profile field has not null value. The default setting excludes admins.';
$string['configkeepalivedelay'] = 'Session keepalive period';
$string['configkeepalivedelay_desc'] = 'Delay between two keepalive log traces for connected users (seconds). Keep as big as possible to lower server load when many users are connected, while keeping tracking tracks consistant.';
$string['configkeepaliveenable'] = 'Enable session keepalive';
$string['configkeepaliveenable_desc'] = 'Session keepalive method will send constantly tracking pulses to moodle when a user is viewing a moodle screen on his terminal. Note that this method should be used with care, as potentially measuring inconsistant local behaviour.';
$string['configkeepaliverule'] = 'Send keepalive if';
$string['configkeepaliverule_desc'] = 'Rule to control keepalive ajax sending';
$string['configlastcompiled'] = 'Last compiled log record date';
$string['configlastcompiled_desc'] = 'On change of this track date, the cron will recalculate all logs following the given date';
$string['configlastpingcredit'] = 'Extra time credit on last ping';
$string['configlastpingcredit_desc'] = 'This amount of time (in minutes) will be systematically added to log track time count for each time a session closure or discontinuity is guessed';
$string['configonesessionpercourse'] = 'One session per course';
$string['configonesessionpercourse_desc'] = 'When enabled, use stat will split sessions each time the track changes the currrent course. If disabled, a session represents a working sequence that may use several courses.';
$string['configthreshold'] = 'Threshold';
$string['configthreshold_desc'] = 'Activity continuity threshold (minutes). Above this gap time between two successive tracks in the log, the user is considered as deconnected. Arbitrary "Last Ping Credit" time will be added to his time count.';
$string['credittime'] = '(LTC) ';
$string['datacubing'] = 'Data cubing';
$string['declaredtime'] = 'Declared time'; // Is a @DYNAKEY.
$string['denyrule'] = 'Allow sending unless matching rule';
$string['debugmode'] = 'Debug mode';
$string['dimensionitem'] = 'Observable classes';
$string['displayactivitiestime'] = 'Only time assigned to effective activities in the course';
$string['displaycoursetime'] = 'Course real time (all time spend in all contexts of the course)';
$string['errornorecords'] = 'No tracking information';
$string['eventscount'] = 'Hits';
$string['eventusestatskeepalive'] = 'Session keep alive';
$string['fixedchoice'] = 'Settings forced to course/account start date';
$string['fixeddate'] = 'From a fixed date reference';
$string['from'] = 'Since&ensp;';
$string['fromrange'] = 'From&ensp;';
$string['go'] = 'Go!';
$string['hidecourselist'] = 'Hide course times';
$string['isfiltered'] = 'Only time above {$a} secs are displayed';
$string['keepuseralive'] = 'User {$a} is still in session';
$string['loganalysisparams'] = 'Log analysis parameters';
$string['modulename'] = 'Activity tracking';
$string['noavailablelogs'] = 'No logs available';
$string['onthismoodlefrom'] = ' here since ';
$string['other'] = 'Other out of course presence';
$string['othershort'] = 'OTHER';
$string['pluginname'] = 'Use Stats';
$string['pluginname_desc'] = 'A block that compiles session times';
$string['printpdf'] = 'Print PDF';
$string['profilefieldcontrol'] = 'Profile Field';
$string['showdetails'] = 'Show details';
$string['sliding'] = 'Sliding time window';
$string['studentchoice'] = 'Students chooses';
$string['studentscansee'] = 'Students can see';
$string['task_cache_ttl'] = 'Aggregate Cache TTL';
$string['task_cleanup'] = 'Time gaps cleanup';
$string['task_compile'] = 'Time gaps compilation';
$string['timeelapsed'] = 'Time elapsed';
$string['to'] = '&ensp;to&ensp;';
$string['use_stats_description'] = 'By publishing this service, you allow remote servers to ask for reading statistics of local users.<br/>When subscribing to this service, you allow your local server to query a remote server about stats on his members.<br/>';
$string['use_stats_name'] = 'Remote access to statistics'; // Is a @DYNAKEY.
$string['use_stats_rpc_service'] = 'Remote access to statistics'; // Is a @DYNAKEY.
$string['use_stats_rpc_service_name'] = 'Remote access to statistics'; // Is a @DYNAKEY.
$string['youspent'] = 'You spent&ensp;';
$string['warningusestateenrolfilter'] = 'The enrolment checker is on in the Use Stats bloc. This may have effects on reports if the user\'s activity falls before the latest enrolment start date.';

require(__DIR__.'/pro_additional_strings.php');
