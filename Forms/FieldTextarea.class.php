<?php
declare(strict_types = 1);

namespace XCOMDatabank\Forms;

class FieldTextarea extends Field {

    public static string $label;
    public static array $divClass;

    function __construct(string $name, ?string $value, string $class, string $id, string $label, array $divClass,
                         bool $required, string $rows) {

        parent::__construct($name, $value);

        self::$inputArray['class'] = $class;
        self::$inputArray['id'] = $id;
        self::$inputArray['required'] = $required;
        self::$inputArray['rows'] = $rows;

        self::$label = $label;
        self::$divClass = $divClass;
    }

    public static function getField(string $name, ?string $value, string $class, string $id, string $label,
                                    array $divClass, bool $required, string $rows): string {

        $field = new static($name, $value, $class, $id, $label, $divClass, $required, $rows);
        return self::printField($field::$inputArray, $field::$label, $field::$divClass);
    }

    private static function printField(array $inputArray, string $label, array $divClass): string {
        $openDiv = "";
        $closeDiv = "";

        if(sizeof($divClass) > 0) {
            $openDiv = '<div class="' . implode(' ', $divClass).'">'."\n";
            $closeDiv = "\n".'</div>';
        }

        $formInput = '<textarea';
        foreach($inputArray as $key => $item) {
            if($key == "required") {
                if($item == true)
                    $formInput .= ' required';
            }
            elseif($key == "value") {
                $value = $item;
            }
            elseif($key != "type") {
                $formInput .= ' '.$key.'="'.$item.'"';
            }
        }
        $formInput .= ">".$value."</textarea>";

        $formLabel = '<label for="'.$inputArray['id'].'">'.$label.'</label>';

        return $openDiv.$formInput.$formLabel.$closeDiv;
    }
}