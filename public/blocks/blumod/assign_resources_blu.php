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
 * BLU mod block resources to BLU assignment page
 * A Moodle block for BLU
 * @package blocks
 * @author: 
 * @date: 2026
 */

require_once '../../config.php';
require_once $CFG->dirroot.'/blocks/blumod/lib.php';
require_once $CFG->dirroot.'/blocks/blumod/classes/resourceselector.php';

global $DB, $CFG, $PAGE;

$courseid = required_param('courseid', PARAM_INT);

$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$url = new moodle_url('/blocks/blumod/assign_resources_blu.php', ['courseid'=>$courseid]);
$PAGE->set_url($url);
require_login($course);

$PAGE->requires->js_call_amd('block_blumod/assign_resources_blu', 'init');

$context = context_course::instance($courseid);
$PAGE->set_context($context);

require_capability('block/blumod:manageblus', $context);
$title = get_string('blumod', 'block_blumod');
navigation_node::override_active_url($url);

$PAGE->set_title($title);
$PAGE->set_heading($course->fullname);
$PAGE->set_pagelayout('standard');

$PAGE->navbar->add(get_string('assignblusresource', 'block_blumod'));

echo $OUTPUT->header();

echo html_writer::start_tag('div');
echo html_writer::tag('h5', get_string('assignblusresource', 'block_blumod'));
echo html_writer::end_tag('div');

echo html_writer::start_tag('div');
echo html_writer::tag('h2', get_string('resourcesincourse', 'block_blumod'));
$resource_selector = new resource_selector($courseid);
echo $resource_selector->display_available_resources_only();
echo html_writer::end_tag('div');

echo html_writer::start_tag('div', ['id' => 'blus']);

echo html_writer::end_tag('div');

echo $OUTPUT->footer();