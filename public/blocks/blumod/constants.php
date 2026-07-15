<?php
if (!defined ("NB_BLOQUE_I")){
    define("NB_BLOQUE_I","blumod");//Nombre del bloque
}

if (!defined ("LITERALES_I")){
    define("LITERALES_I","blumod");//Nombre del bloque
}

// El 'calificador manual' es resource de tipo assessmentitem aunque no esté definido en estas constantes, ya que está separado en la tabla {grade_items}
if (!defined('BLUMOD_LEARNINGRESOURCE_TYPES')) {
    define('BLUMOD_LEARNINGRESOURCE_TYPES', [
        'resource',
        'url',
        'page',
        'book',
        'folder',
        'wiki',
        'imscp',
        'scorm',
        'h5pactivity',
        'data',
        'lesson',
    ]);
}

if (!defined('BLUMOD_ASSESSMENTITEM_TYPES')) {
    define('BLUMOD_ASSESSMENTITEM_TYPES', [
        'assign',
        'quiz',
        'forum',
        'workshop',
        'lesson',
        'choice',
        'questionnaire',
    ]);
}
