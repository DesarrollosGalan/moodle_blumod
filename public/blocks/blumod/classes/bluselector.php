<?php

defined('MOODLE_INTERNAL') || die();

class blu_selector {
    /** @var int */
    private $courseid;

    private $name = "bluselector";
    public function __construct(int $courseid) {
        $this->courseid = $courseid;
    } 
    protected static $jsmodule = array(
        'name' => 'blu_selector',
        'fullpath' => '/block/blumod/selector/module.js',
        'requires'  => array('node', 'event-custom', 'datasource', 'json', 'moodle-core-notification'),
        'strings' => array(
            array('previouslyselectedusers', 'moodle', '%%SEARCHTERM%%'),
            array('nomatchingusers', 'moodle', '%%SEARCHTERM%%'),
            array('none', 'moodle')
        ));
    private $rows = 8;
    public function display () {
        $name = "bluselector";
        $multiselect = 'multiple="multiple" ';
        $output = '<div class="userselector" id="' . $this->name . '_wrapper">' . "\n" .
            '<select name="' . $name . '" id="' . $this->name . '" ' .
            $multiselect . 'size="' . $this->rows . '" class="form-control no-overflow">' . "\n";
        $blus = $this->get_blus();
        foreach ($blus as $blu) {
            $output .= "<option value='{$blu->id}'>{$blu->description}</option>";
        }
        $output .= "</select></div>";
        return $output;
    }
    private function get_blus() {
        // leer la bbdd los registros blus
        global $DB, $CFG;
        $params_blus = array();
        $sql_blus = "SELECT b.id,
                            b.description
                      FROM {block_blu} b
                     WHERE b.course = :courseid
                     ORDER BY b.description ASC";
        $params_blus = ['courseid' => $this->courseid];
        $blus = $DB->get_records_sql($sql_blus, $params_blus);
        return $blus;
    }
}
