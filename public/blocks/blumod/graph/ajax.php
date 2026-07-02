<?php
/**
 *
 * Uso desde el frontend (ver javascript/conexiones.js):
 *   GET ajax.php?action=course_structure&courseid=123&sesskey=xxxx
 *
 */

require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->dirroot . '/blocks/blumod/graph/classes/repository.php');

use blumod_semanticviews\repository;

require_login();
require_sesskey();

$action   = required_param('action', PARAM_ALPHANUMEXT);
$courseid = required_param('courseid', PARAM_INT);

$course  = get_course($courseid); // lanza excepción si no existe
$context = context_course::instance($courseid);

header('Content-Type: application/json; charset=utf-8');

try {
    switch ($action) {
        case 'get_courses':
            echo json_encode(repository::get_courses());
            break;

        case 'blus_without_components':
            echo json_encode(repository::blus_without_components($courseid));
            break;

        case 'component_blu_relations':
            echo json_encode(repository::component_blu_relations($courseid));
            break;

        case 'competency_blu_relations':
            echo json_encode(repository::competency_blu_relations($courseid));
            break;

        case 'course_structure':
            echo json_encode(repository::course_structure($courseid));
            break;

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Unknown action: ' . $action]);
    }
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
