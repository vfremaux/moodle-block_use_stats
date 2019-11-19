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
 * CLI interface for creating a test plan
 *
 * @package 
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

global $CLI_VMOODLE_PRECHECK;

define('CLI_SCRIPT', true);
define('CACHE_DISABLE_ALL', true);
$CLI_VMOODLE_PRECHECK = true; // force first config to be minimal

require(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');

if (!isset($CFG->dirroot)) {
    die ('$CFG->dirroot must be explicitely defined in moodle config.php for this script to be used');
}

require_once($CFG->dirroot.'/lib/clilib.php');         // cli only functions

// CLI options.
list($options, $unrecognized) = cli_get_params(
    array(
        'help' => false,
        'host' => false,
    ),
    array(
        'h' => 'help',
        'H' => 'host'
    )
);

// Display help.
if (!empty($options['help'])) {

    echo "Options:
-h, --help              Print out this help
--host                  the hostname

Example from Moodle root directory:
\$ sudo -u www-data /usr/bin/php blocks/use_stats/cli/build_session_cache.php
\$ sudo -u www-data /usr/bin/php blocks/use_stats/cli/build_session_cache.php --host=http://myvhost.mymoodle.org
";
    // Exit with error unless we're showing this because they asked for it.
    exit(empty($options['help']) ? 1 : 0);
}

// now get cli options

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error("Not recognized options ".$unrecognized);
}

if (!empty($options['host'])) {
    // Arms the vmoodle switching.
    echo('Arming for '.$options['host']."\n"); // Mtrace not yet available.
    define('CLI_VMOODLE_OVERRIDE', $options['host']);
}

// Replay full config whenever. If vmoodle switch is armed, will switch now config.

require(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php'); // Global moodle config file.
echo('Config check : playing for '.$CFG->wwwroot."\n");

require_once($CFG->dirroot.'/blocks/use_stats/locallib.php');

$distinctusers = $DB->get_records_menu('block_use_stats_session', array(), 'DISTINCT userid, userid');

if (!$distinctusers) {
    die('No users to process');
}

$total = count($distinctusers);
$i = 0;
foreach ($distinctusers as $uid) {

    $sessionstarts = $DB->get_records_menu('block_use_stats_session', array('userid' => $uid), 'DISTINCT sessionstart, sessionstart');
    if ($sessionstarts) {
        foreach (array_keys($sessionstarts) as $st) {
            $count = $DB->count_records('block_use_stats_session', array('userid' => $uid, 'sessionstart' => $st));
            if ($count > 1) {
                $records = $DB->get_records('block_use_stats_session', array('userid' => $uid, 'sessionstart' => $st), 'sessionend');
                $first = array_shift($records);
                $todelete = array();
                foreach ($records as $rid => $r) {
                    $first->sessionend = $r->sessionend;
                    $todelete[] = $r->id;
                }
                $DB->update_record('block_use_stats_session', $first);
                if (!empty($todelete)) {
                    $DB->delete_records_list('block_use_stats_session', 'id', $todelete);
                }
            }
            $done = round($i / $total * $scale);
            $donepercent = round($i / $total * 100;
            echo str_repeat('*', $done).str_repeat('-', $scale - $done)." ($donepercent %)\r";
        }
    }
    $i++;
}