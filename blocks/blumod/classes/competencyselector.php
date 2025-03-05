<?php

defined('MOODLE_INTERNAL') || die();

class competency_selector {
    /** @var int */
    private $courseid;

    /** @var ?int */
    private $bluid;

    /** @var array */
    private $competencies = [];

    private $name = 'competencyselector';
    private $rows = 10;    

    public function __construct(int $courseid, ?int $bluid = null) {
        $this->courseid = $courseid;
        $this->bluid = $bluid;

        $this->loadCompetencies();
    }

    public function add(int $id)
    {
        global $DB;

        $sibling = new stdClass();
        $sibling->blu = $this->bluid;
        $sibling->module = $id;
        $DB->insert_record('block_blumod', $sibling);
    }

    public function del(int $id)
    {
        global $DB;
        $DB->delete_records('block_blumod', ['id' => $id]);
    }

    public function display () {
        global $PAGE;

        $available = array_replace([], $this->competencies);
        $assigned = [];

        $blumods = $this->get_blumods();

        foreach ($blumods as $blumod) {
            $assigned[$blumod->id] = $this->competencies[$blumod->module];
            unset($available[$blumod->module]);
        }

        $output = html_writer::start_tag('div', ['id' => $this->name . '_wrapper', 'class' => 'userselector' ]);

        $output .= html_writer::tag('h2', get_string('assignedcompetencies', 'block_blumod'));

        $output .= $this->displaySelect($this->name. '_assigned', $assigned);
        $output .= '<button class="btn btn-secondary btn-secondary-blu" data-action="add" data-from="' . $this->name. '_available"><i class="fa fa-link"></i> '. get_string('addblu', 'block_blumod') . '</button>';
        $output .= '<button class="btn btn-secondary btn-secondary-blu" data-action="del" data-from="' . $this->name. '_assigned"><i class="fa fa-unlink"></i> '. get_string('delblu', 'block_blumod') . '</button>';

        $output .= html_writer::end_tag('div');

        return $output;

    }

    private function loadCompetencies()
    {
        global $DB;
        
        $params = ['courseid' => $this->courseid,'deletioninprogress' => '0'];
        $sql = "SELECT cc.id, CONCAT(co.shortname, ' ', co.description) name
                     FROM {competency_coursecomp} cc
                     JOIN {competency} co
                       ON cc.competencyid = co.id
                     WHERE cc.courseid = :courseid
                     ORDER BY co.description ASC";
        $competencies = $DB->get_records_sql($sql, $params);

        foreach ($competencies as $competency) {            
            // $result = $DB->get_record($competency->module_name,['id'=>$competency->instance]);
            $this->competencies[$competency->id] = $competency->name;
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
