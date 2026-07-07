<?php
if (!defined ("NB_BLOQUE_I")){
    define("NB_BLOQUE_I","blumod");//Nombre del bloque
}

if (!defined ("LITERALES_I")){
    define("LITERALES_I","blumod");//Nombre del bloque
}

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
