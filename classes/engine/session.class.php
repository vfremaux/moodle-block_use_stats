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
 * Master block class for use_stats compiler
 *
 * @package    blocks_use_stats
 * @category   blocks
 * @author     Valery Fremaux (valery.fremaux@gmail.com)
 * @copyright  Valery Fremaux (valery.fremaux@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_use_stats\engine;

defined('MOODLE_INTERNAL') || die();

use coding_exception;
use StdClass;

/**
 * a session is a portion of time of activity of a single user. A session
 * can operate in one single context (course or site), or traverse contexts if allowed to.
 */
class session {

    public $userid;

    public $courses;

    public $start;

    public $end;

    /**
     * real log time elapsed in session.
     */
    public $elapsed;

    /**
     * real log events in session.
     */
    public $events;

    public function __construct($userid, $starttime) {
        $this->userid = $userid;
        $this->start = $starttime;
        $this->end = $starttime;
        $this->elapsed = 0;
        $this->events = 1;
        $this->courses = [];
    }

    /**
     * Pushes end time up to $endtime
     */
    public function extend_endtime($endtime) {
        if ($endtime > $this->end) {
            $this->end = $endtime;
        }
    }

    /**
     * Add elapsed
     */
    public function extend_elapsed($elapsed) {
        $this->elapsed += $elapsed;
    }

    /**
     * Add elapsed
     */
    public function extend_events($events) {
        $this->events += $events;
    }

    /**
     * Add a course to list.
     */
    public function add_course($courseid) {
        if (!in_array($courseid, $this->courses)) {
            $this->courses[] = $courseid;
        }
    }

    /**
     * Get the course id when unique.
     */
    public function get_course() {
        if (count($this->courses) != 1) {
            throw new coding_exception("get_course only possible when single course");
        }
        return $this->courses[0];
    }

    /**
     *
     */
     public function is_null_session() {
        return $this->start == $this->end;
     }

    /**
     * Saves a session record in database. Note that session timerange should NEVER overlap for a single user.
     */
    public function save() {
        global $DB;

        $transaction = $DB->start_delegated_transaction();
        $params = ['userid' => $this->userid, 'sessionstart' => $this->start, 'sessionend' => $this->end];
        $oldrec = $DB->get_record('block_use_stats_session', $params);
        if (!$oldrec) {
            $rec = new StdClass;
            $rec->userid = $this->userid;
            $rec->sessionstart = $this->start;
            $rec->sessionend = $this->end;
            $rec->courses = implode(',', $this->courses);
            $DB->insert_record('block_use_stats_session', $rec);
        }
        $transaction->allow_commit();
    }

    /** 
     * Export data as an unclassed simple standard object structure.
     */
    public function export() {
        $obj = new StdClass;
        $obj->userid = $this->userid;
        $obj->elapsed = $this->elapsed;
        $obj->events = $this->events;
        $obj->sessionstart = $this->start;
        $obj->sessionsend = $this->end;

        return $obj;
    }
}