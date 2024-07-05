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
 * @package   block_use_stats
 * @category  blocks
 * @copyright 2012 Wafa Adham,, Valery Fremaux
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/**
 * This is part of the dual release distribution system.
 * Tells wether a feature is supported or not. Gives back the
 * implementation path where to fetch resources.
 * @param string $feature a feature key to be tested.
 * @param bool $getsupported if true, returns the list of supported features.
 */
function block_use_stats_supports_feature($feature = null, $getsupported = false) {
    global $CFG;
    static $supports;

    if (!during_initial_install()) {
        $config = get_config('block_use_stats');
    }

    if (!isset($supports)) {
        /*
         * Give here descriptors of feature/subfeatures of pro vs. community
         *
         * eg : 'feature1' => ['subfeature1', 'subfeature2', etc.]
         */
        $supports = [
            'pro' => [
                'data' => ['multidimensionnal', 'activetracking', 'keepalive'],
                'api' => ['ws'],
                'view' => ['detail'],
                'format' => ['xls', 'csv', 'pdf']
            ],
            'community' => [
            	'format' => ['csv']
            ],
        ];
    }

    if ($getsupported) {
        return $supports;
    }

    // Check existance of the 'pro' dir in plugin.
    if (is_dir(__DIR__.'/pro')) {
        if ($feature == 'emulate/community') {
            return 'pro';
        }
        if (empty($config->emulatecommunity)) {
            $versionkey = 'pro';
        } else {
            $versionkey = 'community';
        }
    } else {
        $versionkey = 'community';
    }

    if (empty($feature)) {
        // Just return version.
        return $versionkey;
    }

    list($feat, $subfeat) = explode('/', $feature);

    if (!array_key_exists($feat, $supports[$versionkey])) {
        return false;
    }

    if (!in_array($subfeat, $supports[$versionkey][$feat])) {
        return false;
    }

    if (in_array($feat, $supports['community'])) {
        if (in_array($subfeat, $supports['community'][$feat])) {
            // If community exists, default path points community code.
            if (isset($prefer[$feat][$subfeat])) {
                // Configuration tells which location to prefer if explicit.
                $versionkey = $prefer[$feat][$subfeat];
            } else {
                $versionkey = 'community';
            }
        }
    }

    return $versionkey;
}

/**
 * ??
 */
function block_use_stats_setup_theme_notification() {
    global $CFG;

    if (block_use_stats_supports_feature('data/keepalive')) {
        include_once($CFG->dirroot.'/blocks/use_stats/pro/lib.php');
        block_use_stats_pro_setup_theme_notification();
    }
}