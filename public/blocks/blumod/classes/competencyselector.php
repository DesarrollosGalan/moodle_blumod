<?php

defined('MOODLE_INTERNAL') || die();

use core_competency\external;
use core_competency\competency;

class competency_selector {
    /** @var int */
    private $courseid;

    /** @var ?int */
    private $bluid;

    /** @var array */
    private $competencies = [];

    /** @var array */
    private $frameworks = [];

    /** @var array */
    private $frameworkcompetencyids = [];

    /** @var array */
    private $childrenbyparent = [];

    private $name = 'competencyselector';
    private $rows = 10;    

    public function __construct(int $courseid, ?int $bluid = null) {
        $this->courseid = $courseid;
        $this->bluid = $bluid;
        $this->loadCompetencies();
    }

    public function add(int $id)
    {
        global $DB;

        if (!$DB->record_exists('competency', ['id' => $id])) {
            return;
        }

        // BLUs can only be linked to leaf competencies.
        if ($DB->record_exists('competency', ['parentid' => $id])) {
            return;
        }

        if ($DB->record_exists('block_blucompetency', ['bluid' => $this->bluid, 'competencyid' => $id])) {
            return;
        }

        $sibling = new stdClass();
        $sibling->bluid = $this->bluid;
        $sibling->competencyid = $id;
        $DB->insert_record('block_blucompetency', $sibling);

        try {
            $result = external::add_competency_to_course($this->courseid, $id);
            if ($result) {
                echo "Competency successfully added to the course.";
            } else {
                echo "Failed to add competency to the course.";
            }
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage();
        }

    }

    public function del(int $id)
    {
        global $DB;
        $DB->delete_records('block_blucompetency', ['competencyid' => $id, 'bluid' => $this->bluid]);
    }

    public function display () {
        $assignedbyframework = $this->get_blucompetencies();
        $assignedleafids = [];
        foreach ($assignedbyframework as $frameworkid => $competencyids) {
            foreach ($competencyids as $competencyid) {
                $assignedleafids[$competencyid] = true;
            }
        }

        $available = $this->availableCompetencies($assignedleafids);
        $assigned = $this->assignedCompetencies($assignedleafids);

        $output = html_writer::start_tag('div', ['id' => $this->name . '_wrapper', 'class' => 'userselector' ]);
        $output .= html_writer::tag('h2', get_string('availablecompetencies', 'block_blumod'));

        $searchid = $this->name . '_search';
        $availableid = $this->name . '_available';        
        $output .= html_writer::start_div('mb-2');
        $output .= html_writer::label(get_string('searchcompetencies', 'block_blumod'), $searchid, false, ['class' => 'form-label']);
        $output .= html_writer::empty_tag('input', [
            'type' => 'text',
            'id' => $searchid,            
            'class' => 'form-control',
            'value' => '',
            'placeholder' => get_string('searchcompetenciesplaceholder', 'block_blumod'),
            'autocomplete' => 'off'
        ]);
        $output .= html_writer::end_tag('div');

        $output .= html_writer::start_div('mb-2');
        $output .= '<button class="btn btn-secondary btn-secondary-blu" data-action="collapse-available"><i class="fa fa-compress"></i> '. get_string('collapseall', 'block_blumod') . '</button>';
        $output .= '<button class="btn btn-secondary btn-secondary-blu" data-action="expand-available"><i class="fa fa-expand"></i> '. get_string('expandall', 'block_blumod') . '</button>';
        $output .= html_writer::end_tag('div');

        $output .= $this->displaySelect($availableid, $available);
        $output .= '<button class="btn btn-secondary btn-secondary-blu" data-action="add" data-from="' . $this->name. '_available"><i class="fa fa-link"></i> '. get_string('addblu', 'block_blumod') . '</button>';

        $output .= html_writer::tag('h2', get_string('assignedcompetencies', 'block_blumod'));
        $output .= $this->displaySelect($this->name. '_assigned', $assigned);
        $output .= '<button class="btn btn-secondary btn-secondary-blu" data-action="del" data-from="' . $this->name. '_assigned"><i class="fa fa-unlink"></i> '. get_string('delblu', 'block_blumod') . '</button>';
        
        $output .= html_writer::end_tag('div');

        return $output;

    }

    private function loadCompetencies()
    {

        global $DB;

        $frameworksql = "SELECT id, shortname
                           FROM {competency_framework}
                       ORDER BY shortname ASC, id ASC";
        $frameworks = $DB->get_records_sql($frameworksql);
        foreach ($frameworks as $framework) {
            $frameworkid = (int)$framework->id;
            $this->frameworks[$frameworkid] = $framework->shortname;
            $this->frameworkcompetencyids[$frameworkid] = [];
        }

        $sql = "SELECT co.id id,
                       co.parentid,
                       co.path,
                       co.sortorder,
                       co.competencyframeworkid cf_id,
                       co.shortname co_shortname
                  FROM {competency} co
              ORDER BY co.competencyframeworkid ASC, co.path ASC, co.sortorder ASC, co.id ASC";
        $results = $DB->get_records_sql($sql, []);

        foreach ($results as $result) {
            $frameworkid = (int)$result->cf_id;
            if (!array_key_exists($frameworkid, $this->frameworks)) {
                $this->frameworks[$frameworkid] = '';
                $this->frameworkcompetencyids[$frameworkid] = [];
            }

            $level = $this->get_competency_level($result->path);
            $this->competencies[$result->id] = (object)[
                'id' => (int)$result->id,
                'frameworkid' => $frameworkid,
                'frameworkname' => $this->frameworks[$frameworkid],
                'shortname' => $result->co_shortname,
                'parentid' => (int)$result->parentid,
                'path' => $result->path,
                'sortorder' => (int)$result->sortorder,
                'level' => $level,
            ];

            $this->frameworkcompetencyids[$frameworkid][] = (int)$result->id;
            if (!array_key_exists((int)$result->parentid, $this->childrenbyparent)) {
                $this->childrenbyparent[(int)$result->parentid] = [];
            }
            $this->childrenbyparent[(int)$result->parentid][] = (int)$result->id;
        }

    }

    private function availableCompetencies(array $assignedleafids): array
    {
        $available = [];

        foreach ($this->frameworks as $frameworkid => $frameworkname) {
            $available[] = [
                'value' => 'F' . $frameworkid,
                'label' => '▼ ' . ($frameworkname !== '' ? $frameworkname : ('Framework ' . $frameworkid)),
                'selectable' => false,
                'rowtype' => 'framework',
                'frameworkid' => $frameworkid,
                'level' => 0,
                'isleaf' => false,
            ];

            $frameworktree = competency::get_framework_tree((int)$frameworkid);
            if (empty($frameworktree)) {
                continue;
            }

            $available = array_merge(
                $available,
                $this->flattenTreeAvailableCompetencies($frameworktree, $assignedleafids, 1)
            );
        }
    
        return $available;
    }

    private function flattenTreeAvailableCompetencies(array $tree, array $assignedleafids, int $level = 1): array
    {
        $options = [];

        foreach ($tree as $node) {
            if (empty($node->competency)) {
                continue;
            }

            $competency = $node->competency;
            $competencyid = (int)$competency->get('id');
            $shortname = (string)$competency->get('shortname');
            $children = empty($node->children) ? [] : $node->children;
            $isleaf = empty($children);

            if ($isleaf && isset($assignedleafids[$competencyid])) {
                continue;
            }

            $options[] = [
                'value' => (string)$competencyid,
                'label' => $this->build_indented_label($shortname, $level, $isleaf),
                'selectable' => $isleaf,
                'rowtype' => 'competency',
                'frameworkid' => (int)$competency->get('competencyframeworkid'),
                'level' => $level,
                'isleaf' => $isleaf,
            ];

            if (!$isleaf) {
                $options = array_merge(
                    $options,
                    $this->flattenTreeAvailableCompetencies($children, $assignedleafids, $level + 1)
                );
            }
        }

        return $options;
    }

    private function assignedCompetencies(array $assignedleafids): array
    {
        $assigned = [];

        foreach ($this->frameworks as $frameworkid => $frameworkname) {
            $frameworktree = competency::get_framework_tree((int)$frameworkid);
            if (empty($frameworktree)) {
                continue;
            }

            $branchoptions = $this->flattenTreeAssignedCompetencies($frameworktree, $assignedleafids, 1);
            if (empty($branchoptions)) {
                continue;
            }

            $assigned[] = [
                'value' => 'F' . $frameworkid,
                'label' => '▼ ' . ($frameworkname !== '' ? $frameworkname : ('Framework ' . $frameworkid)),
                'selectable' => false,
                'rowtype' => 'framework',
                'frameworkid' => $frameworkid,
                'level' => 0,
                'isleaf' => false,
            ];

            $assigned = array_merge($assigned, $branchoptions);
        }

        return $assigned;
    }

    private function flattenTreeAssignedCompetencies(array $tree, array $assignedleafids, int $level = 1): array
    {
        $options = [];

        foreach ($tree as $node) {
            if (empty($node->competency)) {
                continue;
            }

            $competency = $node->competency;
            $competencyid = (int)$competency->get('id');
            $shortname = (string)$competency->get('shortname');
            // $competencyid = (int)$competency->id;
            // $shortname = (string)$competency->shortname;
            $children = empty($node->children) ? [] : $node->children;
            $isleaf = empty($children);

            $childoptions = [];
            if (!$isleaf) {
                $childoptions = $this->flattenTreeAssignedCompetencies($children, $assignedleafids, $level + 1);
            }

            $isassignedleaf = $isleaf && isset($assignedleafids[$competencyid]);
            $hassassigneddescendant = !empty($childoptions);

            if (!$isassignedleaf && !$hassassigneddescendant) {
                continue;
            }

            $options[] = [
                'value' => $isassignedleaf ? (string)$competencyid : 'C' . $competencyid,
                'label' => $this->build_indented_label($shortname, $level, $isleaf),
                'selectable' => $isassignedleaf,
                'rowtype' => 'competency',
                'frameworkid' => (int)$competency->get('competencyframeworkid'),
                'level' => $level,
                'isleaf' => $isleaf,
            ];

            if (!empty($childoptions)) {
                $options = array_merge($options, $childoptions);
            }
        }

        return $options;
    }

    private function displaySelect(string $name, array $data, bool $multiselect = true): string
    {

        $output = '<select name="' . $name . '" id="' . $name . '" ' .
            ($multiselect ? 'multiple="multiple" ' . 'size="' . $this->rows . '"' : '') . ' class="form-control no-overflow">' . "\n";

        $isavailableselect = ($name === $this->name . '_available');

        foreach ($data as $option) {
            $value = s((string)$option['value']);
            $label = s((string)$option['label']);
            $attrs = " value=\"{$value}\"";

            if (isset($option['rowtype'])) {
                $attrs .= ' data-rowtype="' . s((string)$option['rowtype']) . '"';
            }

            if (isset($option['frameworkid'])) {
                $attrs .= ' data-frameworkid="' . (int)$option['frameworkid'] . '"';
            }

            if (isset($option['level'])) {
                $attrs .= ' data-level="' . (int)$option['level'] . '"';
            }

            if (isset($option['isleaf'])) {
                $attrs .= ' data-isleaf="' . ($option['isleaf'] ? '1' : '0') . '"';
            }

            $attrs .= ' data-selectable="' . (!empty($option['selectable']) ? '1' : '0') . '"';

            if (empty($option['selectable']) && !$isavailableselect) {
                $attrs .= ' disabled="disabled"';
            }

            if (strpos((string)$option['value'], 'F') === 0) {
                $attrs .= ' style="font-weight: bold;"';
            }

            $output .= "<option{$attrs}>{$label}</option>";
        }

        $output .= "</select>";

        return $output;
    }

    private function get_blucompetencies() {
        global $DB;      

        $params = [];
        $sql = "SELECT bc.competencyid bc_competencyid, co.competencyframeworkid cf_id, co.path
                      FROM {block_blucompetency} bc
                         JOIN {competency} co ON bc.competencyid = co.id
                     WHERE bc.bluid = :blu
                     ORDER BY co.competencyframeworkid ASC, co.path ASC, co.sortorder ASC, co.id ASC";
        $params = ['blu' => $this->bluid];
        $results = $DB->get_records_sql($sql, $params);

        // xdebug_break();
        
        $blucompetencies = [];
        foreach ($results as $result) {
            $frameworkid = (int)$result->cf_id;
            if (!array_key_exists($frameworkid, $blucompetencies)) {
                $blucompetencies[$frameworkid] = [];
            }            

            $blucompetencies[$frameworkid][] = (int)$result->bc_competencyid;
        }

        return $blucompetencies;
    }

    private function get_competency_level(string $path): int
    {
        $trimmed = trim($path, '/');
        if ($trimmed === '') {
            return 1;
        }

        return substr_count($trimmed, '/') + 1;
    }

    private function is_leaf_competency(int $competencyid): bool
    {
        return empty($this->childrenbyparent[$competencyid]);
    }

    private function get_path_ids(string $path): array
    {
        $parts = explode('/', trim($path, '/'));
        $ids = [];
        foreach ($parts as $part) {
            $id = (int)$part;
            if ($id > 0) {
                $ids[] = $id;
            }
        }

        return $ids;
    }

    private function build_indented_label(string $shortname, int $level, bool $isleaf): string
    {
        $depth = max(0, $level);
        $indent = str_repeat("\u{2003}", $depth);
        $prefix = $isleaf ? '' : '▼ ';

        return $indent . $prefix . $shortname;
    }

    private function framework_label(string $frameworkname, string $label): string
    {
        return $frameworkname . ': ' . $label;
    }
    
}
