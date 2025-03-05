<?php

defined('MOODLE_INTERNAL') || die();

class reportcourse_selector {
    /** @var int */
    private $courseid;

    /** @var ?int */
    private $bluid;

    /** @var array */
    private $resources = [];

    private $name = 'reportcourseselector';
    private $rows = 10;    

    public function __construct(int $courseid, ?int $bluid = null) {
        $this->courseid = $courseid;
        $this->bluid = $bluid;

        $this->loadResources();
    }

    public function display () {
        global $PAGE;

        $available = array_replace([], $this->resources);
        $assigned = [];

        $blumods = $this->get_blumods();

        foreach ($blumods as $blumod) {
            $assigned[$blumod->id] = $this->resources[$blumod->module];
            unset($available[$blumod->module]);
        }

        $output = html_writer::start_tag('div', ['id' => $this->name . '_wrapper', 'class' => 'userselector' ]);
        $output .= html_writer::tag('h2', get_string('availableresources', 'block_blumod'));

        $output .= $this->displaySelect($this->name. '_available', $available);
        $output .= '<button class="btn btn-secondary btn-secondary-blu" data-action="add" data-from="' . $this->name. '_available"><i class="fa fa-link"></i> '. get_string('addblu', 'block_blumod') . '</button>';

        $output .= html_writer::tag('h2', get_string('assignedresources', 'block_blumod'));

        $output .= $this->displaySelect($this->name. '_assigned', $assigned);
        $output .= '<button class="btn btn-secondary btn-secondary-blu" data-action="del" data-from="' . $this->name. '_assigned"><i class="fa fa-unlink"></i> '. get_string('delblu', 'block_blumod') . '</button>';

        $output .= html_writer::end_tag('div');

        return $output;


        return $output;
    }

    private function loadResources()
    {
        global $DB;
        
        $params = ['courseid' => $this->courseid,'deletioninprogress' => '0'];
        $sql = "SELECT cm.id, cm.instance, m.name module_name
                     FROM {course_modules} cm
                      LEFT  JOIN {modules} m 
                        ON cm.module = m.id
                     WHERE cm.deletioninprogress = :deletioninprogress
                      AND cm.course = :courseid
                     ORDER BY cm.section,cm.id ASC";
        $modules = $DB->get_records_sql($sql, $params);

        foreach ($modules as $module) {            
            $result = $DB->get_record($module->module_name,['id'=>$module->instance]);

            $this->resources[$module->id] = $module->module_name . ': ' . $result->name;
        }

        $params = ['courseid' => $this->courseid];
        $sql = "SELECT gi.id, gi.itemname, 'Calificador: '
                     FROM {grade_items} gi
                     WHERE gi.courseid = :courseid
                      AND gi.itemtype = 'manual'
                     ORDER BY gi.id ASC";
        $gradeitems = $DB->get_records_sql($sql, $params);

        foreach ($gradeitems as $gradeitem) {

            $this->resources[$gradeitem->id] = 'Calificador manual: ' . $gradeitem->itemname;
        }
    }

    private function displaySelect(string $name, $data, bool $multiselect = true): string
    {

        $output = '<select name="' . $name . '" id="' . $name . '" ' .
            ($multiselect ? 'multiple="multiple" ' . 'size="' . $this->rows . '"' : '') . ' class="form-control no-overflow">' . "\n";

        foreach ($data as $id => $name) {
            $output .= "<option value='{$id}'>{$name}</option>";
        }

        $output .= "</select>";

        return $output;
    }


    private function get_blumods() {
        global $DB;        
        $params = array();
        $sql = "SELECT bm.id, bm.module
                      FROM {block_blumod} bm
                     WHERE bm.blu = :blu
                     ORDER BY bm.id ASC";
        $params = ['blu' => $this->bluid];
        $blumods = $DB->get_records_sql($sql, $params);
        
        return $blumods;
    }

}
