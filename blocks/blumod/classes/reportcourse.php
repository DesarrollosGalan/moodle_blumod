<?php

defined('MOODLE_INTERNAL') || die();

class reportcourse_selector {
    /** @var int */
    private $courseid;

    /** @var ?int */
    // private $bluid;

    /** @var array */
    private $resources = [];

    private $name = 'reportcourseselector';
    private $rows = 10;    

    public function __construct(int $courseid) {
        $this->courseid = $courseid;

        // $this->loadresources();
    }

    /*
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
    }
    */
    public function loadData()
    {
        global $DB;
        
        /*
        $sql = "SELECT blu.id AS bluid, 
                       blu.description AS bluname, 
                       blumod.id AS resourceid, 
                       blumod.module AS resourcename, 
                       0 AS resourcecompleted, 
                       blucompetency.competencyid AS competencyid, 
                       competency.shortname AS competencyshortname
                FROM {block_blu} blu
                LEFT JOIN {block_blumod} blumod ON blumod.blu = blu.id
                LEFT JOIN {block_blucompetency} blucompetency ON blucompetency.bluid = blu.id
                LEFT JOIN {competency} competency ON blucompetency.competencyid = competency.id
                WHERE blu.course = :courseid
                ORDER BY blu.course ASC, blu.id ASC, blumod.module ASC, blucompetency.competencyid ASC";
        */
        $sql = "
SELECT
 blu.id AS bluid, 
 blu.description AS bluname, 
 blumod.id AS blumodid,
 blumod.module AS blumodmodule,
 cm.module AS cmmodule,
 cm.instance AS cminstance, 
 blucompetency.competencyid AS competencyid, 
 competency.shortname AS competencyshortname,
 modules.name AS modulename
FROM {block_blumod} blumod
LEFT JOIN {block_blu} blu ON blu.id = blumod.blu
     JOIN {course_modules} cm ON cm.id = blumod.module
LEFT JOIN {modules} modules ON modules.id = cm.module
LEFT JOIN {block_blucompetency} blucompetency ON blucompetency.bluid = blu.id
LEFT JOIN {competency} competency ON blucompetency.competencyid = competency.id
WHERE blu.course = :courseid AND cm.deletioninprogress = :deletioninprogress
ORDER BY blu.course ASC, blu.id ASC, blumod.module ASC, blucompetency.competencyid ASC;
        ";
        $params = ['courseid' => $this->courseid,'deletioninprogress' => '0'];
        $results = $DB->get_recordset_sql($sql, $params);

        $data = [];
        foreach ($results as $result) {
            $resource_name = $DB->get_record($result->modulename,['id'=>$result->cminstance]);
            $resource_name_to_data = $result->modulename . ': ' . $resource_name->name;
            $data[] = (object) [
                'bluid' => $result->bluid, 
                'bluname' => $result->bluname , 
                'blumodid' => $result->blumodid, 
                'blumodmodule' => $result->blumodmodule, 
                'cmmodule' => $result->cmmodule, 
                'cminstance' => $result->cminstance, 
                'resourcename' => $resource_name_to_data, 
                'competencyid' => $result->competencyid, 
                'competencyshortname' => $result->competencyshortname
            ];
        }

        $results->close();
        return $data;
    }

    /*
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
    */

    /*
    private function get_blumods() {
        global $DB;        
        $params = array();
        $sql = "SELECT bm.id, bm.module
                      FROM {block_blumod} bm
                     WHERE bm.blu = :blu
                     ORDER BY bm.id ASC";
        $params = [];
        $blumods = $DB->get_records_sql($sql, $params);
        
        return $blumods;
    }
    */

}
