<?php
namespace blumod_graph;

defined('MOODLE_INTERNAL') || die();
class repository {

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
        $rows = [];

        // Componentes (sub-learning units)
        $subs = $DB->get_records_select('block_blusub', "id_blu $insql", $params);
        foreach ($subs as $s) {
            $rows[] = (object) [
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
            $rows[] = (object) [
                'source'      => $p->id_blu,
                'sourceLabel' => $bluLabel((int) $p->id_blu),
                'target'      => $p->id_pre,
                'targetLabel' => $bluLabel((int) $p->id_pre),
                'type'        => 'pre',
            ];
        }

        // BLUs sin ninguna relación (nodos aislados)
        $connected = [];
        foreach ($rows as $r) {
            $connected[$r->source] = true;
            $connected[$r->target] = true;
        }
        foreach ($blus as $id => $blu) {
            if (empty($connected[$id])) {
                $rows[] = (object) [
                    'source'      => $id,
                    'sourceLabel' => $blu->description,
                    'target'      => null,
                    'targetLabel' => null,
                    'type'        => null,
                ];
            }
        }

        return self::to_bindings($rows);
    }

    /**
     * Query ForjaLens: learning_units_without_resources()
     */
    public static function get_learningunits_without_resources(int $courseid): array {
        global $DB;

        $sql = "SELECT blu.id AS bluid, blu.description AS name
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

        $params = ['courseid' => $courseid,'deletioninprogress' => '0'];
        $sql = "SELECT cm.id id, cm.instance instance, m.name AS module_name
                     FROM {course_modules} cm
                      LEFT JOIN {modules} m ON m.id = cm.module
                      LEFT JOIN {block_blumod} bm ON bm.module = cm.id
                     WHERE cm.deletioninprogress = :deletioninprogress
                      AND cm.course = :courseid
                      AND bm.id IS NULL
                     ORDER BY cm.section,cm.id ASC";
        $modules = $DB->get_records_sql($sql, $params);
        $resources = [];

        foreach ($modules as $module) {            
            $result = $DB->get_record($module->module_name,['id'=>$module->instance]);
            $resources[] = (object)[
                'itemType' => $module->module_name,
                'label' => $result->name,
            ];
        }

        $params = ['courseid' => $courseid];
        $sql = "SELECT gi.id, gi.itemname, 'Calificador: '
                     FROM {grade_items} gi
                     LEFT JOIN {block_blumod} bm ON bm.module = gi.id
                     WHERE gi.courseid = :courseid
                      AND gi.itemtype = 'manual'
                     ORDER BY gi.id ASC";
        $gradeitems = $DB->get_records_sql($sql, $params);

        foreach ($gradeitems as $gradeitem) {

            $resources[] = (object)[
                'itemType' => 'Calificador manual',
                'label' => $gradeitem->itemname,
            ];
        }



        return self::to_bindings($resources);
    }

    /**
     * Query ForjaLens: resource_learningunit_relations()
     * TODO
     */
    public static function get_resource_learningunit_relations(int $courseid): array {
        global $DB;

        $sql = "SELECT bm.id AS relid,
                       bm.module AS source,
                       blu.id AS target,
                       m.name AS sourceLabel,
                       blu.description AS targetLabel,
                       'module' AS sourceType,
                       'blu' AS targetType,
                       'component_blu' AS type
                  FROM {block_blumod} bm
                  JOIN {modules} m ON m.id = bm.module
                  JOIN {block_blu} blu ON blu.id = bm.blu
                 WHERE bm.course = :courseid
              ORDER BY blu.description, m.name";

        return self::to_bindings($DB->get_records_sql($sql, ['courseid' => $courseid]));
    }

    /**
     * Query ForjaLens: learningunit_resource_relations()
     * TODO, ni siquera aparece en el selector
     */
    public static function get_learningunit_resource_relations(int $courseid): array {
        global $DB;

        $sql = "SELECT blu.id AS source,
                       bm.module AS target,
                       blu.description AS sourceLabel,
                       m.name AS targetLabel,
                       'blu' AS sourceType,
                       'module' AS targetType,
                       'lu_module' AS type
                  FROM {block_blu} blu
             LEFT JOIN {block_blumod} bm ON bm.blu = blu.id
             LEFT JOIN {modules} m ON m.id = bm.module
                 WHERE blu.course = :courseid
              ORDER BY blu.description, m.name";

        return self::to_bindings($DB->get_records_sql($sql, ['courseid' => $courseid]));
    }

    /**
     * Query ForjaLens: assessmentitem_learningunit_relations()
     * TODO
     */
    public static function get_assessmentitem_learningunit_relations(int $courseid): array {
        global $DB;

        $sql = "SELECT bc.id AS relid,
                       blu.id AS target,
                       blu.description AS targetLabel,
                       comp.id AS source,
                       comp.shortname AS sourceLabel,
                       'competency' AS sourceType,
                       'blu' AS targetType,
                       'competency_blu' AS type
                  FROM {block_blucompetency} bc
                  JOIN {block_blu} blu ON blu.id = bc.bluid
                  JOIN {competency} comp ON comp.id = bc.competencyid
                 WHERE blu.course = :courseid
              ORDER BY blu.description, comp.shortname";

        return self::to_bindings($DB->get_records_sql($sql, ['courseid' => $courseid]));
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