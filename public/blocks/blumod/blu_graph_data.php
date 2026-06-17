<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * AJAX endpoint: returns JSON data for the BLU structure graph visualization.
 * Returns main BLU, its sub-BLUs (block_blusub) and prerequisite BLUs (block_blupre).
 *
 * @package block_blumod
 */

require_once '../../config.php';

global $DB, $CFG;

$bluid    = required_param('bluid',    PARAM_INT);
$courseid = required_param('courseid', PARAM_INT);

$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
require_login($course);

$context = context_course::instance($courseid);
require_capability('block/blumod:manageblus', $context);

// Main BLU
$main = $DB->get_record('block_blu', ['id' => $bluid], 'id, description', MUST_EXIST);

// Sub-BLUs: entries in block_blusub where id_blu = $bluid
$sql_subs = "SELECT bs.id_sub AS id, b.description
               FROM {block_blusub} bs
               JOIN {block_blu} b ON b.id = bs.id_sub
              WHERE bs.id_blu = :bluid";
$subs = array_values($DB->get_records_sql($sql_subs, ['bluid' => $bluid]));

// Prerequisite BLUs: entries in block_blupre where id_blu = $bluid
$sql_pres = "SELECT bp.id_pre AS id, b.description
               FROM {block_blupre} bp
               JOIN {block_blu} b ON b.id = bp.id_pre
              WHERE bp.id_blu = :bluid";
$pres = array_values($DB->get_records_sql($sql_pres, ['bluid' => $bluid]));

header('Content-Type: application/json');
echo json_encode([
    'main' => ['id' => (int)$main->id, 'description' => $main->description],
    'subs' => array_map(fn($r) => ['id' => (int)$r->id, 'description' => $r->description], $subs),
    'pres' => array_map(fn($r) => ['id' => (int)$r->id, 'description' => $r->description], $pres),
]);
die();
