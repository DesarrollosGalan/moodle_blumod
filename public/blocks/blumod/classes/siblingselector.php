<?php

defined('MOODLE_INTERNAL') || die();

class sibling_selector
{
    public const PRE = 'pre';
    public const SUB = 'sub';

    private const VALID_TYPES = [
        self::PRE,
        self::SUB,
    ];

    /** @var string */
    private $type;
    /** @var string */
    private $name;

    /** @var int */
    private $bluid;

    private $rows = 5;

    public function __construct(string $type, int $bluid)
    {
        if (!in_array($type, self::VALID_TYPES)) {
            throw new Exception('Tipo no válido');
        }

        $this->type = $type;
        $this->name = "blu{$type}selector";
        $this->bluid = $bluid;
    }

    public function add(int $id)
    {
        global $DB;
        $idType = "id_{$this->type}";

        $sibling = new stdClass();
        $sibling->id_blu = $this->bluid;
        $sibling->$idType = $id;
        $DB->insert_record("block_blu{$this->type}", $sibling);
    }

    public function del(int $id)
    {
        global $DB;
        $DB->delete_records("block_blu{$this->type}", ['id' => $id]);
    }

    public function display()
    {
        $output = '<div class="userselector" id="' . $this->name . '_wrapper">' . "\n";
        $output .= $this->displaySelect("{$this->name}_selected", $this->get_selected());
        $output .= '<button class="btn btn-secondary btn-secondary-blu" data-action="del' . $this->type . '" data-from="' . $this->name. '_selected"><i class="fa fa-unlink"></i> '. get_string('delblu', 'block_blumod') . '</button>';
        $output .= $this->displaySelect("{$this->name}_possible", $this->get_possible(), false);
        $output .= '<button class="btn btn-secondary btn-secondary-blu" data-action="add' . $this->type . '" data-from="' . $this->name. '_possible"><i class="fa fa-link"></i> '. get_string('addblu', 'block_blumod') . '</button>';
        $output .= "</div>";

        return $output;
    }

    private function displaySelect(string $name, $data, bool $multiselect = true): string
    {

        $output = '<select name="' . $name . '" id="' . $name . '" ' .
            ($multiselect ? 'multiple="multiple" ' . 'size="' . $this->rows . '"' : '') . ' class="form-control no-overflow">' . "\n";

        foreach ($data as $option) {
            $output .= "<option value='{$option->id}'>{$option->description}</option>";
        }

        $output .= "</select>";

        return $output;
    }
    private function get_selected()
    {
        global $DB;
        $sql = "SELECT bp.id, b.description
                      FROM {block_blu{$this->type}} bp
                        LEFT JOIN {block_blu} b 
                            ON bp.id_{$this->type} = b.id
                     WHERE bp.id_blu = :bluid
                     ORDER BY b.description ASC";

        $params = ['bluid' => $this->bluid];
        $blus = $DB->get_records_sql($sql, $params);

        return $blus;
    }
    private function get_possible()
    {
        global $DB;
        $sql = "SELECT bs.id, bs.description
                      FROM {block_blu} b 
                        INNER JOIN {block_blu} bs
                            ON b.course = bs.course
                                AND b.id != bs.id
                        LEFT JOIN {block_blu{$this->type}} bp
                            ON b.id = bp.id_blu
                             AND bs.id = bp.id_{$this->type}
                     WHERE b.id = :bluid
                        AND bp.id_{$this->type} IS NULL
                     ORDER BY bs.description ASC";

        $params = ['bluid' => $this->bluid];
        $blus = $DB->get_records_sql($sql, $params);

        return $blus;
    }
}
