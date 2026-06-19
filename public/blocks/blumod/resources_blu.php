<?php

require_once('../../config.php');

global $DB;

$courseid = required_param('courseid', PARAM_INT);
$resourceid = optional_param('resourceid', 0, PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);
$bluid = optional_param('bluid', 0, PARAM_INT);

$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
/** @var \context $context */
$context = context_course::instance($courseid);
require_login($course);
require_capability('block/blumod:manageblus', $context);

if ($resourceid <= 0) {
    echo html_writer::tag('p', get_string('selectresourcefirst', 'block_blumod'));
    return;
}

if ($action !== '' && $bluid > 0) {
    if ($action === 'add') {
        if (!$DB->record_exists('block_blumod', ['course' => $courseid, 'module' => $resourceid, 'blu' => $bluid])) {
            $record = new stdClass();
            $record->course = $courseid;
            $record->module = $resourceid;
            $record->blu = $bluid;
            $DB->insert_record('block_blumod', $record);
        }
    } else if ($action === 'del') {
        $DB->delete_records('block_blumod', ['course' => $courseid, 'module' => $resourceid, 'blu' => $bluid]);
    }
}

$allblus = $DB->get_records_sql(
    "SELECT b.id, b.description
       FROM {block_blu} b
      WHERE b.course = :courseid
   ORDER BY b.description ASC",
    ['courseid' => $courseid]
);

$assignedrows = $DB->get_records_sql(
    "SELECT bm.blu
       FROM {block_blumod} bm
      WHERE bm.course = :courseid
        AND bm.module = :resourceid
   ORDER BY bm.blu ASC",
    ['courseid' => $courseid, 'resourceid' => $resourceid]
);

$assignedids = [];
foreach ($assignedrows as $row) {
    $assignedids[(int)$row->blu] = true;
}

$available = [];
$assigned = [];
foreach ($allblus as $blu) {
    $id = (int)$blu->id;
    if (isset($assignedids[$id])) {
        $assigned[$id] = $blu->description;
    }
    $available[$id] = $blu->description; // se muestran siempre todas las BLU
}

function block_blumod_render_blu_select(string $id, array $data): string {
    $rows = 10;
    $output = '<select name="' . $id . '" id="' . $id . '" multiple="multiple" size="' . $rows . '" class="form-control no-overflow">' . "\n";

    foreach ($data as $value => $name) {
        $safevalue = s((string)$value);
        $safename = s((string)$name);
        $output .= '<option value="' . $safevalue . '">' . $safename . '</option>';
    }

    $output .= '</select>';
    return $output;
}

echo html_writer::start_div('mb-3');
echo html_writer::tag('h2', get_string('allblus', 'block_blumod'));
echo block_blumod_render_blu_select('blus_available', $available);
echo '<button class="btn btn-secondary btn-secondary-blu" data-action="add" data-from="blus_available"><i class="fa fa-link"></i> ' . get_string('addblu', 'block_blumod') . '</button>';
echo html_writer::end_div();

echo html_writer::start_div('mb-3');
echo html_writer::tag('h2', get_string('assignedblus', 'block_blumod'));
echo block_blumod_render_blu_select('blus_assigned', $assigned);
echo '<button class="btn btn-secondary btn-secondary-blu" data-action="del" data-from="blus_assigned"><i class="fa fa-unlink"></i> ' . get_string('delblu', 'block_blumod') . '</button>';
echo html_writer::end_div();
