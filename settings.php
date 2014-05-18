<?php

defined('MOODLE_INTERNAL') || die;

require_once $CFG->dirroot.'/blocks/use_stats/adminlib.php';

$settings->add(new admin_setting_configtext('block_use_stats_fromwhen', get_string('configfromwhen', 'block_use_stats'),
                   get_string('configfromwhen_desc', 'block_use_stats'), 60));

$settings->add(new admin_setting_configtext('block_use_stats_threshold', get_string('configthreshold', 'block_use_stats'),
                   get_string('configthreshold_desc', 'block_use_stats'), 60));

$settings->add(new admin_setting_configtext('block_use_stats_capturemodules', get_string('configcapturemodules', 'block_use_stats'),
                   get_string('configcapturemodules_desc', 'block_use_stats'), ''));

$settings->add(new admin_setting_configtext('block_use_stats_ignoremodules', get_string('configignoremodules', 'block_use_stats'),
                   get_string('configignoremodules_desc', 'block_use_stats'), ''));

$settings->add(new admin_setting_configtext('block_use_stats_lastpingcredit', get_string('configlastpingcredit', 'block_use_stats'),
                   get_string('configlastpingcredit_desc', 'block_use_stats'), 15));

$settings->add(new admin_setting_configcheckbox('block_use_stats_enablecompilelogs', get_string('configenablecompilelogs', 'block_use_stats'),
                   get_string('configenablecompilelogs_desc', 'block_use_stats'), ''));

$settings->add(new admin_setting_configcheckbox('block_use_stats_enablecompilecube', get_string('configenablecompilecube', 'block_use_stats'),
                   get_string('configenablecompilecube_desc', 'block_use_stats'), ''));

for ($i = 1 ; $i <= 6 ; $i++){
	$configkey = "block_use_stats_customtag{$i}select";
	$settings->add(new admin_setting_configtext($configkey, get_string('configcustomtagselect', 'block_use_stats').' '.$i,
	                   get_string('configcustomtagselect_desc', 'block_use_stats', $i), ''));
}

$settings->add(new admin_setting_configdatetime('block_use_stats_lastcompiled', get_string('configlastcompiled', 'block_use_stats'),
                   get_string('configlastcompiled_desc', 'block_use_stats'), ''));


//

