<?php
namespace blumod_graph;

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/blocks/blumod/constants.php');
class repository {

    private static function get_resource_module_types(): array {
        if (defined('BLUMOD_RESOURCE_MODULE_TYPES') && is_array(BLUMOD_RESOURCE_MODULE_TYPES)) {
            return BLUMOD_RESOURCE_MODULE_TYPES;
        }

        return [];
    }

    private static function get_assessment_module_types(): array {
        if (defined('BLUMOD_ASSESSMENT_MODULE_TYPES') && is_array(BLUMOD_ASSESSMENT_MODULE_TYPES)) {
            return BLUMOD_ASSESSMENT_MODULE_TYPES;
        }

        return [];
    }

    private static function build_module_type_filter(string $field, array $types, string $prefix = 'modtype'): array {
        global $DB;

        [$insql, $params] = $DB->get_in_or_equal($types, SQL_PARAMS_NAMED, $prefix);
        return ["$field $insql", $params];
    }

    private static function get_module_learningunit_rows(int $courseid, array $moduletypes, string $filterprefix): array {
        global $DB;

        if (empty($moduletypes)) {
            return [];
        }

        [$modulefiltersql, $modulefilterparams] = self::build_module_type_filter('m.name', $moduletypes, $filterprefix);
        $params = ['courseid' => $courseid, 'deletioninprogress' => '0'];
        $params = array_merge($params, $modulefilterparams);

        $sql = "SELECT
                  COALESCE(bm.id, 'cm-' || cm.id) AS relid,
                  cm.id AS cmid,
                  cm.instance AS instance,
                  m.name AS module_name,
                  bm.module AS bmmodule,
                  blu.id AS bluid,
                  blu.description AS bludescription
                FROM {course_modules} cm
                LEFT JOIN {modules} m ON m.id = cm.module
                LEFT JOIN {block_blumod} bm ON bm.module = cm.id
                LEFT JOIN {block_blu} blu ON blu.id = bm.blu
                WHERE cm.deletioninprogress = :deletioninprogress
                  AND cm.course = :courseid
                  AND $modulefiltersql
                ORDER BY cm.id ASC";

        return $DB->get_records_sql($sql, $params);
    }

    /**
     * Query ForjaLens: course_structure()
     */
    public static function get_course_structure(int $courseid): array {
        global $DB;

        $blus = $DB->get_records('block_blu', ['course' => $courseid], '', 'id, description');
        if (empty($blus)) {
            return self::to_bindings([]);
        }

        $bluids = array_keys($blus);
        $bluLabel = function (int $id) use ($blus): ?string {
                return $blus[$id]->description ?? null;
        };

        [$insql, $params] = $DB->get_in_or_equal($bluids);
        $results = [];

        // Componentes
        $subs = $DB->get_records_select('block_blusub', "id_blu $insql", $params);
        foreach ($subs as $s) {
            $results[] = (object) [
                'source'      => $s->id_blu,
                'sourceLabel' => $bluLabel((int) $s->id_blu),
                'target'      => $s->id_sub,
                'targetLabel' => $bluLabel((int) $s->id_sub),
                'type'        => 'sub',
            ];
        }

        // Prerequisitos
        $pres = $DB->get_records_select('block_blupre', "id_blu $insql", $params);
        foreach ($pres as $p) {
            $results[] = (object) [
                'source'      => $p->id_blu,
                'sourceLabel' => $bluLabel((int) $p->id_blu),
                'target'      => $p->id_pre,
                'targetLabel' => $bluLabel((int) $p->id_pre),
                'type'        => 'pre',
            ];
        }

        // BLUs sin ninguna relación (nodos aislados)
        $connected = [];
        foreach ($results as $r) {
            $connected[$r->source] = true;
            $connected[$r->target] = true;
        }
        foreach ($blus as $id => $blu) {
            if (empty($connected[$id])) {
                $results[] = (object) [
                    'source'      => $id,
                    'sourceLabel' => $blu->description,
                    'target'      => null,
                    'targetLabel' => null,
                    'type'        => null,
                ];
            }
        }

        return self::to_bindings($results);
    }

    /**
     * Query ForjaLens: learning_units_without_resources()
     * Puede darse el caso que se asigne alguna "resource" de la tabla {modules} y que no esté ni en BLUMOD_RESOURCE_MODULE_TYPES ni en BLUMOD_ASSESSMENT_MODULE_TYPES. 
     * En ese caso, se considerará que tiene alguna asignación al tener un registro en la tabla {block_blumod}, y no se muestra en esta consulta.
     */
    public static function get_learningunits_without_resources_assessements(int $courseid): array {
        global $DB;

        $sql = "SELECT 
                  blu.id AS bluid, 
                  blu.description AS name
                FROM {block_blu} blu
                LEFT JOIN {block_blumod} bm ON bm.blu = blu.id
                WHERE blu.course = :courseid
                  AND bm.id IS NULL
                ORDER BY blu.description";

        return self::to_bindings($DB->get_records_sql($sql, ['courseid' => $courseid]));
    }

    /**
     * Query ForjaLens: resources_without_learning_units()
     */
    public static function get_resources_without_learning_units(int $courseid): array {
        global $DB;

        $resourcetypes = self::get_resource_module_types();
        if (empty($resourcetypes)) {
            return self::to_bindings([]);
        }

        [$modulefiltersql, $modulefilterparams] = self::build_module_type_filter('m.name', $resourcetypes, 'grwlu');
        $params = ['courseid' => $courseid,'deletioninprogress' => '0'];
        $params = array_merge($params, $modulefilterparams);
        $sql = "SELECT 
                  cm.id AS cmid, 
                  cm.instance AS instance, 
                  m.name AS module_name
                FROM {course_modules} cm
                LEFT JOIN {modules} m ON m.id = cm.module
                LEFT JOIN {block_blumod} bm ON bm.module = cm.id
                WHERE cm.deletioninprogress = :deletioninprogress
                  AND cm.course = :courseid
                  AND $modulefiltersql
                  AND bm.id IS NULL
                ORDER BY cm.id ASC";
        $modules = $DB->get_records_sql($sql, $params);
        $results = [];

        foreach ($modules as $module) {            
            $module_item = $DB->get_record($module->module_name,['id'=>$module->instance]);
            $results[] = (object)[
                'itemType' => $module->module_name,
                'label' => $module_item->name,
            ];
        }

        return self::to_bindings($results);
    }

    /**
     * Query ForjaLens: resource_learningunit_relations()
     */
    public static function get_resource_learningunit_relations(int $courseid): array {
        global $DB;

        $resourcetypes = self::get_resource_module_types();
        if (empty($resourcetypes)) {
            return self::to_bindings([]);
        }

        $modules = self::get_module_learningunit_rows($courseid, $resourcetypes, 'grlr');
        $results = [];

        foreach ($modules as $module) {
            $module_item = $DB->get_record($module->module_name,['id'=>$module->instance]);
            if ($module->bluid === null) {
                $results[] = (object)[
                    'source' => 'cm-' . $module->cmid,
                    'sourceLabel' => $module->module_name . ': ' . $module_item->name,
                    'sourceType' => $module->module_name,
                    'target' => null,
                    'targetLabel' => null,
                    'targetType' => null, 
                    'type' => null,
                ];
            } else {
                $results[] = (object)[
                    'source' => 'cm-' . $module->cmid,
                    'sourceLabel' => $module->module_name . ': ' . $module_item->name,
                    'sourceType' => $module->module_name,
                    'target' => 'blu-' . $module->bluid,
                    'targetLabel' => $module->bludescription,
                    'targetType' => 'lu',
                    'type' => 'resource_learningunit',
                ];
            }

        }

        return self::to_bindings($results);
    }

    /**
     * Query ForjaLens: learningunit_resource_relations()
     */
    public static function get_learningunit_resource_relations(int $courseid): array {
        global $DB;

        $resourcetypes = self::get_resource_module_types();
        if (empty($resourcetypes)) {
            return self::to_bindings([]);
        }

        $params = ['courseid' => $courseid, 'modulecourseid' => $courseid, 'deletioninprogress' => '0'];
        [$modulefiltersql, $modulefilterparams] = self::build_module_type_filter('m.name', $resourcetypes, 'glrr');
        $params = array_merge($params, $modulefilterparams);
        $sql = "SELECT 
                  COALESCE(rel.bmid, -blu.id) AS relid,
                  rel.bmid AS bmid,
                  rel.cmid AS cmid,
                  rel.instance AS instance,
                  rel.module_name AS module_name,
                  rel.bmmodule AS bmmodule,
                  blu.id AS bluid,
                  blu.description AS bludescription
                FROM {block_blu} blu
                LEFT JOIN (
                  SELECT bm.id AS bmid,
                    bm.blu AS bluid,
                    bm.module AS bmmodule,
                    cm.id AS cmid,
                    cm.instance AS instance,
                    m.name AS module_name
                  FROM {block_blumod} bm
                  INNER JOIN {course_modules} cm ON cm.id = bm.module
                  INNER JOIN {modules} m ON m.id = cm.module
                  WHERE cm.course = :modulecourseid
                    AND cm.deletioninprogress = :deletioninprogress
                    AND $modulefiltersql
                ) rel ON rel.bluid = blu.id
                WHERE blu.course = :courseid
                ORDER BY blu.id ASC, rel.cmid ASC";
        $blus = $DB->get_records_sql($sql, $params);
        $results = [];

        foreach ($blus as $blu) {
            
            if ($blu->bmid === null) {
                $results[] = (object)[
                    'source' => 'blu-' . $blu->bluid,
                    'sourceLabel' =>  $blu->bludescription,
                    'sourceType' => 'lu',
                    'target' => null,
                    'targetLabel' => null,
                    'targetType' => null,
                    'type' => null,
                ];
            } else {
                $module_item = $DB->get_record($blu->module_name,['id'=>$blu->instance]);
                $results[] = (object)[
                    'source' => 'blu-' . $blu->bluid,
                    'sourceLabel' => $blu->bludescription,
                    'sourceType' => 'lu',
                    'target' => 'cm-' . $blu->cmid,
                    'targetLabel' => $blu->module_name . ': ' . $module_item->name,
                    'targetType' => $blu->module_name,
                    'type' => 'resource_learningunit',
                ];
            }

        }

        return self::to_bindings($results);

    }

    /**
     * Query ForjaLens: assessmentitem_learningunit_relations()
     * TODO
     */
    public static function get_assessmentitem_learningunit_relations(int $courseid): array {
        global $DB;

        $assessmenttypes = self::get_assessment_module_types();
        if (empty($assessmenttypes)) {
            return self::to_bindings([]);
        }

        $modules = self::get_module_learningunit_rows($courseid, $assessmenttypes, 'galr');
        $results = [];

        foreach ($modules as $module) {
            $moduleitem = $DB->get_record($module->module_name, ['id' => $module->instance]);
            if (!$moduleitem || empty($moduleitem->name)) {
                continue;
            }

            if ($module->bluid === null) {
                $results[] = (object)[
                'source' => 'cm-' . $module->cmid,
                    'sourceLabel' => $module->module_name . ': ' . $moduleitem->name,
                    'sourceType' => $module->module_name,
                    'target' => null,
                    'targetLabel' => null,
                    'targetType' => null,
                    'type' => null,
                ];
            } else {
                $results[] = (object)[
                    'source' => 'cm-' . $module->cmid,
                    'sourceLabel' => $module->module_name . ': ' . $moduleitem->name,
                    'sourceType' => $module->module_name,
                    'target' => 'blu-' . $module->bluid,
                    'targetLabel' => $module->bludescription,
                    'targetType' => 'lu',
                    'type' => 'assessmentitem_learningunit',
                ];
            }
        }
        
        $params = ['courseid' => $courseid];
        $sql = "SELECT 
                  COALESCE(bm.id, 'gi-' || gi.id) AS relid,
                  gi.id AS giid, 
                  gi.itemname AS itemname, 
                  blu.id AS bluid, 
                  blu.description AS bludescription
                FROM {grade_items} gi
                LEFT JOIN {block_blumod} bm ON bm.module = gi.id
                LEFT JOIN {block_blu} blu ON blu.id = bm.blu
                WHERE gi.courseid = :courseid
                  AND gi.itemtype = 'manual'
                ORDER BY gi.id ASC";
        $gradeitems = $DB->get_records_sql($sql, $params);

        foreach ($gradeitems as $gradeitem) {
        
            if ($gradeitem->bluid === null) {
                $results[] = (object)[
                    'source' => 'gi-' . $gradeitem->giid,
                    'sourceLabel' =>  $gradeitem->itemname,
                    'sourceType' => 'calificador',
                    'target' => null,
                    'targetLabel' => null,
                    'targetType' => null,
                    'type' => null,
            ];
            } else {
                $results[] = (object)[
                    'source' => 'gi-' . $gradeitem->giid,
                    'sourceLabel' => $gradeitem->itemname,
                    'sourceType' => 'calificador',
                    'target' => 'blu-' . $gradeitem->bluid,
                    'targetLabel' => $gradeitem->bludescription,
                    'targetType' => 'lu',
                    'type' => 'assessmentitem_learningunit',
                ];

            }
        }

        return self::to_bindings($results);
    }

    
    /**
     * Wrapper para el resultado de las queries y obtener los bindings
     * {results: {bindings: [{field: {type: "literal", value: "..."}}]}}
     * Usado en by graph.js and table.js
     */
    private static function to_bindings(array $records): array {
        $bindings = [];

        foreach ($records as $record) {
            $row = [];
            foreach ((array) $record as $key => $value) {
                // "relid" is internal technical ID, not a graph field
                // Excluded like SPARQL unselected variables
                if ($key === 'relid' || $value === null || $value === '') {
                    continue;
                }
                $row[$key] = [
                    'type'  => 'literal',
                    'value' => (string) $value,
                ];
            }
            $bindings[] = $row;
        }

        return ['results' => ['bindings' => $bindings]];
    }
}