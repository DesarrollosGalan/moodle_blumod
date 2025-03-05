<?php

require_once ('../../config.php');
require_once $CFG->dirroot.'/blocks/blumod/classes/resourceselector.php';

$courseid = required_param('courseid', PARAM_INT);
$id = optional_param('id', null, PARAM_INT);

$resource_selector = new resource_selector($courseid, $id);

$action = optional_param('action', null, PARAM_TEXT);
$modid = optional_param('modid', null, PARAM_INT);

if ($action && $modid != null) {
    switch ($action)
    {
        case 'add':
            $resource_selector->add($modid);
            break;
        case 'del':
            $resource_selector->del($modid);
            break;
    }
}


// Selector Activities with BLU - SELECT para actividades ASIGNADAS
echo html_writer::start_tag('div');
echo html_writer::start_tag('p');
echo html_writer::end_tag('p');
$blumod_selector = new resource_selector($courseid, $id);
echo $blumod_selector->display();
echo html_writer::end_tag('div');

