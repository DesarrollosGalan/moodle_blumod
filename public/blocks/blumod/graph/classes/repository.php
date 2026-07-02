<?php
namespace blumod_semanticviews;

defined('MOODLE_INTERNAL') || die();

/**
 *
 * Consulta directamente las tablas del bloque block_blu.
 * Reutiliza graph.js y table.js sin modificarlos.
 */
class repository {

    /**
     * Cursos que tienen al menos una Learning Unit definida.
     */
    public static function get_courses(): array {
        global $DB;

        $sql = "SELECT DISTINCT c.id AS course, c.fullname AS name
                  FROM {course} c
                  JOIN {block_blu} blu ON blu.course = c.id
              ORDER BY c.fullname";

        return self::to_bindings($DB->get_records_sql($sql));
    }

    /**
     * Learning Units de un curso que no tienen ningún componente
     * (módulo) asociado en block_blumod.
     */
    public static function blus_without_components(int $courseid): array {
        global $DB;

        $sql = "SELECT blu.id AS lu, blu.description AS name
                  FROM {block_blu} blu
             LEFT JOIN {block_blumod} bm ON bm.blu = blu.id
                 WHERE blu.course = :courseid
                   AND bm.id IS NULL
              ORDER BY blu.description";

        return self::to_bindings($DB->get_records_sql($sql, ['courseid' => $courseid]));
    }

    /**
     * Relación Componente (tipo de módulo Moodle) <-> Learning Unit.
     */
    public static function component_blu_relations(int $courseid): array {
        global $DB;

        $sql = "SELECT bm.id AS relid,
                       blu.id AS target,
                       blu.description AS targetLabel,
                       m.id AS source,
                       m.name AS sourceLabel,
                       m.name AS sourceType,
                       'lu' AS targetType,
                       'component_lu' AS type
                  FROM {block_blumod} bm
                  JOIN {block_blu} blu ON blu.id = bm.blu
                  JOIN {modules} m ON m.id = bm.module
                 WHERE bm.course = :courseid
              ORDER BY blu.description, m.name";

        return self::to_bindings($DB->get_records_sql($sql, ['courseid' => $courseid]));
    }

    /**
     * Relación Competencia <-> Learning Unit.
     */
    public static function competency_blu_relations(int $courseid): array {
        global $DB;

        $sql = "SELECT bc.id AS relid,
                       blu.id AS target,
                       blu.description AS targetLabel,
                       comp.id AS source,
                       comp.shortname AS sourceLabel,
                       'lu' AS targetType,
                       'competency' AS sourceType,
                       'competency_lu' AS type
                  FROM {block_blucompetency} bc
                  JOIN {block_blu} blu ON blu.id = bc.bluid
                  JOIN {competency} comp ON comp.id = bc.competencyid
                 WHERE blu.course = :courseid
              ORDER BY blu.description, comp.shortname";

        return self::to_bindings($DB->get_records_sql($sql, ['courseid' => $courseid]));
    }

    /**
     * Estructura completa del curso: todas las LU + relaciones de
     * jerarquía ("sub") y de prerequisito ("pre") entre ellas.
     */
    public static function course_structure(int $courseid): array {
        global $DB;

        $lus = $DB->get_records('block_blu', ['course' => $courseid], '', 'id, description');
        if (empty($lus)) {
            return self::to_bindings([]);
        }

        $luids = array_keys($lus);
        $luLabel = function (int $id) use ($lus, $DB): ?string {
            if (isset($lus[$id])) {
                return $lus[$id]->description;
            }
            // La LU objetivo puede pertenecer a otro curso (referencia cruzada);
            // se resuelve igualmente para no perder la etiqueta.
            return $DB->get_field('block_blu', 'description', ['id' => $id]) ?: null;
        };

        [$insql, $params] = $DB->get_in_or_equal($luids);
        $rows = [];

        // Jerarquía: LU padre -> LU hija.
        $subs = $DB->get_records_select('block_blusub', "id_blu $insql", $params);
        foreach ($subs as $s) {
            $rows[] = (object) [
                'source'      => $s->id_blu,
                'sourceLabel' => $luLabel((int) $s->id_blu),
                'target'      => $s->id_sub,
                'targetLabel' => $luLabel((int) $s->id_sub),
                'type'        => 'sub',
            ];
        }

        // Prerequisitos: LU -> LU requerida antes.
        $pres = $DB->get_records_select('block_blupre', "id_blu $insql", $params);
        foreach ($pres as $p) {
            $rows[] = (object) [
                'source'      => $p->id_blu,
                'sourceLabel' => $luLabel((int) $p->id_blu),
                'target'      => $p->id_pre,
                'targetLabel' => $luLabel((int) $p->id_pre),
                'type'        => 'pre',
            ];
        }

        // LUs sin ninguna relación (nodos aislados) también se incluyen,
        // para que no desaparezcan del grafo aunque no tengan sub/pre.
        $connected = [];
        foreach ($rows as $r) {
            $connected[$r->source] = true;
            $connected[$r->target] = true;
        }
        foreach ($lus as $id => $lu) {
            if (empty($connected[$id])) {
                $rows[] = (object) [
                    'source'      => $id,
                    'sourceLabel' => $lu->description,
                    'target'      => null,
                    'targetLabel' => null,
                    'type'        => null,
                ];
            }
        }

        return self::to_bindings($rows);
    }

    /**
     * Envuelve un array de stdClass/arrays asociativos en el contrato
     * "SPARQL-results" ({results:{bindings:[...]}}) que ya consumen
     * graph.js y table.js. Los valores null se omiten (igual que un
     * OPTIONAL sin resultado en SPARQL no genera esa clave).
     *
     * @param array $records
     * @return array
     */
    private static function to_bindings(array $records): array {
        $bindings = [];

        foreach ($records as $record) {
            $row = [];
            foreach ((array) $record as $key => $value) {
                // "relid" es un id técnico interno de la fila SQL, no un
                // campo del grafo: se descarta igual que se haría con
                // una variable SPARQL no seleccionada por el frontend.
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
