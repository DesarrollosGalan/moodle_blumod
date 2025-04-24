<?php

defined('MOODLE_INTERNAL') || die();

class competency_selector {
    /** @var int */
    private $courseid;

    /** @var ?int */
    private $bluid;

    /** @var array */
    private $competencies = [];
    private $blucompetencies = [];

    private $name = 'competencyselector';
    private $rows = 10;    

    public function __construct(int $courseid, ?int $bluid = null) {
        $this->courseid = $courseid;
        $this->bluid = $bluid;
        $this->loadCompetencies();
        // $this->get_competency();
    }

    public function add(int $id)
    {
        global $DB;

        $sibling = new stdClass();
        $sibling->bluid = $this->bluid;
        $sibling->competencyid = $id;
        $DB->insert_record('block_blucompetency', $sibling);
    }

    public function del(int $id)
    {
        global $DB;
        $DB->delete_records('block_blucompetency', ['competencyid' => $id, 'bluid' => $this->bluid]);
    }

    public function display () {
        global $PAGE;

        $available = array_replace([], $this->competencies);
        $assigned = [];

        // $blucompetencies = $this->get_blucompetencies();
        $this->get_blucompetencies();
        $assigned = array_replace([], $this->blucompetencies);

        // $assigned = array_replace([], $this->blucompetencies);


        // foreach ($this->blucompetencies as $blucompetency) {
        //     $assigned[$blucompetency->bc_id] = $this->competencies[$blucompetency->bc_id];
        //     unset($available[$blucompetency->bc_id]);
        // }
        foreach ($this->blucompetencies as $id => $blucompetency) {
            // $assigned[$id] = $this->competencies[$id];
            unset($available[$id]);
        }

        $output = html_writer::start_tag('div', ['id' => $this->name . '_wrapper', 'class' => 'userselector' ]);

        $output .= html_writer::tag('h2', get_string('availablecompetencies', 'block_blumod'));
        $output .= $this->displaySelect($this->name. '_available', $available);
        $output .= '<button class="btn btn-secondary btn-secondary-blu" data-action="add" data-from="' . $this->name. '_available"><i class="fa fa-link"></i> '. get_string('addblu', 'block_blumod') . '</button>';
        $output .= html_writer::end_tag('div');

        $output .= html_writer::tag('h2', get_string('assignedcompetencies', 'block_blumod'));
        $output .= $this->displaySelect($this->name. '_assigned', $assigned);
        $output .= '<button class="btn btn-secondary btn-secondary-blu" data-action="del" data-from="' . $this->name. '_assigned"><i class="fa fa-unlink"></i> '. get_string('delblu', 'block_blumod') . '</button>';
        $output .= html_writer::end_tag('div');

        return $output;

    }

    private function loadCompetencies()
    {

        global $DB;
        // Usar el picker de competency?????? NO lo consigo y lo dejo aparcado usando el mismo modelo que recursos

        // $params = ['courseid' => $this->courseid,'deletioninprogress' => '0'];
        // $sql = "SELECT co.id id, CONCAT(co.shortname, ' ', co.description) name
        //              FROM {competency_coursecomp} cc
        //              JOIN {competency} co
        //                ON cc.competencyid = co.id
        //              WHERE cc.courseid = :courseid
        //              ORDER BY co.description DESC";
        $sql = "SELECT co.id id, cf.id cf_id, cf.shortname cf_shortname, co.shortname name
        FROM {competency_framework} cf
        JOIN {competency} co ON cf.id = co.competencyframeworkid
        ORDER BY cf_shortname, name ASC";
        $params = [];
        $results = $DB->get_records_sql($sql, $params);

        $cf_id_comp = "";
        foreach ($results as $result) {
            if ($cf_id_comp != $result->cf_id) {
                $cf_id_comp = $result->cf_id;
                $this->competencies['F' . $result->cf_id] = $result->cf_shortname;
            }            
            $this->competencies[$result->id] = ' - ' . $result->name;
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


    private function get_blucompetencies() {
        global $DB;      
        
        // xdebug_break();

        $params = array();
        $sql = "SELECT bc.competencyid bc_id, bc.bluid, co.shortname name, cf.shortname cf_shortname, cf.id cf_id
                      FROM {block_blucompetency} bc
                     JOIN {competency} co ON bc.competencyid = co.id
                     JOIN {competency_framework} cf ON co.competencyframeworkid = cf.id
                     WHERE bc.bluid = :blu
                     ORDER BY cf_shortname, name ASC";
        $params = ['blu' => $this->bluid];
        $results = $DB->get_records_sql($sql, $params);

        $cf_id_comp = "";
        foreach ($results as $result) {
            if ($cf_id_comp != $result->cf_id) {
                $cf_id_comp = $result->cf_id;
                $this->blucompetencies['F' . $result->cf_id] = $result->cf_shortname;
            }            
            $this->blucompetencies[$result->bc_id] = ' - ' . $result->name;
        }

        // return $this->blucompetencies;
    }

    /*
    private function get_competency() {
        global $DB;        

        $sql = "SELECT cf.id cf_id, cf.shortname cf_shortname, co.id id, CONCAT(co.shortname, ' ', co.description) name
        FROM {competency_framework} cf
        JOIN {competency} co ON cf.id = co.competencyframeworkid
        ORDER BY cf_shortname, name DESC";
        $params = [];
        $competencies = $DB->get_records_sql($sql, $params);
        
        return $competencies;
    }
    */
    
}
