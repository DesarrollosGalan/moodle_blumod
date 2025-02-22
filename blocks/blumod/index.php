<?php
// use block_blumod\fetcher_blumod;

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
 * Configurable Reports
 * A Moodle block for BLU
 * @package blocks
 * @author: 
 * @date: 2009
 */

require_once '../../config.php';
require_once $CFG->dirroot.'/blocks/blumod/lib.php';
require_once $CFG->dirroot.'/blocks/blumod/classes/bluselector.php';

global $DB, $CFG, $PAGE;

$courseid = required_param('courseid', PARAM_INT);

$course = $DB->get_record('course', ['id'=>$courseid], '*', MUST_EXIST);
$url = new moodle_url('/block/blumod/index.php', ['courseid'=>$courseid]);
$PAGE->set_url($url);
require_login($course);

$PAGE->requires->js('/blocks/blumod/blu.js',true);

$context = context_course::instance($course->id);
$PAGE->set_context($context);

require_capability('block/blumod:manageblus', $context);
$title = get_string('blumod', 'block_blumod');
navigation_node::override_active_url($url);

$PAGE->set_title($title);
$PAGE->set_heading($course->fullname);
$PAGE->set_pagelayout('standard');

$PAGE->navbar->add(get_string('managerblumod', 'block_blumod'));

echo $OUTPUT->header();

// Selector BLUs
echo html_writer::start_tag('div');
echo html_writer::start_tag('p');
echo html_writer::tag('h2', get_string('blus', 'block_blumod'));
echo html_writer::end_tag('p');
$blu_selector = new blu_selector($courseid);
echo $blu_selector->display();
echo html_writer::end_tag('div');

$url = new moodle_url('/blocks/blumod/blu.php', ['courseid' => $course->id]);
echo $OUTPUT->single_button($url, get_string('addblu', 'block_blumod'), 'get', ['class' => 'singlebutton singlebutton-blu']);
$url = new moodle_url('/blocks/blumod/blu.php', ['courseid' => $course->id, 'id' => -1]);
echo $OUTPUT->single_button($url, get_string('modblu', 'block_blumod'), 'get', ['class' => 'singlebutton singlebutton-blu validate-selected']);
$url = new moodle_url('/blocks/blumod/blu.php', ['courseid' => $course->id, 'delete' => 1, 'id' => -1]);
echo $OUTPUT->single_button($url, get_string('delblu', 'block_blumod'), 'del', ['class' => 'singlebutton singlebutton-blu validate-selected']);


echo html_writer::start_tag('div', ['id' => 'siblings']);

echo html_writer::end_tag('div');

echo $OUTPUT->footer();
