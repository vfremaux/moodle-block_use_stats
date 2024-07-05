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
 * @package     block_use_stats
 * @category    block
 * @author      Valery Fremaux <valery.fremaux@gmail.com>, Florence Labord <info@expertweb.fr>
 * @copyright   Valery Fremaux <valery.fremaux@gmail.com> (ActiveProLearn.com)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL
 */

include('../../../config.php');
require_once($CFG->dirroot.'/blocks/use_stats/lib.php');
require_once($CFG->dirroot.'/blocks/use_stats/pro/prolib.php');
$promanager = block_use_stats\pro_manager::instance();

$componentpath = $promanager::$componentpath;

require_once($CFG->dirroot.'/'.$componentpath.'/pro/forms/form_getkey.php');

$url = new moodle_url('/'.$componentpath.'/pro/getoptions.php');
$PAGE->set_url($url);
$context = context_system::instance();
$PAGE->set_context($context);

require_login();
require_capability('moodle/site:config', $context);
$promanager->require_pro();

$mform = new GetKeyStart_Form($url, ['manager' => $promanager]);

if ($mform->is_cancelled()) {
    redirect($promanager->return_url());
}

$data = $mform->get_data();

if ($data) {
    $params = [
        'provider' => $data->provider,
        'partnerkey' => $data->partnerkey
    ];
    $formurl = new moodle_url('/'.$componentpath.'/pro/getkey.php', $params);
    redirect($formurl);
}

echo $OUTPUT->header();
$mform->display();
echo $OUTPUT->footer();
