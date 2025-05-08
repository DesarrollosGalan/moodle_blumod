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
require_once $CFG->dirroot . '/blocks/blumod/lib.php';
require_once $CFG->dirroot . '/blocks/blumod/classes/reportcourse.php';
require_once $CFG->libdir . '/tablelib.php';
// require_once $CFG->libdir . '/csvlib.class.php';


define('DEFAULT_PAGE_SIZE', 25);
// xdebug_break();

global $DB, $CFG, $PAGE;
\core\session\manager::write_close();

$pluginname = get_string('reportblumod', 'block_blumod');

$courseid = required_param('courseid', PARAM_INT);
$course = $DB->get_record('course', ['id'=>$courseid], '*', MUST_EXIST);
$baseurl = new moodle_url('/blocks/blumod/reportcourse.php', array(
    'courseid' => $course->id));

$PAGE->set_url($baseurl);
$PAGE->set_pagelayout('admin');
$PAGE->set_course($course);
$title2 = get_string('blumod', 'block_blumod');
$context = context_course::instance($course->id);
$PAGE->set_context($context);
$PAGE->set_title(format_string($course->shortname, true, array('context' => $context)) .': '. $title2);
$PAGE->set_heading(format_string($course->fullname, true, array('context' => $context)) .': '. $title2);
// $PAGE->set_pagelayout('standard');
require_login($course);
require_capability('block/blumod:manageblus', $context);

$select = groups_allgroups_course_menu($course, $baseurl, true, $currentgroup);

$reportcourse = new reportcourse_selector($courseid, null);
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

$table->is_downloadable(true);
$download_type = optional_param('download', '', PARAM_ALPHA);
$filename_report = 'blumod_report_' . date('Ymd_Hi');
$table->is_downloading($download_type, $filename_report, get_string('blumod', 'block_blumod'));
$table->show_download_buttons_at([TABLE_P_TOP, TABLE_P_BOTTOM]);
$page_table = optional_param('spage', 0, PARAM_INT);

$table->setup();

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

if ($table->is_downloading()) {
    foreach ($data as $row) {
        $table->add_data((array) $row);
    }
    $table->finish_output();
    exit;
}

echo $OUTPUT->header();

$start_table = $page_table * DEFAULT_PAGE_SIZE;
$end_table = $start_table + DEFAULT_PAGE_SIZE;

foreach (array_slice($data, $start_table, DEFAULT_PAGE_SIZE) as $row) {
    $table->add_data((array) $row);
}

$table->finish_output();
echo $OUTPUT->footer();
