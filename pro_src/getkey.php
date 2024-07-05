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
$returnurl = $promanager->return_url();

$component = $promanager::$component;
$componentpath = $promanager::$componentpath;

require_once($CFG->dirroot.'/'.$componentpath.'/pro/forms/form_getkey.php');

$url = new moodle_url('/'.$componentpath.'/pro/getkey.php');
$PAGE->set_url($url);
$context = context_system::instance();
$PAGE->set_context($context);

// Security.
require_login();
require_capability('moodle/site:config', $context);
$promanager->require_pro();

$provider = required_param('provider', PARAM_TEXT);
$partnerkey = required_param('partnerkey', PARAM_TEXT);
$options = $promanager->get_activation_options($partnerkey, $provider);
// $optionsmenu = ['' => get_string('chooseoption', $promanager::$shortcomponent)];
if (!empty($options)) {
    foreach ($options as $o) {
        $optionsmenu[$o->code] = $OUTPUT->render_from_template($promanager::$component.'/pro_purchase_options', $o);
    }
}
$mform = new GetKey_Form($url, ['manager' => $promanager, 'options' => $optionsmenu]);
$formdata = new StdClass();
$formdata->provider = $provider;
$formdata->partnerkey = $partnerkey;

if ($mform->is_cancelled()) {
    redirect($promanager->return_url());
}

if ('confirm' == optional_param('what', '', PARAM_ALPHA)) {

    $data = new StdClass;
    $data->provider = required_param('provider', PARAM_ALPHA);
    $data->partnerkey = required_param('partnerkey', PARAM_TEXT);
    $data->activationoption = required_param('activationoption', PARAM_TEXT);

    if (empty($data->provider) || empty($data->partnerkey) || empty($data->activationoption)) {
        echo $OUTPUT->header();
        echo $OUTPUT->notification(get_string('missingparams', $promanager::$shortcomponent), 'error');
        echo $OUTPUT->continue_button($returnurl);
        echo $OUTPUT->footer();
        die;
    }

    $result = $promanager->get_license_key($data->partnerkey, $data->provider, $data->activationoption, $error);
    if ($result) {
        redirect($returnurl);
    } else {
        echo $OUTPUT->header();
        echo $OUTPUT->notification($error, 'error');
        echo $OUTPUT->continue_button($returnurl);
        echo $OUTPUT->footer();
        die;
    }
}

if (isset($formdata)) {
    $mform->set_data($formdata);
}

echo $OUTPUT->header();
$mform->display();
echo $OUTPUT->footer();
