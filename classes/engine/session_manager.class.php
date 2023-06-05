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

require_once($CFG->dirroot.'/blocks/use_stats/classes/engine/session.class.php');

/**
 * The session manager is a singleton object that manages sessions.
 * can operate in one single context (course or site), or traverse contexts if allowed to.
 */
class session_manager {

    /**
     * Session course overlapping mode : 'single' or 'multiple'
     * 'multiple' means a single session can traverse multiple courses.
     * 'single' means one session can only match a single course.
     */
    protected $mode;

    /**
     * An array of sessions indexed by starttime.
     */
    protected $sessions;

    /**
     * An ref on last session.
     */
    protected $lastsession;

    /**
     * An external log buffer where to log manager events.
     */
    protected $logbuffer;

    /**
     * Hidden constructor.
     */
    private function __construct() {
        $this->sessions = [];
        $this->lastsession = null;
        $this->loguffer = '';
    }

    /**
     * Public singleton constructor.
     */
    public static function instance() {
        static $instance;

        if (is_null($instance)) {
            $instance = new session_manager();
        }

        return $instance;
    }

    /**
     * Sets the manager mode.
     */
    public function set_mode($mode) {
        if (!in_array($mode, ['single', 'multiple'])) {
            throw new coding_exception("Bad mode");
        }
        $this->mode = $mode;
    }

    /**
     * Sets the manager mode.
     */
    public function set_log_buffer(&$logbuffer) {
        $this->logbuffer = $logbuffer;
    }

    /**
     * Extends the last session time.
     */
    protected function extend_last_session($endtime) {
        $this->lastsession->extend_endtime($endtime);
    }

    /**
     * Extends the last session event count.
     */
    protected function extend_events($events) {
        $this->lastsession->extend_events($events);
    }

    /**
     * Extends the last session elapsed time count.
     */
    protected function extend_elapsed($laptime) {
        $this->lastsession->extend_elapsed($laptime);
    }

    /**
     * Adds a course id to last available session.
     */
    public function last_session_add_course($courseid) {
        $this->lastsession->add_course($courseid);
    }

    /**
     * Register an event in session. If mode is single, add course to course list within the same session object.
     * If mode is multiple, closes the current session and opens a new one.
     * @param int $userid the event owner
     * @param int $time the event timestamp
     * @param int $courseid the concerned courseid
     * @param int $laptime the time lap to previous event of the same user.
     */
    public function register_event($userid, $time, $courseid, $laptime) {

        // this may occur at start of the track.
        if (is_null($this->lastsession)) {
            $this->start_session($userid, $time, $courseid);
        }

        if ($this->mode == 'multiple') {
            // Let the last session continue.
            $this->last_session_add_course($courseid);
        } else {
            // mode single : we need to respawn a session if course has changed.
            if ($courseid != $this->lastsession->get_course()) {
                $this->start_session($userid, $time, $courseid);
            }
            $this->extend_last_session($time);
        }
        $this->extend_events(1);
        $this->extend_elapsed($laptime);
    }

    /**
     * starts a new session, ensuring we do not have same start time.
     */
     public function start_session($userid, $starttime, $courseid) {
        if (array_key_exists($starttime, $this->sessions)) {
            // throw new coding_exception("Start time already registered");
            // same sesssion, several log entries on same time.
            return;
        }

        $session = new session($userid, $starttime);
        $session->add_course($courseid);
        $this->sessions[$starttime] = $session;
        $this->lastsession = &$session;
     }

    /** 
     * Save all sessions.
     */
    public function save() {
        if (!empty($this->sessions)) {
            foreach ($this->sessions as $s) {
                if (!$s->is_null_session()) {
                    $s->save();
                }
            }
        }
    }

    /**
     *
     */
    public function aggregate(&$aggregate) {
        if (!empty($this->sessions)) {
            $i = 0;
            foreach ($this->sessions as $s) {
                $obj = new StdClass;
                $obj->sessionstart = $s->start;
                $obj->sessionend = $s->end;
                $obj->elapsed = $s->elapsed;
                $obj->events = $s->events;
                $obj->courses = $s->courses;
                $aggregate['sessions'][$i] = $obj;
                $i++;
            }
        }
    }
}
