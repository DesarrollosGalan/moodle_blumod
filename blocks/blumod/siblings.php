<?php

require_once ('../../config.php');
require_once $CFG->dirroot.'/blocks/blumod/classes/siblingselector.php';

$id = required_param('id', PARAM_INT);

$bp_selector = new sibling_selector(sibling_selector::PRE, $id);
$bs_selector = new sibling_selector(sibling_selector::SUB, $id);

$action = optional_param('action', null, PARAM_TEXT);
$modid = optional_param('modid', null, PARAM_INT);

if ($action && $modid) {
    switch ($action)
    {
        case 'addpre':
            $bp_selector->add($modid);
            break;
        case 'delpre':
            $bp_selector->del($modid);
            break;
        case 'addsub':
            $bs_selector->add($modid);
            break;
        case 'delsub':
            $bs_selector->del($modid);
            break;
    }
}


// Selector BLU PRE
echo html_writer::start_tag('div');
echo html_writer::start_tag('p');
echo html_writer::tag('h2', get_string('preselectors', 'block_blumod'));
echo html_writer::end_tag('p');


echo $bp_selector->display();
echo html_writer::end_tag('div');

// Selector BLU SUB
echo html_writer::start_tag('div');
echo html_writer::start_tag('p');
echo html_writer::tag('h2', get_string('subselectors', 'block_blumod'));
echo html_writer::end_tag('p');

echo $bs_selector->display();
echo html_writer::end_tag('div');
