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
 * A Moodle block for BLU
 * @package blocks
 * @author: 
 * @date: 2022
 */

 // use core\report_helper;

require('../../config.php');
require_once $CFG->dirroot.'/blocks/blumod/lib.php';
require_once $CFG->dirroot.'/blocks/blumod/classes/reportcourse.php';
require_once $CFG->libdir.'/tablelib.php';
require_once $CFG->libdir . '/csvlib.class.php';


define('DEFAULT_PAGE_SIZE', 25);

global $DB, $CFG, $PAGE;

$courseid = required_param('courseid', PARAM_INT);
// $id       = required_param('id', PARAM_INT); // course id.

$course = $DB->get_record('course', ['id'=>$courseid], '*', MUST_EXIST);
$baseurl = new moodle_url('/blocks/blumod/reportcourse.php', array(
    'courseid' => $course->id));
$PAGE->set_url($baseurl);
$PAGE->set_pagelayout('admin');
require_login($course);
$context = context_course::instance($course->id);

$reportcourse = new reportcourse_selector($courseid, null);
$PAGE->set_context($context);

require_capability('block/blumod:manageblus', $context);
$title2 = get_string('blumod', 'block_blumod');
// navigation_node::override_active_url($baseurl);

$PAGE->set_title(format_string($course->shortname, true, array('context' => $context)) .': '. $title2);
$PAGE->set_heading(format_string($course->fullname, true, array('context' => $context)) .': '. $title2);
echo $OUTPUT->header();
$PAGE->set_pagelayout('standard');

$pluginname = get_string('reportblumod', 'block_blumod');
// report_helper::print_report_selector($pluginname);
// Release session lock.
\core\session\manager::write_close();

// $baseurl = new moodle_url('/blocks/blumod/reportcourse.php', array(
//     'courseid' => $course->id
// ));


echo html_writer::start_tag('form', ['class' => 'form-inline', 'method' => 'get', 'action' => $baseurl]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'courseid', 'value' => $courseid]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'exportall', 'value' => true]);
echo html_writer::empty_tag('input', ['type' => 'submit', 'value' => get_string('exportall', 'block_blumod'), 'class' => 'btn btn-secondary']);
echo html_writer::end_tag('form');

$select = groups_allgroups_course_menu($course, $baseurl, true, $currentgroup);

$data = $reportcourse->loadData();
$number_of_records = count($data);

$table = new flexible_table('blumod-report-'.$course->id);
$table->course = $course;

$table->initialbars(true);

$table->define_columns(array('bluid', 'bluname', 'blumodid', 'blumodmodule', 'cmmodule', 'cminstance', 'resourcename', 'competencyid', 'competencyshortname'));
$table->define_headers(array('BLUID', 'BLU', 'BLUMODID', 'BLUMODMODULE', 'CMMODULE', 'CMINSTANCE', 'RESOURCENAME', 'COMPETENCYID', 'COMPETENCY'));
$table->define_baseurl($baseurl);
$table->sortable(true);
$table->set_attribute('class', 'generaltable generalbox reporttable');
$table->pagesize(DEFAULT_PAGE_SIZE, $number_of_records);
$table->set_control_variables(array(
                                TABLE_VAR_SORT    => 'ssort',
                                TABLE_VAR_HIDE    => 'shide',
                                TABLE_VAR_SHOW    => 'sshow',
                                TABLE_VAR_IFIRST  => 'sifirst',
                                TABLE_VAR_ILAST   => 'silast',
                                TABLE_VAR_PAGE    => 'spage',
                                ));
$page_table = optional_param('spage', 0, PARAM_INT);
$table->setup();

// xdebug_break();

$sortcolumn = $table->get_sql_sort();
if (empty($sortcolumn)) {
    $sortcolumn = 'bluname'; // Default column to sort by.
}

uasort($data, function ($a, $b) use ($sortcolumn) {
    $sortparts = explode(',', $sortcolumn);
    foreach ($sortparts as $sortpart) {
        list($column, $direction) = explode(' ', trim($sortpart));
        if ($a->$column == $b->$column) {
            continue;
        }
        return ($direction == 'ASC') ? ($a->$column < $b->$column ? -1 : 1) : ($a->$column > $b->$column ? -1 : 1);
    }
    return 0;
});

$start_table = $page_table * DEFAULT_PAGE_SIZE;
$end_table = $start_table + DEFAULT_PAGE_SIZE;

foreach (array_slice($data, $start_table, DEFAULT_PAGE_SIZE) as $row) {
    $table->add_data((array) $row);
}
$table->finish_output();
echo $OUTPUT->footer();

// xdebug_break();
$exportall = optional_param('exportall', false, PARAM_BOOL);
if ($exportall) {
    export_to_csv($data);
    exit;
}

function export_to_csv($data) {
    $csvwriter = new csv_export_writer();
    $csvwriter->set_filename('blumod_report_' . date('Ymd_His'));

    // Add headers.
    $csvwriter->add_data([
        'bluid',
        'bluname',
        'blumodid',
        'blumodmodule',
        'cmmodule',
        'cminstance',
        'resourcename',
        'competencyid',
        'competencyshortname'
    ]);

    // Add data rows.
    foreach ($data as $row) {
        $csvwriter->add_data((array) $row);
    }

    // Download the CSV file.
    $csvwriter->download_file();
    exit;
}