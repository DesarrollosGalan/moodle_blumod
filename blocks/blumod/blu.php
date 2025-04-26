<?php

require_once ('../../config.php');
require_once($CFG->dirroot. '/blocks/blumod/blu_form.php');

$id = optional_param('id', 0, PARAM_INT);
$course_id = obtenerIdCurso();
$backurl = new moodle_url('/blocks/blumod/index.php', ['courseid' => $course_id]);
$blu = null;

if ($id > 0) {
    $blu = $DB->get_record('block_blu', ['id' => $id]);
    
    if (optional_param('delete', false, PARAM_BOOL)) {
        borrarBlu($id);

        return redirect($backurl, get_string('deleted', 'block_blumod'));
    }

    $strtitle = new lang_string('modblu','block_blumod');
} else {
    $strtitle = new lang_string('addblu','block_blumod');
    $blu = new stdClass();    
    $blu->course = $course_id;
}

$context = context_system::instance();
$PAGE->set_context($context);

$bluform = new block_blumod_blu_form();


// Form processing and displaying is done here.
if ($bluform->is_cancelled()) {
    // Handle form cancel operation, if cancel button is present on form.
    return redirect($backurl);

} 


require_capability('block/blumod:manageblus', $context);

$url = new moodle_url('/block/blumod/blu.php');
$PAGE->set_url($url);


if ($data = $bluform->get_data()) {
  
  if (!isset($blu->id)) {
      $blu->description = $data->description;
      $DB->insert_record('block_blu', $blu);
  } else {
    $blu->description = $data->description;
    $DB->update_record('block_blu', $blu);
  }
  
  return redirect($backurl, get_string('saved', 'block_blumod'));
  
} 

$PAGE->set_pagelayout('admin');
$PAGE->set_heading($strtitle);
echo $OUTPUT->header();
echo $OUTPUT->heading($strtitle);

$bluform->set_data($blu);
$bluform->display();

echo $OUTPUT->footer();


function obtenerIdCurso(): int
{
    $course_id = optional_param('courseid', -1, PARAM_INT);
    if ($course_id == -1) {
        $course_id = optional_param('course', -1, PARAM_INT);
    }

    return $course_id;
}

function borrarBlu(int $id): void
{
    global $DB;
    $DB->delete_records('block_blucompetency', ['competencyid' => $id, 'bluid' => $id]);
    $DB->delete_records('block_blumod', ['blu' => $id]);
    $DB->delete_records('block_blupre', ['id_blu' => $id]);
    $DB->delete_records('block_blusub', ['id_blu' => $id]);
    $DB->delete_records('block_blu', ['id' => $id]);
}