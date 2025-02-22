<?php
/*
 * @author  UPV/EHU - Noviembre de 2021
 * Objetivo: Visualizar el enlace a la evaluación docente
 *
 * Cada docente del aula podrá instalar dicho bloque y se generará un enlace personalizado.
 */

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__) . '/constants.php'); //Constantes del bloque
require_once(dirname(__FILE__) . '/lib.php');

class block_blumod extends block_base {
    
    public function init() {
      
        if (block_blumod_has_permissions()){
            
            $this->title = get_string('pluginname', 'block_blumod');
            
        }else{
            $this->title = '';
        }
    }
    
    public function specialization() {
        if (isset($this->config)) {
            if (empty($this->config->title)) {
                $this->title = get_string('defaulttitle', 'block_blumod');
            } else {
                $this->title = $this->config->title;
            }
            
            if (empty($this->config->text)) {
                $this->config->text = get_string('defaulttext', 'block_blumod');
            }
        }
    }
    
    // Esta funcion controla en que sitios (contextos) se puede añadir el bloque (solo tiene efecto sobre los nuevos intentos de añadir el bloque)
    public function applicable_formats() {
        return array('course' => true);
    }
    
    // para que se puedan instalar multiples bloques en un aula
    public function instance_allow_multiple() {
        return true;
    }
    
    public function has_config() {
        return false;
    }
    
    function get_content() {
        global $COURSE;
        if ($this->content !== null) {
          return $this->content;
        }
        
        if (empty($this->instance)) {
            $this->content = '';
            return $this->content;
        }

        // $coursemodules = new fetcher($this->page->context, $this->page->course->id);
        
        $this->content = new stdClass;
        $this->content->items = array();
        $this->content->icons = array();
        $this->content->footer = '';

        if ($COURSE->id == SITEID) {
            $context = context_system::instance();
        } else {
            $context = context_course::instance($COURSE->id);
        }
        
        if (! empty($this->config->text)) {
            $this->content->text = $this->config->text;
        }
        
        $blockid = $this->context->instanceid;
        $this->content->text = '';
        $html = '';
        
        $html .= html_writer::start_tag('p');
        $titulo = get_string('pluginname', 'block_blumod');
        $linktext = get_string('managerblumod', 'block_blumod');
        $url = new moodle_url('/blocks/blumod/index.php', ['courseid' => $COURSE->id]);
        $html .= html_writer::link($url, $linktext);
        $html .= html_writer::end_tag('p');
        
        $html .= html_writer::start_tag('p');
        $linktext = get_string('mapblumod', 'block_blumod');
        $url = new moodle_url('/blocks/blumod/map.php', ['courseid' => $COURSE->id]);
        $html .= html_writer::link($url, $linktext);
        $html .= html_writer::end_tag('p');

        $html .= html_writer::start_tag('p');
        $linktext = get_string('reportblumod', 'block_blumod');
        $url = new moodle_url('/blocks/blumod/reportcourse.php', ['courseid' => $COURSE->id, 'blumodid' => '']);
        $html .= html_writer::link($url, $linktext);
        $html .= html_writer::end_tag('p');

        $this->content->text =  $html;

        return $this->content;
    }
}