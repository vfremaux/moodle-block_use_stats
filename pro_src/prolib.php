<?php
// This file is NOT part of Moodle - http://moodle.org/
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
 * @package     block_use_stats
 * @category    block
 * @author      Valery Fremaux <valery.fremaux@gmail.com>, Florence Labord <info@expertweb.fr>
 * @copyright   Valery Fremaux <valery.fremaux@gmail.com> (ActiveProLearn.com)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_use_stats;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/local/vfcore/pro/lib.php');

final class pro_manager extends \local_vfcore\license_manager {

    public static $shortcomponent = 'block_use_stats';
    public static $component = 'block_use_stats';
    public static $componentpath = 'blocks/use_stats';
    public static $componentsettings = 'blocksettinguse_stats';

    protected function __construct() {
        assert(1);
    }

    /**
     * Singleton implementation. Why it is better than pure static class :
     * Allows manipulation of methods through a single instance that
     * DO NOT mention the class name, so more portable accross plugins.
     * The class name is used just once per script when calling to the singleton.
     */
    public static function instance() {
        static $manager;

        if (is_null($manager)) {
            $manager = new pro_manager();
        }

        return $manager;
    }
}