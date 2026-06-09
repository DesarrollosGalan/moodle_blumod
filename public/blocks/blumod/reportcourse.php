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

// xdebug_break();
require('../../config.php');
require_once $CFG->dirroot.'/blocks/blumod/lib.php';
require_once $CFG->dirroot.'/blocks/blumod/classes/reportcourse.php';
require_once $CFG->libdir.'/tablelib.php';

 define('DEFAULT_PAGE_SIZE', 50);

global $DB, $CFG, $PAGE, $OUTPUT;


$courseid = required_param('courseid', PARAM_INT);
// $id       = required_param('id', PARAM_INT); // course id.
$course = $DB->get_record('course', ['id'=>$courseid], '*', MUST_EXIST);
$url = new moodle_url('/blocks/blumod/reportcourse.php', array(
    'courseid' => $course->id));
$PAGE->set_url($url);
require_login($course);
$context = context_course::instance($course->id);

$download = optional_param('download', '', PARAM_ALPHA);

$reportcourse = new reportcourse_selector($courseid);
$PAGE->set_context($context);

require_capability('block/blumod:manageblus', $context);
$title2 = get_string('blumod', 'block_blumod');

$pluginname = get_string('reportblumod', 'block_blumod');
\core\session\manager::write_close();

$baseurl = new moodle_url('/blocks/blumod/reportcourse.php', array(
    'courseid' => $course->id
));
$currentgroup = 0;
$select = groups_allgroups_course_menu($course, $baseurl, true, $currentgroup);

$table = new flexible_table('blumod-report-'.$course->id);
// $table->course = $course;

$table->define_columns(array('bluid', 'bluname', 'blumodid', 'blumodmodule', 'cmmodule', 'cminstance', 'resource_name', 'competencyid', 'competencyshortname'));
$table->define_headers(array('BLUID', 'BLU', 'BLUMODID', 'BLUMODMODULE', 'CMMODULE', 'CMINSTANCE', 'RESOURCE_NAME', 'COMPETENCYID', 'COMPETENCY'));
$table->set_attribute('class', 'generaltable generalbox reporttable');
$table->sortable(true, 'bluname', SORT_ASC);
$table->collapsible(true);
$table->pageable(true);
$table->show_download_buttons_at([TABLE_P_TOP, TABLE_P_BOTTOM]);
$table->is_downloading($download, 'blumod-report-' . $course->id, $pluginname);

if (!$table->is_downloading()) {
    $PAGE->set_pagelayout('admin');
    $PAGE->set_title(format_string($course->shortname, true, array('context' => $context)) .': '. $title2);
    $PAGE->set_heading(format_string($course->fullname, true, array('context' => $context)) .': '. $title2);
    echo $OUTPUT->header();
    $PAGE->set_pagelayout('standard');
}

// $table->initialbars($totalcount > $perpage);
// $table->pagesize($perpage, $matchcount);
$table->define_baseurl($baseurl);

$data = $reportcourse->loadData();
$totalrows = is_countable($data) ? count($data) : 0;

$perpage = optional_param('perpage', DEFAULT_PAGE_SIZE, PARAM_INT);
$table->pagesize($perpage, $totalrows);
$table->setup();

$params = ['courseid' => $course->id];

$columns = array('bluid', 'bluname', 'blumodid', 'blumodmodule', 'cmmodule', 'cminstance', 'resource_name', 'competencyid', 'competencyshortname');
$sortcols = $table->get_sort_columns();

if (!empty($sortcols)) {
    $primary = key($sortcols);
    $dir = current($sortcols);
    $params['tsort'] = $primary;
    $params['tdir']  = $dir;
}
$baseurl = new moodle_url('/blocks/blumod/reportcourse.php', $params);
$table->define_baseurl($baseurl);


if (!empty($sortcols)) {
    $primary = key($sortcols);
    $dir = current($sortcols);
    $colindex = array_search($primary, $columns, true);
    if ($colindex !== false) {
        usort($data, function ($a, $b) use ($colindex, $dir) {
            $v1 = $a[$colindex] ?? '';
            $v2 = $b[$colindex] ?? '';
            $asc = ($dir === SORT_ASC);
            if (is_numeric($v1) && is_numeric($v2)) {
                $cmp = $v1 <=> $v2;
                return $asc ? $cmp : -$cmp;
            }
            $cmp = strnatcasecmp((string)$v1, (string)$v2);
            return $asc ? $cmp : -$cmp;
        });
    }
}

if (!$table->is_downloading()) {
    $start = (int)$table->get_page_start();
    $length = (int)$table->get_page_size();
    $pageddata = array_slice($data, $start, $length);
} else {
    $pageddata = $data;
}

foreach ($pageddata as $row) {
    $row_sanitized = [
        $row['0'] ?? '',
        $row['1'] ?? '',
        $row['2'] ?? '',
        $row['3'] ?? '',
        $row['4'] ?? '',
        $row['5'] ?? '',
        $row['6'] ?? '',
        $row['7'] ?? '',
        $row['8'] ?? ''
    ];
    $table->add_data($row_sanitized);
}

$table->finish_output();
if ($table->is_downloading()) {
    exit;
}

if (!$table->is_downloading()) {
    echo html_writer::start_tag('div');
    echo html_writer::tag('h2', get_string('blus', 'block_blumod'));
    echo html_writer::end_tag('div');
    echo '<form class="form-inline" action="reportcourse.php" method="get"><div>' . "\n" .
    '<input type="hidden" name="courseid" value="' . $course->id . '" />' . "\n" .
    '<input type="submit" value="' . get_string('go') . '" class="btn btn-primary"/>' . "\n</div></form>\n";
    echo $OUTPUT->footer();
}
