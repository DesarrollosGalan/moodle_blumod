<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.


/**
 * A form for the creation and editing of BLUs.
 *
 * @copyright 
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package   block_blumod
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir.'/formslib.php');

/**
 * BLU form class
 *
 * @copyright 
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package   block_blumod
 */
class block_blumod_blu_form extends moodleform {

    /**
     * Definition of the form
     */
    function definition () {
        global $CFG, $COURSE;

        $mform =& $this->_form;
        $editoroptions = $this->_customdata['editoroptions'];

        $mform->addElement('text','description', get_string('bludesc', 'block_blumod'),'maxlength="254" size="50"');
        $mform->addRule('description', get_string('required'), 'required', null, 'client');
        $mform->setType('description', PARAM_TEXT);

        $mform->addElement('hidden','id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden','course');
        $mform->setType('course', PARAM_INT);

        $this->add_action_buttons();
    }

    /**
     * Extend the form definition after the data has been parsed.
     */
    public function definition_after_data() {
        global $COURSE, $DB, $USER;

        $mform = $this->_form;
        $bluid = $mform->getElementValue('id');

        // $blu = $DB->get_record('block_blu', array('id' => $bluid))

    }


    /**
     * Get editor options for this form
     *
     * @return array An array of options
     */
    function get_editor_options() {
        return $this->_customdata['editoroptions'];
    }
}
