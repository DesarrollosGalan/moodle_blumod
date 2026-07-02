<?php

require_once(__DIR__ . '/../../../../config.php');

$courseid = required_param('courseid', PARAM_INT);
$course   = get_course($courseid);
$context  = context_course::instance($courseid);

require_login($course);

$PAGE->set_url('/blocks/blumod/graph/view.php', ['courseid' => $courseid]);
$PAGE->set_context($context);
$PAGE->set_course($course);
$PAGE->set_title(get_string('semanticview', 'block_blumod'));
$PAGE->set_heading($course->fullname);
$PAGE->set_pagelayout('report');

// Librerías de terceros para Cytoscape (igual que en index.html original).
$PAGE->requires->js(new moodle_url('https://unpkg.com/cytoscape/dist/cytoscape.min.js'), true);
$PAGE->requires->js(new moodle_url('https://unpkg.com/dagre@0.8.5/dist/dagre.min.js'), true);
$PAGE->requires->js(new moodle_url('https://unpkg.com/cytoscape-dagre@2.5.0/cytoscape-dagre.js'), true);
$PAGE->requires->js(new moodle_url('https://unpkg.com/cytoscape-expand-collapse/cytoscape-expand-collapse.js'), true);

$PAGE->requires->js(new moodle_url('/blocks/blumod/graph/javascript/graph.js'), true);
$PAGE->requires->js(new moodle_url('/blocks/blumod/graph/javascript/table.js'), true);
$PAGE->requires->js(new moodle_url('/blocks/blumod/graph/javascript/conexiones.js'), true);
$PAGE->requires->css(new moodle_url('/blocks/blumod/graph/styles.css'));


echo $OUTPUT->header();

// Se inyectan los parámetros de Moodle que conexiones.js necesita
// (wwwroot, sesskey, courseid)
echo html_writer::script('
    window.FORJALENS = {
        wwwroot:  ' . json_encode($CFG->wwwroot) . ',
        sesskey:  ' . json_encode(sesskey()) . ',
        courseid: ' . json_encode($courseid) . '
    };
');
?>

<h2><?php echo get_string('semanticview', 'block_blumod'); ?></h2>
<p><?php echo format_string($course->fullname); ?></p>

<label for="querySelect"><?php echo get_string('semanticviews_query', 'block_blumod'); ?>:</label>
<select id="querySelect">
    <option value="blus_without_components" data-mode="table">
        Learning Units sin componentes
    </option>
    <option value="component_blu_relations" data-mode="graph">
        Componentes &rarr; Learning Units
    </option>
    <option value="competency_blu_relations" data-mode="graph">
        Competencias &rarr; Learning Units
    </option>
    <option value="course_structure" data-mode="obsidian">
        Course Structure
    </option>
    <option value="course_structure" data-mode="compound">
        Course Structure compound
    </option>
</select>

<button id="loadBtn"><?php echo get_string('semanticviews_view', 'block_blumod'); ?></button>

<table id="resultsTable">
    <thead>
        <tr>
            <th>Learning Unit</th>
            <th>ID</th>
        </tr>
    </thead>
    <tbody></tbody>
</table>

<div id="wrapper" style="display:none;">
    <div id="graph"></div>
    <div id="legend"></div>
</div>
<div id="popup"></div>

<?php
echo $OUTPUT->footer();
