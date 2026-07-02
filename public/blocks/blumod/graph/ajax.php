<?php
/**
 *
 * Uso desde el frontend (ver javascript/conexiones.js):
 *   GET ajax.php?action=get_course_structure&courseid=123&sesskey=xxxx
 *
 */

require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->dirroot . '/blocks/blumod/graph/classes/repository.php');

use blumod_graph\repository;

require_login();
require_sesskey();

$action   = required_param('action', PARAM_ALPHANUMEXT);
$courseid = required_param('courseid', PARAM_INT);

$course  = get_course($courseid); // lanza excepción si no existe
$context = context_course::instance($courseid);

header('Content-Type: application/json; charset=utf-8');

try {
    switch ($action) {
        case 'get_learningunits_without_resources':
            echo json_encode(repository::get_learningunits_without_resources($courseid));
            break;

        case 'get_resources_without_learning_units':
            echo json_encode(repository::get_resources_without_learning_units($courseid));
            break;

        case 'get_resource_learningunit_relations':
            echo json_encode(repository::get_resource_learningunit_relations($courseid));
            break;

        case 'get_assessmentitem_learningunit_relations':
            echo json_encode(repository::get_assessmentitem_learningunit_relations($courseid));
            break;

        case 'get_learningunit_resource_relations':
            echo json_encode(repository::get_learningunit_resource_relations($courseid));
            break;
        
        case 'get_course_structure':
            echo json_encode(repository::get_course_structure($courseid));
            break;

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Unknown action: ' . $action]);
    }
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
