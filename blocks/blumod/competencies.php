<?php

require_once ('../../config.php');
require_once $CFG->dirroot.'/blocks/blumod/classes/competencyselector.php';

$courseid = required_param('courseid', PARAM_INT);
$bluid = optional_param('bluid', null, PARAM_INT);

xdebug_break();

$competency_selector1 = new competency_selector($courseid, $bluid);

$action = optional_param('action', null, PARAM_TEXT);
$modid = optional_param('modid', null, PARAM_INT);

if ($action && $modid != null) {
    switch ($action)
    {
        case 'add':
            $competency_selector1->add($modid);
            break;
        case 'del':
            $competency_selector1->del($modid);
            break;
    }
}

// Selector Competencies BLU
echo html_writer::start_tag('div');
echo html_writer::start_tag('p');
echo html_writer::end_tag('p');
$competency_selector = new competency_selector($courseid, $bluid);
echo $competency_selector->display();
echo html_writer::end_tag('div');
