moodle-block_use_stats
======================

Time based use stats block

Version 2015121900
=======================
Adding keepalive event handling

Calling the use_stats notification handler :

the notification handler allows all pages of Moodle in user agent to trigger a 10 min (adjustable)
request to punch a log event in th user log track.

invoking the notification hook needs to be present on every page of Moodle, so the good
way to implement it is to customize the core site renderer. Another alternative way could be to add 
the use_stats plug into a generic footer include in theme layouts.

class theme_customtheme_core_renderer extends theme_core_renderer {

    function standard_end_of_body_html() {
        global $CFG;

        $str = '';

        // use_stats notification plug / VF Consulting 2015-12-19
        if (file_exists($CFG->dirroot.'/blocks/use_stats/lib.php')) {
            include_once $CFG->dirroot.'/blocks/use_stats/lib.php';
            $str .= block_use_stats_setup_theme_notification();
        }
        $str .= parent::standard_end_of_body_html();
        return $str;
    }
}