<?php

defined('MOODLE_INTERNAL') || die();
if (!defined ("RAIZ_MOODLE")){
    define('RAIZ_MOODLE',dirname(dirname(dirname(__FILE__))));//Raiz de la instalacion de Moodle debe estar dos directorios por encima del actual
    
}

require_once(dirname(__FILE__) . '/constants.php'); //Constantes del bloque
require_once(RAIZ_MOODLE."/config.php");//Necesitamos la configuracion de Moodle
require_once($CFG->libdir.'/moodlelib.php');


function block_blumod_has_permissions()
{
    global $USER;
    // global $USER, $COURSE;
    
    $contexto = context_system::instance();
    // $coursecontext = context_course::instance($COURSE->id);
    // if (has_capability('block/blumod:addinstance', $coursecontext))
    if (has_capability('block/blumod:addinstance',$contexto)){
        return true;
    }
    else {
        return false;
    }
}


