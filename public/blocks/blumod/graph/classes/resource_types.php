<?php
namespace blumod_graph;

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/blocks/blumod/constants.php');

class resource_types {

    public static function get_learningresource_types(): array {
        if (defined('BLUMOD_LEARNINGRESOURCE_TYPES') && is_array(BLUMOD_LEARNINGRESOURCE_TYPES)) {
            return BLUMOD_LEARNINGRESOURCE_TYPES;
        }

        return [];
    }

    public static function get_assessmentitem_types(): array {
        if (defined('BLUMOD_ASSESSMENTITEM_TYPES') && is_array(BLUMOD_ASSESSMENTITEM_TYPES)) {
            return BLUMOD_ASSESSMENTITEM_TYPES;
        }

        return [];
    }

    public static function build_resource_types_filter(string $field, array $types, string $prefix = 'modtype'): array {
        global $DB;

        [$insql, $params] = $DB->get_in_or_equal($types, SQL_PARAMS_NAMED, $prefix);
        return ["$field $insql", $params];
    }
}