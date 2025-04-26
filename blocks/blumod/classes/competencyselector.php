<?php

defined('MOODLE_INTERNAL') || die();

use core_competency\external;

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
        $sibling->bluid = $this->bluid;
        $sibling->competencyid = $id;
        $DB->insert_record('block_blucompetency', $sibling);

        try {
            $result = external::add_competency_to_course($this->courseid, $id);
            if ($result) {
                echo "Competency successfully added to the course.";
            } else {
                echo "Failed to add competency to the course.";
            }
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage();
        }

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
        // xdebug_break();
        $blucompetencies = $this->get_blucompetencies();

        foreach ($blucompetencies as $blucompetency) {
            $assigned[$blucompetency->bc_competencyid] = $this->competencies[$blucompetency->bc_competencyid];
            if ($blucompetency->co_shortname != 'Framework') {
                unset($available[$blucompetency->bc_competencyid]);
            }
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

        $sql = "SELECT co.id id, cf.id cf_id, cf.shortname cf_shortname, co.shortname co_shortname
        FROM {competency_framework} cf
        JOIN {competency} co ON cf.id = co.competencyframeworkid
        ORDER BY cf_shortname, co_shortname ASC";
        $params = [];
        $results = $DB->get_records_sql($sql, $params);

        $cf_id_comp = "";
        foreach ($results as $result) {
            if ($cf_id_comp != $result->cf_id) {
                $cf_id_comp = $result->cf_id;
                $this->competencies['F' . $result->cf_id] = '▼ ' . $result->cf_shortname;
            }            
            $this->competencies[$result->id] = $result->co_shortname;
        }

    }
    
    private function displaySelect(string $name, $data, bool $multiselect = true): string
    {

        // xdebug_break();
        $output = '<select name="' . $name . '" id="' . $name . '" ' .
            ($multiselect ? 'multiple="multiple" ' . 'size="' . $this->rows . '"' : '') . ' class="form-control no-overflow">' . "\n";

        foreach ($data as $id => $name) {
            if (strpos($id, 'F') === 0) {
                $output .= "<option value='{$id}' style='font-weight: bold;'>{$name}</option>";
            } else {
                $output .= "<option value='{$id}'>{$name}</option>";
            }
        }

        $output .= "</select>";

        return $output;
    }

    private function get_blucompetencies() {
        global $DB;      
        
        $params = array();
        $sql = "SELECT bc.competencyid bc_competencyid, bc.bluid, co.shortname co_shortname, cf.shortname cf_shortname, cf.id cf_id
                      FROM {block_blucompetency} bc
                     JOIN {competency} co ON bc.competencyid = co.id
                     JOIN {competency_framework} cf ON co.competencyframeworkid = cf.id
                     WHERE bc.bluid = :blu
                     ORDER BY cf_shortname, co_shortname ASC";
        $params = ['blu' => $this->bluid];
        $results = $DB->get_records_sql($sql, $params);

        // $blucompetencies = array_replace([], $results);
        $cf_id_comp = "";
        foreach ($results as $result) {
            if ($cf_id_comp != $result->cf_id) {
                $cf_id_comp = $result->cf_id;
                // $blucompetencies['F' . $result->cf_id] = $result->cf_shortname;
                $blucompetencies['F' . $result->cf_id] = (object)[
                    'bc_competencyid' => 'F' . $result->cf_id,
                    'bluid' => $result->bluid,
                    'co_shortname' => 'Framework',
                    'cf_shortname' => '▼ ' . $result->cf_shortname,
                    'cf_id' => $result->cf_id
                ];
            }            
            $blucompetencies[$result->bc_competencyid] = (object)[
                'bc_competencyid' => $result->bc_competencyid,
                'bluid' => $result->bluid,
                'co_shortname' => $result->co_shortname,
                'cf_shortname' => $result->cf_shortname,
                'cf_id' => $result->cf_id
            ];
        }
        return $blucompetencies;
    }
    
}
