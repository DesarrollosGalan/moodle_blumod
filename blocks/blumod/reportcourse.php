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

 xdebug_break();

require('../../config.php');
require_once $CFG->dirroot.'/blocks/blumod/lib.php';
require_once $CFG->dirroot.'/blocks/blumod/classes/reportcourse.php';
require_once $CFG->libdir.'/tablelib.php';

 define('DEFAULT_PAGE_SIZE', 20);
 define('SHOW_ALL_PAGE_SIZE', 5000);

global $DB, $CFG, $PAGE;


$courseid = required_param('courseid', PARAM_INT);
// $id       = required_param('id', PARAM_INT); // course id.
$course = $DB->get_record('course', ['id'=>$courseid], '*', MUST_EXIST);
$url = new moodle_url('/block/blumod/reportcourse.php', array(
    'courseid' => $course->id));
$PAGE->set_url($url);
$PAGE->set_pagelayout('admin');
require_login($course);
$context = context_course::instance($course->id);

$reportcourse = new reportcourse_selector($courseid, null);
$PAGE->set_context($context);

require_capability('block/blumod:manageblus', $context);
$title2 = get_string('blumod', 'block_blumod');
// navigation_node::override_active_url($url);

$PAGE->set_title(format_string($course->shortname, true, array('context' => $context)) .': '. $title2);
$PAGE->set_heading(format_string($course->fullname, true, array('context' => $context)) .': '. $title2);
echo $OUTPUT->header();
$PAGE->set_pagelayout('standard');

$pluginname = get_string('reportblumod', 'block_blumod');
// report_helper::print_report_selector($pluginname);
// Release session lock.
\core\session\manager::write_close();

// $reporttable = report_blumod_get_table_name(); // Log table to use for fetaching records.

$baseurl = new moodle_url('/blocks/blumod/reportcourse.php', array(
    'courseid' => $course->id
));
$select = groups_allgroups_course_menu($course, $baseurl, true, $currentgroup);

$table = new flexible_table('blumod-report-'.$course->id);
$table->course = $course;

$table->define_columns(array('bluid', 'bluname', 'blumodid', 'blumodmodule', 'cmmodule', 'cminstance', 'resource_name', 'competencyid', 'competencyshortname'));
$table->define_headers(array('BLUID', 'BLU', 'BLUMODID', 'BLUMODMODULE', 'CMMODULE', 'CMINSTANCE', 'RESOURCE_NAME', 'COMPETENCYID', 'COMPETENCY'));
$table->define_baseurl($baseurl);
$table->set_attribute('class', 'generaltable generalbox reporttable');
$table->sortable(true, 'lastname', SORT_ASC);
$table->no_sorting('select');
$table->set_control_variables(array(
                                TABLE_VAR_SORT    => 'ssort',
                                TABLE_VAR_HIDE    => 'shide',
                                TABLE_VAR_SHOW    => 'sshow',
                                TABLE_VAR_IFIRST  => 'sifirst',
                                TABLE_VAR_ILAST   => 'silast',
                                TABLE_VAR_PAGE    => 'spage',
                                ));
$table->setup();

// $table->initialbars($totalcount > $perpage);
// $table->pagesize($perpage, $matchcount);

$data = $reportcourse->loadData();
foreach ($data as $row) {
    $table->add_data($row);
}
$table->finish_output();

// Selector BLUs
echo html_writer::start_tag('div');
echo html_writer::tag('h2', get_string('blus', 'block_blumod'));
echo html_writer::end_tag('div');

echo '<form class="form-inline" action="reportcourse.php" method="get"><div>'."\n".
'<input type="hidden" name="courseid" value="'.$course->id.'" />';
'<input type="hidden" name="bluid" value="'.$block_blu->id.'" />'."\n";
echo '<input type="submit" value="'.get_string('go').'" class="btn btn-primary"/>'."\n</div></form>\n";


echo $OUTPUT->footer();
